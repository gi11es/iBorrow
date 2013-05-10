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

header('Content-Type: text/html; charset=utf-8');

$start_time = microtime(true);

require_once (dirname(__FILE__).'/includes/constants.php');
require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/includes/amazon.php');
require_once (dirname(__FILE__).'/includes/freeform.php');
require_once (dirname(__FILE__).'/includes/analytics.php');
require_once (dirname(__FILE__).'/includes/uihelper.php');
require_once (dirname(__FILE__).'/../client/facebook.php');
require_once (dirname(__FILE__).'/settings.php');

include $TEMPLATE["SEARCH_STYLE"];

$facebook = new Facebook($api_key, $secret);
$facebook->require_frame();
$userid = $facebook->require_install();
$user = User::getUser($userid);

if (isset($_REQUEST["FRIEND_ID"]) && $_REQUEST["FRIEND_ID"] == $userid) {
	echo "<fb:redirect url=\"".$PAGE['MY_ITEMS']."\" />"; // If clicking on your own link, redirect to your items
} elseif (isset($_REQUEST["FRIEND_ID"])) {
	echo UIHelper::RenderMenu($PAGE_CODE['INDEX'], $user, $facebook->api_client);
	
	if (isset($_REQUEST["ITEM_ASIN"]) && isset($_REQUEST["LOCALE"]) && isset($_REQUEST["SEARCH_TYPE"])) {
		// We need to check first if the item is available in case it changed since the previous page
		
		$friend = User::getUser($_REQUEST["FRIEND_ID"]);
		$friend_shared_items = $friend->getAmazonSharedItems();
		$the_item = null;
		foreach ($friend_shared_items as $friend_item) {
			if ($friend_item["ITEM_ASIN"] == $_REQUEST["ITEM_ASIN"])
				$the_item = $friend_item;
		}
		
		if ($the_item == null || $the_item["STATUS"] != $STATUS["SHARED"]) {
			// Display error message because this item is not available for borrowal
			// TODO: increase variety of messages depending on the situation
			renderItem($_REQUEST["FRIEND_ID"], $_REQUEST["ITEM_ASIN"], $_REQUEST["LOCALE"], $_REQUEST["SEARCH_TYPE"], true);
		} else {
		
			if (isset($_REQUEST["BORROW"]) && isset($_REQUEST["borrowal_description"]) && strcmp($_REQUEST["BORROW"], "true") == 0) {
				// This is the actual borrowal request
				$success = $friend->requestAmazonItem($the_item, utf8_decode($_REQUEST["borrowal_description"]), $userid);
				
				$facebook->api_client->notifications_send($friend->getId(), "wants to <a href=\"".$PAGE["INDEX"]."\">borrow an item from you</a>".(strcmp($_REQUEST["borrowal_description"], "") == 0?"":": ".htmlentities(utf8_decode($_REQUEST["borrowal_description"]))));
				
				$session_key = $friend->getSessionKey();				
				$facebook->set_user($friend->getId(), $session_key);
				$facebook->api_client->profile_setFBML('', $friend->getId(), $friend->generateProfileFBML());
				
				$session_key = $user->getSessionKey();				
				$facebook->set_user($userid, $session_key);
				
				if (!$success)
					renderItem($_REQUEST["FRIEND_ID"], $_REQUEST["ITEM_ASIN"], $_REQUEST["LOCALE"], $_REQUEST["SEARCH_TYPE"], true);
				else
					renderItem($_REQUEST["FRIEND_ID"], $_REQUEST["ITEM_ASIN"], $_REQUEST["LOCALE"], $_REQUEST["SEARCH_TYPE"], false, true);
			} else {
				// This is where the user enters a message to borrow the item
				renderItem($_REQUEST["FRIEND_ID"], $_REQUEST["ITEM_ASIN"], $_REQUEST["LOCALE"], $_REQUEST["SEARCH_TYPE"]);
			}
		}
	} elseif(isset($_REQUEST["FREEFORM_ID"])) {
		$friend = User::getUser($_REQUEST["FRIEND_ID"]);
		$friend_shared_items = $friend->getFreeformSharedItems();
		$the_item = null;
		
		foreach ($friend_shared_items as $friend_item) {
			if ($friend_item["FREEFORM_ID"] == $_REQUEST["FREEFORM_ID"])
				$the_item = $friend_item;
		}
		
		if ($the_item == null || $the_item["STATUS"] != $STATUS["SHARED"]) {
			// Display error message because this item is not available for borrowal
			// TODO: increase variety of messages depending on the situation
			renderFreeformItem($_REQUEST["FRIEND_ID"], $_REQUEST["FREEFORM_ID"], $_REQUEST["SEARCH_TYPE"], true);
		} else {
			if (isset($_REQUEST["BORROW"]) && isset($_REQUEST["borrowal_description"])  && strcmp($_REQUEST["BORROW"], "true") == 0) {
				// This is the actual borrowal request
				$success = $friend->requestFreeformItem($the_item, utf8_decode($_REQUEST["borrowal_description"]), $userid);
				
				$facebook->api_client->notifications_send($friend->getId(), "wants to <a href=\"".$PAGE["INDEX"]."\">borrow an item from you</a>".(strcmp($_REQUEST["borrowal_description"], "") == 0?"":": ".htmlentities(utf8_decode($_REQUEST["borrowal_description"]))));
				
				$session_key = $friend->getSessionKey();				
				$facebook->set_user($friend->getId(), $session_key);
				$facebook->api_client->profile_setFBML('', $friend->getId(), $friend->generateProfileFBML());
				
				$session_key = $user->getSessionKey();				
				$facebook->set_user($userid, $session_key);
				if (!$success)
					renderFreeformItem($_REQUEST["FRIEND_ID"], $_REQUEST["FREEFORM_ID"], $_REQUEST["SEARCH_TYPE"], true);
				else
					renderFreeformItem($_REQUEST["FRIEND_ID"], $_REQUEST["FREEFORM_ID"], $_REQUEST["SEARCH_TYPE"], false, true);

			} else {
				// This is where the user enters a message to borrow the item
				renderFreeformItem($_REQUEST["FRIEND_ID"], $_REQUEST["FREEFORM_ID"], $_REQUEST["SEARCH_TYPE"]);
			}
		}
	}

    //echo "Page rendered in ".(microtime(true) - $start_time)." seconds."; ?>
	</div>

	<?php
} else echo "<fb:redirect url=\"".$PAGE['THEIR_ITEMS']."\" />"; // If incomplete $_REQUEST, redirect to your friends' items

function renderFreeformItem($friendid, $freeform_id, $search_type, $unavailable=false, $confirmation=false) {
	global $PAGE;
	
?>
<br />
		<table class="lists" cellspacing="0" border="0">
			<tr>
				<th>
		             <h4><a href=<?php echo $PAGE['THEIR_ITEMS'].">";?>Item you want to borrow from <?php echo "<fb:name firstnameonly=\"true\" uid=\"".$friendid."\"/>"; ?></a></h4>
				</th>
				<th>
		             <div class = "rightaligned"></div>
		        </th>
			</tr>
		</table>
		<div>
			<table class="lists" cellspacing="0" border="0">

		<?php		
			echo Freeform::RenderBorrowItem($friendid, $freeform_id,  $search_type, $unavailable, $confirmation);
		?>
		
		</table>
	</div>
<?php	

}

function renderItem($friendid, $item_asin, $locale, $search_type, $unavailable=false, $confirmation=false) {
	global $PAGE;
	
?>
<br />
		<table class="lists" cellspacing="0" border="0">
			<tr>
				<th>
		             <h4><a href=<?php echo $PAGE['THEIR_ITEMS'].">";?>Item you want to borrow from <?php echo "<fb:name firstnameonly=\"true\" uid=\"".$friendid."\"/>"; ?></a></h4>
				</th>
				<th>
		             <div class = "rightaligned"></div>
		        </th>
			</tr>
		</table>
		<div>
			<table class="lists" cellspacing="0" border="0">

		<?php		
			echo Amazon::RenderBorrowItem($friendid,$item_asin,  $locale,  $search_type, $unavailable, $confirmation);
		?>
		
		</table>
		
	</div>
<?php	

}

echo Analytics::Page("borrow.html?userid=".$userid);

?>