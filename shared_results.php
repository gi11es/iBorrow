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
$userid = $_REQUEST["fb_sig_user"];
$user = User::getUser($_REQUEST["fb_sig_user"], true);

if (isset($_REQUEST["fb_sig_user"])) {
	
	if (isset($_REQUEST["freeform"]) && strcmp($_REQUEST["freeform"], "true") == 0) {
		if (isset($_REQUEST["REMOVE"]) && strcmp($_REQUEST["REMOVE"], "true") == 0) {
			$user->removeFreeformSharedItem($_REQUEST["freeform_id"]);
			$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
		} else {
			$user->addFreeformSharedItem($_REQUEST["freeform_type"], utf8_decode($_REQUEST["freeform_title"]), utf8_decode($_REQUEST["freeform_description"]));
			$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
		}
	}
	
	if (isset($_REQUEST["ITEM_ASIN"]) && isset($_REQUEST["ITEM_TYPE"])) {
		if (isset($_REQUEST["REMOVE"]) && strcmp($_REQUEST["REMOVE"], "true") == 0) {
			$user->removeAmazonSharedItem($_REQUEST["ITEM_ASIN"]);
			$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
		}
		else {
			/*$facebook->api_client->feed_publishStoryToUser("<fb:name uid=\"".$userid."\"/> added an item to <a href=\"http://apps.facebook.com/iborrow/my_items.php\"> the list of items you can borrow from <fb:pronoun useyou=\"false\" usethey=\"false\" objective=\"true\" uid=\"".$userid."\"/></a>");*/
			
			$user->addAmazonSharedItem($_REQUEST["ITEM_ASIN"], $_REQUEST["ITEM_TYPE"]);
			$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
		}
	} 		
}

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

	$result = "<form class=\"inlineform\" method=\"post\" id=\"".$text.$to_override."page\" onsubmit=\"do_ajax('".$PAGE['SHARED_RESULTS']."' + '?page_dvd=' + '".urlencode(($to_override == $ITEM_TYPE_ID["DVD"]?$newvalue:$page_dvd))."' + '&page_book=' + '".urlencode(($to_override == $ITEM_TYPE_ID["BOOK"]?$newvalue:$page_book))."' + '&page_cd=' + '".urlencode(($to_override == $ITEM_TYPE_ID["CD"]?$newvalue:$page_cd))."' + '&page_game=' + '".urlencode(($to_override == $ITEM_TYPE_ID["VIDEO_GAME"]?$newvalue:$page_game))."' + '&page_other=' + '".urlencode(($to_override == $ITEM_TYPE_ID["OTHER"]?$newvalue:$page_other))."', 'sharedresults', null); return false;\" >";
	$result .= "<input value=\"".$text."\" type=\"submit\" class=\"inputbutton\"/></form>";

	return $result;
}

$shared_dvd = $user->getSharedTypedItems($ITEM_TYPE_ID["DVD"], true, false);
$total_pages_dvd = ceil(floatval(count($shared_dvd)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_dvd = array_slice($shared_dvd, ($page_dvd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);

$shared_book = $user->getSharedTypedItems($ITEM_TYPE_ID["BOOK"], true, false);
$total_pages_book = ceil(floatval(count($shared_book)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_book = array_slice($shared_book, ($page_book - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);

$shared_cd = $user->getSharedTypedItems($ITEM_TYPE_ID["CD"], true, false);
$total_pages_cd = ceil(floatval(count($shared_cd)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_cd = array_slice($shared_cd, ($page_cd - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);

$shared_game = $user->getSharedTypedItems($ITEM_TYPE_ID["VIDEO_GAME"], true, false);
$total_pages_game = ceil(floatval(count($shared_game)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_game = array_slice($shared_game, ($page_game - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);

$shared_other = $user->getSharedTypedItems($ITEM_TYPE_ID["OTHER"], true, false);
$total_pages_other = ceil(floatval(count($shared_other)) / floatval($SHARED_ITEMS_PAGE_SIZE));
$subset_other = array_slice($shared_other, ($page_other - 1) * $SHARED_ITEMS_PAGE_SIZE, $SHARED_ITEMS_PAGE_SIZE);

renderGenericSharedItems("Your shared DVDs", $subset_dvd, $page_dvd, $total_pages_dvd, $ITEM_TYPE_ID["DVD"]);
renderGenericSharedItems("Your shared books", $subset_book, $page_book, $total_pages_book, $ITEM_TYPE_ID["BOOK"]);
renderGenericSharedItems("Your shared CDs", $subset_cd, $page_cd, $total_pages_cd, $ITEM_TYPE_ID["CD"]);
renderGenericSharedItems("Your shared video games", $subset_game, $page_game, $total_pages_game, $ITEM_TYPE_ID["VIDEO_GAME"]);
renderGenericSharedItems("Your shared miscellaneous items", $subset_other, $page_other, $total_pages_other, $ITEM_TYPE_ID["OTHER"]);

function renderGenericSharedItems($text, $shareditems, $current_page, $max_page, $item_type) {
global $PAGE;

if (count($shareditems) > 0) {
?>

<table class="lists" cellspacing="0" border="0">
	<tr>
		<th>
             <h4><a href=<?php echo $PAGE['MY_ITEMS'].">".$text;?></a></h4>
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
			echo Amazon::renderSharedItem($genericitem["ITEM_ASIN"], $genericitem["LOCALE"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult);
		else
			echo Freeform::renderSharedItem($genericitem["FREEFORM_ID"], $genericitem["TITLE"], $genericitem["DESCRIPTION"], $genericitem["ITEM_TYPE"], $genericitem["STATUS"], $firstresult);
		if ($firstresult) $firstresult = false;
	}

?>
</table>
</div>

<?php
}
}

?>