<?php

/* 
 	Copyright (C) 2007 Gilles Dubuc.
 
 	This file is part of iBorrow.

    iBorrow is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    iBorrow is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with iBorrow.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once (dirname(__FILE__).'/constants.php');
require_once (dirname(__FILE__).'/dbmanager.php');
require_once (dirname(__FILE__).'/amazon.php');
require_once (dirname(__FILE__).'/cachemanager.php');
require_once (dirname(__FILE__).'/freeform.php');
require_once (dirname(__FILE__).'/logmanager.php');
require_once (dirname(__FILE__).'/../settings.php');
require_once (dirname(__FILE__).'/feed.php');

require_once 'MDB2.php';

class User {
	private $id;
	private $status;
	private $locale;
	private $session_key = "";
	private $profile_display = Array();
	private $amazon_shared_items = Array();
	private $freeform_shared_items = Array();
	private $requests = Array();
	private $saved_friends_ids = Array();
	private $saved_friends_timestamp = null;
	
	private static $statements_started = false;
	private static $statement_getUser;
	private static $statement_getUserList;
	private static $statement_createUser;
	private static $statement_setStatus;
	private static $statement_setLocale;
	private static $statement_setSessionKey;
	private static $statement_addAmazonSharedItem;
	private static $statement_removeAmazonSharedItem;
	private static $statement_getAmazonSharedItems;
	private static $statement_removeFreeformSharedItem;
	private static $statement_getFreeformSharedItems;
	private static $statement_deleteAmazonSharedItems;
	private static $statement_deleteFreeformSharedItems;
	private static $statement_delete;
	private static $statement_addAmazonRequest;
	private static $statement_addFreeformRequest;
	private static $statement_cancelAmazonRequest;
	private static $statement_acceptAmazonRequest;
	private static $statement_cancelFreeformRequest;
	private static $statement_acceptFreeformRequest;
	private static $statement_setDVDProfileDisplay;
	private static $statement_setBookProfileDisplay;
	private static $statement_setCDProfileDisplay;
	private static $statement_setGameProfileDisplay;
	private static $statement_setOtherProfileDisplay;

	public static function getUser($userid, $self=false, $api_client=null) {
		global $TABLE;
		global $COLUMN;
		global $STATUS;
		global $AMAZON_LOCALE;
		global $ITEM_TYPE_ID;
		
		if (!User::$statements_started) User::prepareStatements();
		LogManager::trace(__CLASS__, "retrieving user with id=".$userid);

		// First try to retrieve the user from the cache
		$ibuser = CacheManager::get("User-".$userid);
		if (!$ibuser) { // If that fails, get the user from the DB
			LogManager::trace(__CLASS__, "Can't find user in the cache, looking in the DB for user with id=".$userid);
			
			$result = User::$statement_getUser->execute($userid);

			if (!$result || PEAR::isError($result) || $result->numRows() != 1) {
				$ibuser = false;
			} else {
				$row = $result->fetchRow();
				$ibuser = new User();
				$ibuser->setId($row[$COLUMN["USER_ID"]]);
				$ibuser->setStatus($row[$COLUMN["STATUS"]], false);
				$ibuser->setLocale($row[$COLUMN["LOCALE"]], false);
				$ibuser->setSessionKey($row[$COLUMN["SESSION_KEY"]], false);
				$ibuser->setProfileDisplay($ITEM_TYPE_ID["DVD"], $row[$COLUMN["QUANTITY_DVD"]], false);
				$ibuser->setProfileDisplay($ITEM_TYPE_ID["BOOK"], $row[$COLUMN["QUANTITY_BOOK"]], false);
				$ibuser->setProfileDisplay($ITEM_TYPE_ID["CD"], $row[$COLUMN["QUANTITY_CD"]], false);
				$ibuser->setProfileDisplay($ITEM_TYPE_ID["VIDEO_GAME"], $row[$COLUMN["QUANTITY_GAME"]], false);
				$ibuser->setProfileDisplay($ITEM_TYPE_ID["OTHER"], $row[$COLUMN["QUANTITY_OTHER"]], false);

				$result2 = User::$statement_getAmazonSharedItems->execute($userid);

				$asi = Array();
				while ($row = $result2->fetchRow()) {
					$asi[] = Array( ITEM_ASIN => $row[$COLUMN["ITEM_ASIN"]], LOCALE => $row[$COLUMN["LOCALE"]], ITEM_TYPE => $row[$COLUMN["ITEM_TYPE"]], STATUS => $row[$COLUMN["STATUS"]], TIMESTAMP => $row[$COLUMN["TIMESTAMP"]], USER_ID => $ibuser->getId(), REQUEST_MESSAGE => $row[$COLUMN["REQUEST_MESSAGE"]], BORROWER_ID => $row[$COLUMN["BORROWER_ID"]]);
				}
				$ibuser->setAmazonSharedItems($asi);
				
				$result = User::$statement_getFreeformSharedItems->execute($userid);

				$fsi = Array();
				while ($row = $result->fetchRow()) {
					$fsi[] = Array( FREEFORM_ID => $row[$COLUMN["FREEFORM_ID"]], TITLE => $row[$COLUMN["TITLE"]], DESCRIPTION => $row[$COLUMN["DESCRIPTION"]], ITEM_TYPE => $row[$COLUMN["ITEM_TYPE"]], STATUS => $row[$COLUMN["STATUS"]], TIMESTAMP => $row[$COLUMN["TIMESTAMP"]], USER_ID => $ibuser->getId(), REQUEST_MESSAGE => $row[$COLUMN["REQUEST_MESSAGE"]], BORROWER_ID => $row[$COLUMN["BORROWER_ID"]]);
				}
				$ibuser->setFreeformSharedItems($fsi);
			}
		} else {
			LogManager::trace(__CLASS__, "Found in the cache user with id=".$userid);
			return $ibuser;
		} 

		// We couldn't find that user id in the database or in the cache, let's create the user entry
		if (!$ibuser) {
			LogManager::trace(__CLASS__, "Can't be found in cache or DB, must create user with id=".$userid);
			$ibuser = new User();
			$ibuser->setId($userid);
			$ibuser->setStatus($STATUS["ACTIVE"], false);
			$ibuser->setProfileDisplay($ITEM_TYPE_ID["DVD"], 5, false);
			$ibuser->setProfileDisplay($ITEM_TYPE_ID["BOOK"], 5, false);
			$ibuser->setProfileDisplay($ITEM_TYPE_ID["CD"], 5, false);
			$ibuser->setProfileDisplay($ITEM_TYPE_ID["VIDEO_GAME"], 5, false);
			$ibuser->setProfileDisplay($ITEM_TYPE_ID["OTHER"], 5, false);
			
			$country = "United States";
			
			if ($api_client != null) {
				$users = $api_client->fql_query("SELECT current_location.country FROM user WHERE uid = ".$userid.")");
				
				if (isset($users[0]))
				{
					$user = $users[0];
					if (isset($user["current_location"])) {
						$current_location = $user["current_location"];
						if (isset($current_location["country"]))
							$country = $current_location["country"];
					}
				}

				$api_client->profile_setFBML($ibuser->generateProfileFBML(), $userid);
			}
			$ibuser->setLocaleFromName($country);

			$result = User::$statement_createUser->execute(array($ibuser->getId(), $STATUS["ACTIVE"], $AMAZON_LOCALE["US"]));
			CacheManager::set("User-".$ibuser->getId(), $ibuser);
			
			return $ibuser;
		} else {
			// Since we just fetched the user from the DB, let's put him/her in the cache
			CacheManager::set("User-".$ibuser->getId(), $ibuser);
			return $ibuser;
		}
	}
	
	public static function getUserList() {
		global $COLUMN;
		
		if (!User::$statements_started) User::prepareStatements();
		$list = Array();
		
		$result = User::$statement_getUserList->execute(1);
		while ($row = $result->fetchRow()) {
			$list []= $row[$COLUMN["USER_ID"]];
		}
		
		return $list;
	}

	public static function recreateDBSchema() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE_WRITE;
		global $COLUMN_TYPE;

		if (!User::$statements_started) User::prepareStatements();
		LogManager::info(__CLASS__, "Recreating the DB schema, this will drop the tables for this class");
		
		/**** TABLE STRUCTURE ****/

		DBManager::dropMasterDBTable("USER");
		DBManager::createMasterDBTable("USER", Array("USER_ID", "STATUS", "LOCALE", "QUANTITY_DVD", "QUANTITY_BOOK", "QUANTITY_CD", "QUANTITY_GAME", "QUANTITY_OTHER", "SESSION_KEY"));
		DBManager::alterMasterDBTablePrimaryKey("USER", Array("USER_ID"));

		DBManager::dropMasterDBTable("AMAZON_SHARED_ITEMS");
		DBManager::createMasterDBTable("AMAZON_SHARED_ITEMS", Array("ITEM_ASIN", "USER_ID", "LOCALE", "ITEM_TYPE", "STATUS", "BORROWER_ID", "REQUEST_MESSAGE", "TIMESTAMP"));
		DBManager::alterMasterDBTablePrimaryKey("AMAZON_SHARED_ITEMS", Array("ITEM_ASIN", "USER_ID"));
				
		DBManager::dropMasterDBTable("FREEFORM_SHARED_ITEMS");
		DBManager::createMasterDBTable("FREEFORM_SHARED_ITEMS", Array("FREEFORM_ID", "USER_ID", "ITEM_TYPE", "TITLE", "DESCRIPTION", "STATUS", "BORROWER_ID", "REQUEST_MESSAGE", "TIMESTAMP"));
	}
	
	public static function deleteUser($userid) {
		if (!User::$statements_started) User::prepareStatements();
	
		LogManager::trace(__CLASS__, "deleting user with id=".$userid);
				
		$result = User::$statement_deleteAmazonSharedItems->execute($userid);
		$result = User::$statement_deleteFreeformSharedItems->execute($userid);
		$result = User::$statement_delete->execute($userid);
		
		try {
		CacheManager::delete("User-".$userid);
		} catch (CacheManagerException $e) {}
		try {
		CacheManager::delete("Feed-".$userid);
		} catch (CacheManagerException $e) {}
	}

	public function saveCache() {
		if (!User::$statements_started) User::prepareStatements();
	
		LogManager::trace(__CLASS__, "updating cache entry of user with id=".$this->id);
		CacheManager::replace("User-".$this->id, $this);
	}

	public function getId() {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->id;
	}

	public function setId($id) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->id = $id;
	}

	public function getStatus() {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->status;
	}

	public function setStatus($newstatus, $persist=true) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->status = $newstatus;
		if ($persist) {
			$this->saveCache();
			$result = User::$statement_setStatus->execute(array($this->id, $this->status));
		}
	}
	
	public function getLocale() {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->locale;
	}

	public function setLocale($newlocale, $persist=true) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->locale = $newlocale;
		if ($persist) {
			$this->saveCache();
			$result = User::$statement_setLocale->execute(array($this->id, $this->locale));
		}
	}
	
	public function getSessionKey() {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->session_key;
	}

	public function setSessionKey($newkey, $persist=true) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->session_key = $newkey;
		if ($persist) {
			$this->saveCache();
			$result = User::$statement_setSessionKey->execute(array($this->id, $this->session_key));
		}
	}
	
	public function setLocaleFromName($countryname) {
		global $AMAZON_LOCALE;
		
		if (!User::$statements_started) User::prepareStatements();
		LogManager::trace(__CLASS__, "setLocaleFromName(".$countryname.") for user with id=".$this->id);
		
		if (strcmp($countryname, "Canada") == 0)
			$this->setLocale($AMAZON_LOCALE["CA"]);
		elseif (strcmp($countryname, "France") == 0 || strcmp($countryname, "Mauritius") == 0 || strcmp($countryname, "Martinique") == 0 || strcmp($countryname, "French Polyniesa") == 0 || strcmp($countryname, "French Guiana") == 0 || strcmp($countryname, "Belgium") == 0 || strcmp($countryname, "Luxembourg") == 0)
			$this->setLocale($AMAZON_LOCALE["FR"]);
		elseif (strcmp($countryname, "England") == 0 || strcmp($countryname, "Scotland") == 0 || strcmp($countryname, "Wales") == 0 || strcmp($countryname, "Ireland") == 0 || strcmp($countryname, "Northern Ireland") == 0)
			$this->setLocale($AMAZON_LOCALE["UK"]);
		elseif (strcmp($countryname, "Germany") == 0 || strcmp($countryname, "Austria") == 0)
			$this->setLocale($AMAZON_LOCALE["DE"]);
		else
			$this->setLocale($AMAZON_LOCALE["US"]);
	}

	public function addAmazonSharedItem($asin, $item_type) {
		global $STATUS;
		global $ITEM_TYPE_ID;

		if (!User::$statements_started) User::prepareStatements();
		$already_shared = false;
		foreach($this->amazon_shared_items as $existing_shared_item)
			if ($existing_shared_item["ITEM_ASIN"] == $asin)
			$already_shared = true;

		if (!$already_shared) {
			$item = Array( ITEM_ASIN => $asin, LOCALE => $this->locale, ITEM_TYPE => $item_type, STATUS => $STATUS["SHARED"], TIMESTAMP => time(), USER_ID => $this->id, REQUEST_MESSAGE => null, BORROWER_ID => null);
			$this->amazon_shared_items []= $item;
			$feed = Feed::getFeed($this->id);
			$feed->addAmazonItem($item);
			
			$this->saveCache();
			$result = User::$statement_addAmazonSharedItem->execute(array($asin, $this->id, $this->locale, $item_type, $STATUS["SHARED"]));
		}
	}
	
	public function removeAmazonSharedItem($asin) {
		if (!User::$statements_started) User::prepareStatements();
	
		$new_selection = Array();
		foreach($this->amazon_shared_items as $item)
			if (strcmp($asin, $item["ITEM_ASIN"]) != 0)
				$new_selection []= $item;
		$this->amazon_shared_items = $new_selection;
		
		/*$filter = create_function('$amazon_item', 'return (strcmp($amazon_item["ITEM_ASIN"],'.$asin.') != 0);');
		$this->amazon_shared_items = array_filter($this->amazon_shared_items, $filter);*/
		$this->saveCache();
		$result = User::$statement_removeAmazonSharedItem->execute(array($asin, $this->id));
	}

	public function getAmazonSharedItems($sorted=false) {
		if (!User::$statements_started) User::prepareStatements();
	
		if ($sorted) {
			$sorted = $this->amazon_shared_items;
			foreach ($sorted as $key => $row) {
				$timestamp[$key] = $row["TIMESTAMP"];
			}
			array_multisort($timestamp, SORT_DESC, $sorted);
			return $sorted;
		}
		return $this->amazon_shared_items;
	}

	public function setAmazonSharedItems($items) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->amazon_shared_items = $items;
	}

	public function addFreeformSharedItem($item_type, $title, $description) {
		global $STATUS;
		global $ITEM_TYPE_ID;
		global $DATABASE_WRITE;
		global $TABLE;
		global $COLUMN;

		if (!User::$statements_started) User::prepareStatements();
		$result = DBManager::queryMasterDB("INSERT INTO ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]."(".$COLUMN["USER_ID"].", ".$COLUMN["ITEM_TYPE"].", ".$COLUMN["TITLE"].", ".$COLUMN["DESCRIPTION"].", ".$COLUMN["STATUS"].") VALUES(".$this->id.", ".$item_type.", '".$title."', '".$description."', ".$STATUS["SHARED"].")");
		$freeform_id = DBManager::insertidWriteDB();
		
		$item = Array( FREEFORM_ID => $freeform_id, ITEM_TYPE => $item_type, TITLE => $title, DESCRIPTION => $description, TIMESTAMP => time(), STATUS => $STATUS["SHARED"], USER_ID => $this->id);
		
		$this->freeform_shared_items []= $item;
		
		$feed = Feed::getFeed($this->id);
		$feed->addFreeformItem($item);
		
		$this->saveCache();
	}

	public function getFreeformSharedItems() {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->freeform_shared_items;
	}

	public function setFreeformSharedItems($items) {
		if (!User::$statements_started) User::prepareStatements();
	
		$this->freeform_shared_items = $items;
	}
	
	public function removeFreeformSharedItem($freeform_id) {
		if (!User::$statements_started) User::prepareStatements();
	
		$new_selection = Array();
		foreach($this->freeform_shared_items as $item)
			if ($item["FREEFORM_ID"] != $freeform_id)
				$new_selection []= $item;
				
		$this->freeform_shared_items = $new_selection;
		$this->saveCache();
		
		$result = User::$statement_removeFreeformSharedItem->execute($freeform_id);
	}
	
	private function renderProfileItems($item_type, $title) {
		if (!User::$statements_started) User::prepareStatements();
	
		$sorted_items = $this->getSharedTypedItems($item_type, true);
		if (count($sorted_items) == 0)
			return "";
			
		$quantity = $this->getProfileDisplay($item_type);
		
		if ($quantity == 0)
			return "";
			
		$result = "<div style=\"clear:both; border-bottom:1px solid #ccc;\"><h2>".$title."</h2></div>";
		$textlinks = "";
		$imglinks = "";
		for ($i = 0; $i < $quantity; $i++) {
			if (isset($sorted_items[$i])) {
				$item_to_display = $sorted_items[$i];
				if (isset($item_to_display["ITEM_ASIN"]))
					$imglinks .= Amazon::renderBorrowLink($item_to_display["ITEM_ASIN"], $item_to_display["LOCALE"], $item_to_display["ITEM_TYPE"], $this->id)." &nbsp;&nbsp;";
				else {
					$textlinks .= Freeform::renderBorrowLink($item_to_display["FREEFORM_ID"], $item_to_display["ITEM_TYPE"], $this->id, $item_to_display["TITLE"])."<br />";
				}
			}
		}
		$result .= $textlinks."<br />";
		$result .= $imglinks;
		$result .= "<br /><br />";
		
		return $result;
	}
	
	public function generateProfileFBML() {
		global $ITEM_TYPE_ID;
		
		if (!User::$statements_started) User::prepareStatements();
		$subtitle = "<fb:subtitle>Below are the latest items you can borrow from me</fb:subtitle>";
		
		$result = $subtitle;
		$result .= $this->renderProfileItems($ITEM_TYPE_ID["DVD"], "DVDs");
		$result .= $this->renderProfileItems($ITEM_TYPE_ID["BOOK"], "Books");
		$result .= $this->renderProfileItems($ITEM_TYPE_ID["CD"], "CDs");
		$result .= $this->renderProfileItems($ITEM_TYPE_ID["VIDEO_GAME"], "Video games");
		$result .= $this->renderProfileItems($ITEM_TYPE_ID["OTHER"], "Other", false);
		
		if (strcmp($result, $subtitle) != 0) $result = substr($result, 0, -12); // Hack to get rid of the final <br>s
		
		return $result;
	}

	public function getSharedTypedItems($item_type, $sorted=false, $ignore_borrowed=true) {
		global $STATUS;

		if (!User::$statements_started) User::prepareStatements();
		$newlist = Array();
		foreach($this->freeform_shared_items as $freeform_item)
			if ($freeform_item["ITEM_TYPE"] == $item_type)
			{
				if ($ignore_borrowed && $freeform_item["STATUS"] == $STATUS["SHARED"])
					$newlist [] = $freeform_item;
				elseif (!$ignore_borrowed)
					$newlist [] = $freeform_item;
			}
		foreach($this->amazon_shared_items as $amazon_item)
			if ($amazon_item["ITEM_TYPE"] == $item_type)
			{
				if ($ignore_borrowed && $amazon_item["STATUS"] == $STATUS["SHARED"])
					$newlist [] = $amazon_item;
				elseif (!$ignore_borrowed)
					$newlist [] = $amazon_item;
			}
			
		if ($sorted) {
			$sorted = $newlist;
			$timestamp = Array();
			foreach ($sorted as $key => $row) {
				$timestamp[$key] = $row["TIMESTAMP"];
			}
			array_multisort($timestamp, SORT_DESC, $sorted);
			return $sorted;
		}
			
		return $newlist;
	}
	
	public function getFriendsIDs($api_client) {
		if (!User::$statements_started) User::prepareStatements();
	
		if ($this->saved_friends_timestamp == null || time() > ($this->saved_friends_timestamp + 900)) { // 15 minutes
		
			$results = Array();
			$fql_result = $api_client->fql_query("SELECT uid FROM user WHERE has_added_app = 1 AND uid IN (SELECT uid2 FROM friend WHERE uid1 = ".$this->id.")");
		
			foreach($fql_result as $result)
				$results []= $result["uid"];
				
			$this->saved_friends_ids = $results;
			$this->saved_friends_timestamp = time();
			$this->saveCache();
			
			return $results;
		} else return $this->saved_friends_ids;
	}
	
	public function hasAddedApp($api_client) {
		if (!User::$statements_started) User::prepareStatements();
	
		$fql_result = $api_client->fql_query("SELECT has_added_app FROM user WHERE uid = ".$this->id);
		
		if (isset($fql_result[0])) {
			return ($fql_result[0]["has_added_app"] == 1);
		} else return false;
	}
	
	public function getFriendsTypedItems($item_type, $api_client, $sorted=false) {
		if (!User::$statements_started) User::prepareStatements();
	
		$friendsids = $this->getFriendsIDs($api_client);
		$result = Array();
		foreach($friendsids as $friendid) {
			$friend = User::getUser($friendid);
			$result = array_merge($result, $friend->getSharedTypedItems($item_type, false, false));
		}
		
		if ($sorted) {
			$sorted = $result;
			$timestamp = Array();
			foreach ($sorted as $key => $row) {
				$timestamp[$key] = $row["TIMESTAMP"];
			}
			array_multisort($timestamp, SORT_DESC, $sorted);
			return $sorted;
		}
			
		return $result;
	}
	
	public function getFriendsSharedCount($api_client) {
		if (!User::$statements_started) User::prepareStatements();
	
		$total_shared = 0;
		
		$friendsids = $this->getFriendsIDs($api_client);

		foreach($friendsids as $friendid) {
			$friend = User::getUser($friendid);
			$total_shared += count($friend->getFreeformSharedItems()) + count($friend->getAmazonSharedItems());
		}
		
		return $total_shared;
	}
	
	public function setProfileDisplay($item_type, $quantity, $persist=true) {
		global $ITEM_TYPE_ID;
	
		if (!User::$statements_started) User::prepareStatements();
	
		$this->profile_display[$item_type] = $quantity;
		
		if ($persist) {
			$this->saveCache();
			if ($item_type == $ITEM_TYPE_ID["DVD"]) {
				$result = User::$statement_setDVDProfileDisplay->execute(array($quantity, $this->id));
			} elseif ($item_type == $ITEM_TYPE_ID["BOOK"]) {
				$result = User::$statement_setBookProfileDisplay->execute(array($quantity, $this->id));
			} elseif ($item_type == $ITEM_TYPE_ID["CD"]) {
				$result = User::$statement_setCDProfileDisplay->execute(array($quantity, $this->id));
			} elseif ($item_type == $ITEM_TYPE_ID["VIDEO_GAME"]) {
				$result = User::$statement_setGameProfileDisplay->execute(array($quantity, $this->id));
			} elseif ($item_type == $ITEM_TYPE_ID["OTHER"]) {
				$result = User::$statement_setOtherProfileDisplay->execute(array($quantity, $this->id));
			}
		}
	}
	
	public function getProfileDisplay($item_type) {
		if (!User::$statements_started) User::prepareStatements();
	
		return $this->profile_display[$item_type];
	}
	
	public function requestAmazonItem($item, $message, $borrowerid) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->amazon_shared_items); $i++) {
			if ($this->amazon_shared_items[$i]["ITEM_ASIN"] == $item["ITEM_ASIN"])
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		if ($this->amazon_shared_items[$the_item]["STATUS"] != $STATUS["SHARED"]) return false;
		
		$this->amazon_shared_items[$the_item]["STATUS"] = $STATUS["REQUESTED"];
		$this->amazon_shared_items[$the_item]["REQUEST_MESSAGE"] = $message;
		$this->amazon_shared_items[$the_item]["BORROWER_ID"] = $borrowerid;
		$this->saveCache();

		$result = User::$statement_addAmazonRequest->execute(array($message, $borrowerid, $this->id, $item["ITEM_ASIN"]));
		
		return true;
	}
	
	public function cancelAmazonRequest($item_asin) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->amazon_shared_items); $i++) {
			if ($this->amazon_shared_items[$i]["ITEM_ASIN"] == $item_asin)
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		$this->amazon_shared_items[$the_item]["STATUS"] = $STATUS["SHARED"];
		$this->amazon_shared_items[$the_item]["REQUEST_MESSAGE"] = null;
		$this->amazon_shared_items[$the_item]["BORROWER_ID"] = null;
		$this->saveCache();
		
		$result = User::$statement_cancelAmazonRequest->execute(array($this->id, $item_asin));
		
		return true;
	}
	
	public function acceptAmazonRequest($item_asin) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->amazon_shared_items); $i++) {
			if ($this->amazon_shared_items[$i]["ITEM_ASIN"] == $item_asin)
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		$this->amazon_shared_items[$the_item]["STATUS"] = $STATUS["BORROWED"];
		$this->amazon_shared_items[$the_item]["REQUEST_MESSAGE"] = null;
		$this->saveCache();
		
		$result = User::$statement_acceptAmazonRequest->execute(array($this->id, $item_asin));
		
		return true;
	}
	
	public function requestFreeformItem($item, $message, $borrowerid) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->freeform_shared_items); $i++) {
			if ($this->freeform_shared_items[$i]["FREEFORM_ID"] == $item["FREEFORM_ID"])
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		if ($this->freeform_shared_items[$the_item]["STATUS"] != $STATUS["SHARED"]) return false;
		
		$this->freeform_shared_items[$the_item]["STATUS"] = $STATUS["REQUESTED"];
		$this->freeform_shared_items[$the_item]["REQUEST_MESSAGE"] = $message;
		$this->freeform_shared_items[$the_item]["BORROWER_ID"] = $borrowerid;
		$this->saveCache();

		$result = User::$statement_addFreeformRequest->execute(array($message, $borrowerid, $this->id, $item["FREEFORM_ID"]));
		
		return true;
	}
	
	public function cancelFreeformRequest($freeform_id) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->freeform_shared_items); $i++) {
			if ($this->freeform_shared_items[$i]["FREEFORM_ID"] == $freeform_id)
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		$this->freeform_shared_items[$the_item]["STATUS"] = $STATUS["SHARED"];
		$this->freeform_shared_items[$the_item]["REQUEST_MESSAGE"] = null;
		$this->freeform_shared_items[$the_item]["BORROWER_ID"] = null;
		$this->saveCache();
		
		$result = User::$statement_cancelFreeformRequest->execute(array($this->id, $freeform_id));
		
		return true;
	}
	
	public function acceptFreeformRequest($freeform_id) {
		global $STATUS;
		
		if (!User::$statements_started) User::prepareStatements();
		$the_item = -1;
		
		for ($i = 0; $i < count($this->freeform_shared_items); $i++) {
			if ($this->freeform_shared_items[$i]["FREEFORM_ID"] == $freeform_id)
				$the_item = $i;
		}
		
		if ($the_item == -1) return false;
		
		$this->freeform_shared_items[$the_item]["STATUS"] = $STATUS["BORROWED"];
		$this->freeform_shared_items[$the_item]["REQUEST_MESSAGE"] = null;
		$this->saveCache();
		
		$result = User::$statement_acceptFreeformRequest->execute(array($this->id, $freeform_id));
		
		return true;
	}
	
	private static function prepareStatements() {
		global $TABLE;
		global $COLUMN;
		global $DATABASE_WRITE;
		global $COLUMN_TYPE;
		global $STATUS;

		LogManager::trace(__CLASS__, "Preparing DB statements for this class");
		
		User::$statement_getUser = DBManager::prepareReadMasterDB( 
				"SELECT ".$COLUMN["USER_ID"].", ".$COLUMN["STATUS"].", ".$COLUMN["LOCALE"].", ".$COLUMN["QUANTITY_DVD"].", ".$COLUMN["QUANTITY_BOOK"].", ".$COLUMN["QUANTITY_CD"].", ".$COLUMN["QUANTITY_GAME"].", ".$COLUMN["QUANTITY_OTHER"].", ".$COLUMN["SESSION_KEY"]
				." FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]
				." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'));
						
		User::$statement_getUserList = DBManager::prepareReadMasterDB( 
				"SELECT ".$COLUMN["USER_ID"]." FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." WHERE ?"
						, array('integer'));
								
		User::$statement_createUser = DBManager::prepareWriteMasterDB( 
				"INSERT INTO ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." (".$COLUMN["USER_ID"].", ".$COLUMN["STATUS"].", ".$COLUMN["LOCALE"].") VALUES(?, ?, ?)"
						, array('integer', 'integer', 'integer'));
						
		User::$statement_setStatus = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["STATUS"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setLocale = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["LOCALE"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));

		User::$statement_setSessionKey = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["SESSION_KEY"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('text', 'integer'));
						
		User::$statement_addAmazonSharedItem = DBManager::prepareWriteMasterDB( 
				"INSERT INTO ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." VALUES(?, ?, ?, ?, ?, NULL, NULL, NOW())"
						, array('text', 'integer', 'integer', 'integer', 'integer'));
		
		User::$statement_removeAmazonSharedItem = DBManager::prepareWriteMasterDB( 
				"DELETE FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." WHERE ".$COLUMN["ITEM_ASIN"]." = ? AND ".$COLUMN["USER_ID"]." = ?"
						, array('text', 'integer'));

		User::$statement_getAmazonSharedItems = DBManager::prepareReadMasterDB( 
				"SELECT ".$COLUMN["ITEM_ASIN"].", ".$COLUMN["LOCALE"].", ".$COLUMN["ITEM_TYPE"].", ".$COLUMN["STATUS"].", ".$COLUMN["REQUEST_MESSAGE"].", ".$COLUMN["BORROWER_ID"].", UNIX_TIMESTAMP(".$COLUMN["TIMESTAMP"].") AS ".$COLUMN["TIMESTAMP"]." FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'), array('text', 'integer', 'integer', 'integer', 'text', 'integer', 'timestamp'));
						
		User::$statement_removeFreeformSharedItem = DBManager::prepareWriteMasterDB( 
				"DELETE FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." WHERE ".$COLUMN["FREEFORM_ID"]." = ?"
						, array('integer'));
						
		User::$statement_getFreeformSharedItems = DBManager::prepareReadMasterDB( 
				"SELECT ".$COLUMN["FREEFORM_ID"].", ".$COLUMN["ITEM_TYPE"].", ".$COLUMN["TITLE"].", ".$COLUMN["DESCRIPTION"].", ".$COLUMN["STATUS"].", ".$COLUMN["REQUEST_MESSAGE"].", ".$COLUMN["BORROWER_ID"].", UNIX_TIMESTAMP(".$COLUMN["TIMESTAMP"].") AS ".$COLUMN["TIMESTAMP"]." FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'));
		
		User::$statement_deleteAmazonSharedItems = DBManager::prepareWriteMasterDB( 
				"DELETE FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'));
						
		User::$statement_deleteFreeformSharedItems = DBManager::prepareWriteMasterDB( 
				"DELETE FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'));
						
		User::$statement_delete = DBManager::prepareWriteMasterDB( 
				"DELETE FROM ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer'));
					
		User::$statement_addAmazonRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = ?, ".$COLUMN["BORROWER_ID"]." = ?, ".$COLUMN["STATUS"]." = ".$STATUS["REQUESTED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["ITEM_ASIN"]." = ?"
						, array('text', 'integer', 'integer', 'text'));
							
		User::$statement_addFreeformRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = ?, ".$COLUMN["BORROWER_ID"]." = ?, ".$COLUMN["STATUS"]." = ".$STATUS["REQUESTED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["FREEFORM_ID"]." = ?"
						, array('text', 'integer', 'integer', 'integer'));
						
		User::$statement_cancelAmazonRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = NULL, ".$COLUMN["BORROWER_ID"]." = NULL, ".$COLUMN["STATUS"]." = ".$STATUS["SHARED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["ITEM_ASIN"]." = ?"
						, array('integer', 'text'));
						
		User::$statement_acceptAmazonRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["AMAZON_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = NULL, ".$COLUMN["STATUS"]." = ".$STATUS["BORROWED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["ITEM_ASIN"]." = ?"
						, array('integer', 'text'));
						
		User::$statement_cancelFreeformRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = NULL, ".$COLUMN["BORROWER_ID"]." = NULL, ".$COLUMN["STATUS"]." = ".$STATUS["SHARED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["FREEFORM_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_acceptFreeformRequest = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["FREEFORM_SHARED_ITEMS"]." SET ".$COLUMN["REQUEST_MESSAGE"]." = NULL, ".$COLUMN["STATUS"]." = ".$STATUS["BORROWED"]." WHERE ".$COLUMN["USER_ID"]." = ? AND ".$COLUMN["FREEFORM_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setDVDProfileDisplay = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["QUANTITY_DVD"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setBookProfileDisplay = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["QUANTITY_BOOK"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setCDProfileDisplay = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["QUANTITY_CD"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setGameProfileDisplay = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["QUANTITY_GAME"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
						
		User::$statement_setOtherProfileDisplay = DBManager::prepareWriteMasterDB( 
				"UPDATE ".$DATABASE_WRITE["PREFIX"].$TABLE["USER"]." SET ".$COLUMN["QUANTITY_OTHER"]." = ? WHERE ".$COLUMN["USER_ID"]." = ?"
						, array('integer', 'integer'));
		
		User::$statements_started = true;
	}
}

?>