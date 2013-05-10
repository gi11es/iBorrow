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

$facebook = new Facebook($api_key, $secret);
$userid = $facebook->require_login();
$user = User::getUser($_REQUEST["fb_sig_user"], true);

$page_dvd = 1;
if (isset($_REQUEST["page_dvd"]))
	$page_dvd = $_REQUEST["page_dvd"];

$page_book = 1;
if (isset($_REQUEST["page_book"]))
	$page_book = $_REQUEST["page_book"];

$page_cd = 1;
if (isset($_REQUEST["page_cd"]))
	$page_cd = $_REQUEST["page_cd"];

$page_game = 1;
if (isset($_REQUEST["page_game"]))
	$page_game = $_REQUEST["page_game"];

$page_other = 1;
if (isset($_REQUEST["page_other"]))
	$page_other = $_REQUEST["page_other"];

function renderPageLink($newvalue, $text, $to_override) {
	global $_REQUEST;
	global $PAGE;
	global $ITEM_TYPE_ID;
	global $page_dvd;
	global $page_cd;
	global $page_game;
	global $page_book;
	global $page_other;
	
	$result = "<form class=\"inlineform\" method=\"post\" id=\"".$text.$to_override."page\" onsubmit=\"do_ajax('".$PAGE['FRIENDS_SHARED_RESULTS']."' + '?page_dvd=' + escape('".($to_override == $ITEM_TYPE_ID["DVD"]?$newvalue:$page_dvd)."') + '&page_book=' + escape('".($to_override == $ITEM_TYPE_ID["BOOK"]?$newvalue:$page_book)."') + '&page_cd=' + escape('".($to_override == $ITEM_TYPE_ID["CD"]?$newvalue:$page_cd)."') + '&page_game=' + escape('".($to_override == $ITEM_TYPE_ID["VIDEO_GAME"]?$newvalue:$page_game)."') + '&page_other=' + escape('".($to_override == $ITEM_TYPE_ID["OTHER"]?$newvalue:$page_other)."'), 'friendssharedresults', null); return false;\" >";
	$result .= "<input value=\"".$text."\" type=\"submit\" class=\"inputbutton\"/></form>";

	return $result;
}

$shared_dvd = $user->getFriendsTypedItems($ITEM_TYPE_ID["DVD"], $facebook->api_client, true);
$total_pages_dvd = ceil(floatval(count($shared_dvd)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_dvd = array_slice($shared_dvd, ($page_dvd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
// We need to check if the page is not empty due to friends removing shared items while this user is browsing
if (empty($subset_dvd) && $page_dvd != 1) {
	$page_dvd = 1;
	$subset_dvd = array_slice($shared_dvd, ($page_dvd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
}

$shared_book = $user->getFriendsTypedItems($ITEM_TYPE_ID["BOOK"], $facebook->api_client, true);
$total_pages_book = ceil(floatval(count($shared_book)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_book = array_slice($shared_book, ($page_book - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
// We need to check if the page is not empty due to friends removing shared items while this user is browsing
if (empty($subset_book) && $page_book != 1) {
	$page_book = 1;
	$subset_book = array_slice($shared_nook, ($page_nook - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
}

$shared_cd = $user->getFriendsTypedItems($ITEM_TYPE_ID["CD"], $facebook->api_client, true);
$total_pages_cd = ceil(floatval(count($shared_cd)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_cd = array_slice($shared_cd, ($page_cd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
// We need to check if the page is not empty due to friends removing shared items while this user is browsing
if (empty($subset_cd) && $page_cd != 1) {
	$page_cd = 1;
	$subset_cd = array_slice($shared_cd, ($page_cd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
}

$shared_game = $user->getFriendsTypedItems($ITEM_TYPE_ID["VIDEO_GAME"], $facebook->api_client, true);
$total_pages_game = ceil(floatval(count($shared_game)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_game = array_slice($shared_game, ($page_game - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
// We need to check if the page is not empty due to friends removing shared items while this user is browsing
if (empty($subset_game) && $page_game != 1) {
	$page_game = 1;
	$subset_game = array_slice($shared_game, ($page_game - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
}

$shared_other = $user->getFriendsTypedItems($ITEM_TYPE_ID["OTHER"], $facebook->api_client, true);
$total_pages_other = ceil(floatval(count($shared_other)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_other = array_slice($shared_other, ($page_other - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
// We need to check if the page is not empty due to friends removing shared items while this user is browsing
if (empty($subset_other) && $page_other != 1) {
	$page_other = 1;
	$subset_other = array_slice($shared_other, ($page_other - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);
}

$drawn = 0;
$drawn += renderGenericSharedItems("Your friends' shared DVDs", $subset_dvd, $page_dvd, $total_pages_dvd, $ITEM_TYPE_ID["DVD"]);
$drawn += renderGenericSharedItems("Your friends' shared books", $subset_book, $page_book, $total_pages_book, $ITEM_TYPE_ID["BOOK"]);
$drawn += renderGenericSharedItems("Your friends' shared CDs", $subset_cd, $page_cd, $total_pages_cd, $ITEM_TYPE_ID["CD"]);
$drawn += renderGenericSharedItems("Your friends' shared video games", $subset_game, $page_game, $total_pages_game, $ITEM_TYPE_ID["VIDEO_GAME"]);
$drawn += renderGenericSharedItems("Your friends' shared miscellaneous items", $subset_other, $page_other, $total_pages_other, $ITEM_TYPE_ID["OTHER"]);

if ($drawn == 0)
	echo "<i>Your friends don't share any items yet.</i>";

function renderGenericSharedItems($text, $shareditems, $current_page, $max_page, $item_type) {
	global $PAGE;
	
	if (count($shareditems) > 0) {
		?>

		<table class="lists" cellspacing="0" border="0">
			<tr>
				<th>
					<h4><a href=<?php echo $PAGE['THEIR_ITEMS'].">".$text;?></a></h4>
				</th>
				<th>
					<div class = "rightaligned"><?php echo ($current_page > 1?renderPageLink($current_page - 1, "previous", $item_type):"")." Page ".$current_page." of ".$max_page." ".($current_page < $max_page?renderPageLink($current_page + 1, "next", $item_type):""); ?></div>
				</th>
			</tr>
		</table>
		<div>
			<table class="lists" cellspacing="0" border="0">
				<?php
				
				

			$firstresult = true;
			foreach($shareditems as $genericitem) {
				if (isset($genericitem["ITEM_ASIN"]))
					echo Amazon::renderFriendsItem($genericitem["USER_ID"], $genericitem["ITEM_ASIN"], $genericitem["LOCALE"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $genericitem["BORROWER_ID"], $firstresult);
				else
					echo Freeform::renderFriendsItem($genericitem["USER_ID"], $genericitem["FREEFORM_ID"], $genericitem["TITLE"], $genericitem["DESCRIPTION"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $genericitem["BORROWER_ID"], $firstresult);
				if ($firstresult) $firstresult = false;
			}

			?>
		</table>
	</div>

	<?php
		return 1;
	} else return 0;
}
?>

