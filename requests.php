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

require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/includes/amazon.php');
require_once (dirname(__FILE__).'/includes/freeform.php');
require_once (dirname(__FILE__).'/includes/constants.php');
require_once (dirname(__FILE__).'/../client/facebook.php');
require_once (dirname(__FILE__).'/settings.php');

echo " ";

global $STATUS;
global $PAGE;

$facebook = new Facebook($api_key, $secret);
$userid = $_REQUEST["fb_sig_user"];
$user = User::getUser($_REQUEST["fb_sig_user"], true);

if (isset($_REQUEST["DECLINE"]) && strcmp($_REQUEST["DECLINE"], "Decline") == 0) {
	if (isset($_REQUEST["ITEM_ASIN"]))
		$user->cancelAmazonRequest($_REQUEST["ITEM_ASIN"]);
	elseif (isset($_REQUEST["FREEFORM_ID"]))
		$user->cancelFreeformRequest($_REQUEST["FREEFORM_ID"]);
		
	if (isset($_REQUEST["BORROWER_ID"]) && isset($_REQUEST["TITLE"]))
		$facebook->api_client->notifications_send($_REQUEST["BORROWER_ID"], "declined your request to <a href=\"".$PAGE["THEIR_ITEMS"]."\">borrow ".$_REQUEST["TITLE"]."</a>");
		
	$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
}

if (isset($_REQUEST["ACCEPT"]) && strcmp($_REQUEST["ACCEPT"], "Accept") == 0) {
	if (isset($_REQUEST["ITEM_ASIN"]))
		$user->acceptAmazonRequest($_REQUEST["ITEM_ASIN"]);
	elseif (isset($_REQUEST["FREEFORM_ID"]))
		$user->acceptFreeformRequest($_REQUEST["FREEFORM_ID"]);
		
	if (isset($_REQUEST["BORROWER_ID"]) && isset($_REQUEST["TITLE"]))
		$facebook->api_client->notifications_send($_REQUEST["BORROWER_ID"], "accepted your request to <a href=\"".$PAGE["THEIR_ITEMS"]."\">borrow ".$_REQUEST["TITLE"]."</a>");
}

$generic_requests = Array();

$amazonitems = $user->getAmazonSharedItems();

foreach ($amazonitems as $amazonitem) {
	if ($amazonitem["STATUS"] == $STATUS["REQUESTED"])
		$generic_requests []= $amazonitem;
}

$freeformitems = $user->getFreeformSharedItems();

foreach ($freeformitems as $freeformitem) {
	if ($freeformitem["STATUS"] == $STATUS["REQUESTED"])
		$generic_requests []= $freeformitem;
}

if (!empty($generic_requests)) {
	renderGenericSharedItems2("Items your friends want to borrow from you", $generic_requests);
} else echo "<i>None of your friends have sent you new requests to borrow your items.</i><br />";

function renderGenericSharedItems2($text, $shareditems) {
	global $PAGE;
	global $user;
	
	if (count($shareditems) > 0) {
		?>

		<table class="lists" cellspacing="0" border="0">
			<tr>
				<th>
					<h4><a href=<?php echo $PAGE['INDEX'].">".$text;?></a></h4>
				</th>
				<th>
					<div class = "rightaligned"></div>
				</th>
			</tr>
		</table>
		<div>
			<table class="lists" cellspacing="0" border="0">
				<?php

			$firstresult = true;
			foreach($shareditems as $genericitem) {
				if (isset($genericitem["ITEM_ASIN"]))
					echo Amazon::renderRequestedItem($genericitem["BORROWER_ID"], $genericitem["ITEM_ASIN"], $genericitem["LOCALE"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult, $genericitem["REQUEST_MESSAGE"]);
				else
					echo Freeform::renderRequestedItem($genericitem["BORROWER_ID"], $genericitem["FREEFORM_ID"], $genericitem["TITLE"], $genericitem["DESCRIPTION"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult, $genericitem["REQUEST_MESSAGE"]);
				if ($firstresult) $firstresult = false;
			}

			?>
		</table>
	</div>

	<?php
	}
}

?>