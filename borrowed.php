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

echo "<br /> ";

global $STATUS;
global $PAGE;

$facebook = new Facebook($api_key, $secret);
$userid = $_REQUEST["fb_sig_user"];
$user = User::getUser($_REQUEST["fb_sig_user"], true);

if (isset($_REQUEST["RETURNED"]) && strcmp($_REQUEST["RETURNED"], "true") == 0) {
	if (isset($_REQUEST["ITEM_ASIN"]))
		$user->cancelAmazonRequest($_REQUEST["ITEM_ASIN"]);
	elseif (isset($_REQUEST["FREEFORM_ID"]))
		$user->cancelFreeformRequest($_REQUEST["FREEFORM_ID"]);
}

$generic_requests = Array();

$amazonitems = $user->getAmazonSharedItems();

foreach ($amazonitems as $amazonitem) {
	if ($amazonitem["STATUS"] == $STATUS["BORROWED"])
		$generic_requests []= $amazonitem;
}

$freeformitems = $user->getFreeformSharedItems();

foreach ($freeformitems as $freeformitem) {
	if ($freeformitem["STATUS"] == $STATUS["BORROWED"])
		$generic_requests []= $freeformitem;
}

if (!empty($generic_requests)) {
	renderGenericSharedItems3("Items borrowed from you", $generic_requests);
}

function renderGenericSharedItems3($text, $shareditems) {
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
					echo Amazon::renderBorrowedItem($genericitem["BORROWER_ID"], $genericitem["ITEM_ASIN"], $genericitem["LOCALE"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult, $genericitem["REQUEST_MESSAGE"]);
				else
					echo Freeform::renderBorrowedItem($genericitem["BORROWER_ID"], $genericitem["FREEFORM_ID"], $genericitem["TITLE"], $genericitem["DESCRIPTION"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult, $genericitem["REQUEST_MESSAGE"]);
				if ($firstresult) $firstresult = false;
			}

			?>
		</table>
	</div>

	<?php
	}
}

?>