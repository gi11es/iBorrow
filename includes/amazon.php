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
require_once (dirname(__FILE__).'/urlmanager.php');
require_once (dirname(__FILE__).'/cachemanager.php');
require_once (dirname(__FILE__).'/user.php');

class Amazon {

	public static function renderBuyLink($asin, $locale, $text) {
		global $AMAZON_LOCALE_URL;
		
		return "<a target=_blank href=\"http://www.".$AMAZON_LOCALE_URL[$locale]."/gp/product/".$asin."?ie=UTF8&tag=darum-20&linkCode=as2&camp=1789&creative=9325&creativeASIN=".$asin."\">".$text."</a><img src=\"http://www.assoc-amazon.com/e/ir?t=darum-20&l=as2&o=1&a=".$asin."\" width=\"1\" height=\"1\" border=\"0\" alt=\"\" style=\"border:none !important; margin:0px !important;\" />";
	}

	private static function renderThreeAttributes($array, $start_text, $end_text=false) {
		$element1 = $array[0];
		$element2 = $array[1];
		$element3 = $array[2];

		$result = false;

		if ($element1) {
			$result = $start_text.$element1;
			if ($element2) $result .= ", ".$element2;
			if ($element3) $result .= ", ".$element3;
		}

		if ($end_text && isset($array[3])) $result .= $end_text;

		return $result;
	}

	public static function renderExtraItemInformation($Item, $search_type) {
		global $ITEM_TYPE_ID;

		if ($search_type == $ITEM_TYPE_ID["DVD"]) {
			$actorsstring = Amazon::renderThreeAttributes($Item->ItemAttributes->Actor, "With ");
			$directorstring = ($Item->ItemAttributes->Director?"Directed by ".$Item->ItemAttributes->Director.". ":"");

			return $directorstring.$actorsstring;
		} 
		elseif ($search_type == $ITEM_TYPE_ID["BOOK"]) {
			$authorstring = Amazon::renderThreeAttributes($Item->ItemAttributes->Author, "Written by ", " et al.");
			return ($authorstring?$authorstring:"Author unknown");
		}
		elseif ($search_type == $ITEM_TYPE_ID["VIDEO_GAME"]) {
			return "Platform: ".$Item->ItemAttributes->Format." ".$Item->ItemAttributes->Platform;
		}
		elseif ($search_type == $ITEM_TYPE_ID["CD"]) {
			$artistsstring = Amazon::renderThreeAttributes($Item->ItemAttributes->Artist, "By ", " et al.");
			if ($artistsstring) return $artistsstring;
			$creatorsstring = Amazon::renderThreeAttributes($Item->ItemAttributes->Creator, "By ", " et al.");
			if ($creatorsstring) return $creatorsstring;
			return "By Various artists";
		}
	}

	public static function renderItemSearchEntry($Item, $search_type, $firstresult, $user) {
		global $PAGE;
		global $ITEM_TYPE_ID;

		if ($search_type == $ITEM_TYPE_ID["DVD"]) {
			$button_text = "Make this DVD available to your friends";
			$buy_text = "Details about this DVD on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["BOOK"]) {
			$button_text = "Make this book available to your friends";
			$buy_text = "Details about this book on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["CD"]) {
			$button_text = "Make this CD available to your friends";
			$buy_text = "Details about this CD on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["VIDEO_GAME"]) {
			$button_text = "Make this video game available to your friends";
			$buy_text = "Details about this video game on Amazon";
		}

		$title = $Item->ItemAttributes->Title;
		$asin = $Item->ASIN;
		
		$amazonitems = $user->getAmazonSharedItems();
		foreach ($amazonitems as $amazonitem)
			if (strcmp($amazonitem["ITEM_ASIN"], $asin) == 0 && $amazonitem["LOCALE"] == $user->getLocale())
				return Amazon::renderSharedItem($asin, $amazonitem["LOCALE"], $search_type, $amazonitem["STATUS"], $firstresult, true);

		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 70) {
			$title = trim(substr($title, 0, 68))."...";
		}

		$actionstring = "<br><br><form method=\"post\" onsubmit=\"do_ajax('".$PAGE['SHARED_RESULTS']."?ITEM_TYPE=".urlencode($search_type)."&ITEM_ASIN=".urlencode($asin)."', 'sharedresults', 'searchresults'); clearSearchResults(document); return false;\"> <input value=\"".$button_text."\" type=\"submit\" class=\"inputbutton\" /> ".Amazon::renderBuyLink($asin, $user->getLocale(), $buy_text)."</form>";

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$actionstring."</div></td>";
		$result.= "</tr>";

		return $result;
	}
	
	public static function ItemLookup($asin, $locale, $search_type) {
		global $ITEM_TYPE_ID;
		global $AMAZON_LOCALE_AWS_URL;
		
		if ($search_type == $ITEM_TYPE_ID["VIDEO_GAME"])
			$params["ResponseGroup"] = "Images,Small,ItemAttributes";
		else
			$params["ResponseGroup"] = "Images,Small";
			
		$params["Service"] = "AWSECommerceService";
		$params["AWSAccessKeyId"] = "02VMCFHJBGRBB84TA3R2";
		$params["Operation"] = "ItemLookup";
		$params["ContentType"] = "text%2Fxml";
		$params["ItemId"] = $asin;
		
		$serializedkey = "ItemLookup-".$locale."-".$asin;
		
		$xmlresult = CacheManager::get($serializedkey);
		
		if (!$xmlresult) {
			$xmlresult = URLManager::getURL("http://ecs.".$AMAZON_LOCALE_AWS_URL[$locale]."/onca/xml", $params);
			CacheManager::set($serializedkey, $xmlresult);
		}
		$xml = new SimpleXMLElement($xmlresult);		
		return $xml->Items->Item;		
	}
	
	public static function renderBorrowLink($asin, $locale, $search_type, $userid) {
		global $PAGE;
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);
		return "<a href=\"".$PAGE["BORROW"]."?FRIEND_ID=".$userid."&ITEM_ASIN=".$asin."&LOCALE=".$locale."&SEARCH_TYPE=".$search_type."\" title=\"".$Item->ItemAttributes->Title."\"><img src=\"".$Item->SmallImage->URL."\" alt=\"".$Item->ItemAttributes->Title."\" /></a>";
	}
	
	public static function renderSharedItem($asin, $locale, $search_type, $status, $firstresult, $stress_sharing=false) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);
		
		$title = $Item->ItemAttributes->Title;
		
		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 70) {
			$title = trim(substr($title, 0, 68))."...";
		}

		$actionstring = ($stress_sharing?"<br><div class=\"already_shared clearfix\">This item is already in your shared list</div>":"<br>")."<br><form method=\"post\" onsubmit=\"do_ajax('".$PAGE['SHARED_RESULTS']."?REMOVE=true&ITEM_TYPE=".urlencode($search_type)."&ITEM_ASIN=".urlencode($asin)."', 'sharedresults', 'searchresults'); return false;\"> <input value=\"Remove from your shared items\" type=\"submit\" class=\"inputbutton\" /></form>";

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$actionstring."</div></td>";
		$result.= "</tr>";

		return $result;
	}
	
	public static function renderFriendsItem($friendsid, $asin, $locale, $search_type, $status, $borrower_id, $firstresult) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		global $STATUS;
		
		if ($search_type == $ITEM_TYPE_ID["DVD"]) {
			$button_text = "Borrow this DVD";
			$buy_text = "Details about this DVD on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["BOOK"]) {
			$button_text = "Borrow this book";
			$buy_text = "Details about this book on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["CD"]) {
			$button_text = "Borrow this CD";
			$buy_text = "Details about this CD on Amazon";
		}
		elseif ($search_type == $ITEM_TYPE_ID["VIDEO_GAME"]) {
			$button_text = "Borrow this video game";
			$buy_text = "Details about this video game on Amazon";
		}
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);

		$title = $Item->ItemAttributes->Title;
		
		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 62) {
			$title = trim(substr($title, 0, 60))."...";
		}

		if ($status == $STATUS["REQUESTED"]) {
			$sharedstring = "<br /><i>Requested by <b><fb:name uid=\"".$borrower_id."\" firstnameonly=\"true\"/></b>, waiting for an answer from <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i>";
			$actionstring = "";
		} else {
			$sharedstring = "<br /><i>Shared by <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i>";
			$actionstring = "<br><form method=\"post\" action=".$PAGE['BORROW']."> <input type=\"hidden\" name=\"FRIEND_ID\" value=\"".$friendsid."\"/> <input type=\"hidden\" name=\"ITEM_ASIN\" value=\"".$asin."\"/> <input type=\"hidden\" name=\"LOCALE\" value=\"".$locale."\"/> <input type=\"hidden\" name=\"SEARCH_TYPE\" value=\"".$search_type."\"/> <input value=\"".$button_text."\" type=\"submit\" class=\"inputbutton\" /> ".Amazon::renderBuyLink($asin, $locale, $buy_text)."</form>";
		}

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$sharedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderRequestedItem($friendsid, $asin, $locale, $search_type, $status, $firstresult, $request_message) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);

		$title = $Item->ItemAttributes->Title;
		
		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 62) {
			$title = trim(substr($title, 0, 60))."...";
		}
		
		$requestedstring = "<br /><i>Requested by <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i><br /><br />".htmlentities(stripslashes($request_message));

		$actionstring = "<br /><br /><form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['REQUESTS']."?fb_sig_user=".urlencode($_REQUEST["fb_sig_user"])."&ACCEPT=Accept&BORROWER_ID=".urlencode($friendsid)."&TITLE=".urlencode($title)."&ITEM_ASIN=".urlencode($asin)."&LOCALE=".urlencode($locale)."&SEARCH_TYPE=".urlencode($search_type)."', 'requests', null); do_ajax('".$PAGE['BORROWED']."', 'borrowed', null); return false;\"> <input name=\"ACCEPT\" value=\"Accept\" type=\"submit\" class=\"inputbutton\" /> </form>
		
		<form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['REQUESTS']."?fb_sig_user=".urlencode($_REQUEST["fb_sig_user"])."&DECLINE=Decline&BORROWER_ID=".urlencode($friendsid)."&TITLE=".urlencode($title)."&ITEM_ASIN=".urlencode($asin)."&LOCALE=".urlencode($locale)."&SEARCH_TYPE=".urlencode($search_type)."', 'requests', null); return false;\"> <input name=\"DECLINE\" value=\"Decline\" type=\"submit\" class=\"inputbutton\" /> </form>";

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$requestedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderBorrowedItem($friendsid, $asin, $locale, $search_type, $status, $firstresult, $request_message) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);

		$title = $Item->ItemAttributes->Title;
		
		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 62) {
			$title = trim(substr($title, 0, 60))."...";
		}
		
		$requestedstring = "<br /><i>Borrowed by <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i><br /><br />".htmlentities(stripslashes($request_message));

		$actionstring = "<br /><br /><form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['BORROWED']."?RETURNED=true&BORROWER_ID=".urlencode($friendsid)."&TITLE=".urlencode($title)."&ITEM_ASIN=".urlencode($asin)."&LOCALE=".urlencode($locale)."&SEARCH_TYPE".urlencode($search_type)."', 'borrowed', null); return false;\"> <input value=\"This item was returned to me\" name=\"RETURNED\" type=\"submit\" class=\"inputbutton\" /></form>";

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$requestedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderBorrowItem($friendsid, $asin, $locale, $search_type, $unavailable=false, $confirmation=false) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		
		$Item = Amazon::ItemLookup($asin, $locale, $search_type);

		$title = $Item->ItemAttributes->Title;
		
		$result= "<tr>";
		if (isset($Item->SmallImage->URL)) $result.= "<td class=\"image\"><div class=\"list_itemfirst clearfix\"> <img src=\"".$Item->SmallImage->URL."\" /> </div></td>";
		else $result.= "<td class=\"image\"><div class=\"list_itemfirst clearfix\"></div></td>";

		if (strlen($title) > 62) {
			$title = trim(substr($title, 0, 60))."...";
		}

		$actionstring = "<br /><br /><form method=\"post\" action=".$PAGE['BORROW']."> <input type=\"hidden\" name=\"FRIEND_ID\" value=\"".$friendsid."\"/> <input type=\"hidden\" name=\"ITEM_ASIN\" value=\"".$asin."\"/>  <input type=\"hidden\" name=\"LOCALE\" value=\"".$locale."\"/> <input type=\"hidden\" name=\"SEARCH_TYPE\" value=\"".$search_type."\"/> <input type=\"hidden\" name=\"BORROW\" value=\"true\"/> <b>Please enter details about your request (e.g. when to meet to get the item):</b><br /> <textarea name=\"borrowal_description\" cols=60 rows=3 /> <br /> <input value=\"Send request to your friend\" type=\"submit\" class=\"inputbutton\" /></form>";
		
		if ($confirmation)
			$actionstring = "<br /><br /><b>Your request was successfully sent to <fb:name firstnameonly=\"true\" uid=\"".$friendsid."\"/>.</b>";
			
		if ($unavailable)
			$actionstring = "<br /><br /><b>Unfortunately this item has become unavailable and cannot be borrowed at this time.</b>";

		$result.= "<td class=\"title\"><div class=\"list_itemfirst clearfix\"><h2>".$title."</h2>".Amazon::renderExtraItemInformation($Item, $search_type).$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_itemfirst clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}

}