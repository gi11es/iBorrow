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

require_once (dirname(__FILE__).'/includes/urlmanager.php');
require_once (dirname(__FILE__).'/includes/cachemanager.php');
require_once (dirname(__FILE__).'/includes/constants.php');
require_once (dirname(__FILE__).'/includes/amazon.php');
require_once (dirname(__FILE__).'/includes/analytics.php');
require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/../client/facebook.php');
require_once (dirname(__FILE__).'/settings.php');

$facebook = new Facebook($api_key, $secret);
$userid = $_REQUEST["fb_sig_user"];
$user = User::getUser($userid);

$render_start_time = microtime(true);

if (!isset($_REQUEST["ItemTypeSelection"])) {
	echo "Must be used inside facebook";
	exit(0);
}

if (strcmp($_REQUEST["ItemTypeSelection"], "DVD") == 0) {
	$item_text = "DVD";
	$title_text = "DVDs that match your search";
	$search_type = $ITEM_TYPE_ID["DVD"];
	$params["SearchIndex"] = "DVD";
	if ($user->getLocale() == $AMAZON_LOCALE["US"])
		$params["Sort"] = "relevancerank";
	else
		$params["Sort"] = "salesrank";
	$button_text = "Make this DVD available to your friends";
	$buy_text = "Details about this DVD on Amazon";
	$lookfor = "Title";
	$params["ResponseGroup"] = "Images,Small";
}
elseif (strcmp($_REQUEST["ItemTypeSelection"], "Book") == 0) {
	$item_text = "book";
	$title_text = "Books that match your search";
	$search_type = $ITEM_TYPE_ID["BOOK"];
	$params["SearchIndex"] = "Books";
	if ($user->getLocale() == $AMAZON_LOCALE["US"])
		$params["Sort"] = "relevancerank";
	else
		$params["Sort"] = "salesrank";
	$button_text = "Make this book available to your friends";
	$buy_text = "Details about this book on Amazon";
	$lookfor = "Keywords";
	$params["ResponseGroup"] = "Images,Small";
}
elseif (strcmp($_REQUEST["ItemTypeSelection"], "CD") == 0) {
	$item_text = "CD";
	$title_text = "CDs that match your search";
	$search_type = $ITEM_TYPE_ID["CD"];
	$params["SearchIndex"] = "Music";
	$params["Sort"] = "salesrank";
	$button_text = "Make this CD available to your friends";
	$buy_text = "Details about this CD on Amazon";
	$lookfor = "Keywords";
	$params["ResponseGroup"] = "Images,Small";
}
elseif (strcmp($_REQUEST["ItemTypeSelection"], "Video game") == 0) {
	$item_text = "video game";
	$title_text = "Video games that match your search";
	$search_type = $ITEM_TYPE_ID["VIDEO_GAME"];
	$params["SearchIndex"] = "VideoGames";
	$params["Sort"] = "salesrank";
	$button_text = "Make this video game available to your friends";
	$buy_text = "Details about this video game on Amazon";
	$lookfor = "Keywords";
	$params["ResponseGroup"] = "Images,Small,ItemAttributes";
}
elseif (strcmp($_REQUEST["ItemTypeSelection"], "Other") == 0) {
	$search_type = $ITEM_TYPE_ID["OTHER"];
}
else
	exit(0);

if (isset($_REQUEST["Page"])) {
	$page = $_REQUEST["Page"];
	$params["ItemPage"] = $page;
	} else $page = 1;

	$params["Service"] = "AWSECommerceService";
	$params["AWSAccessKeyId"] = "02VMCFHJBGRBB84TA3R2";
	$params["Operation"] = "ItemSearch";
	$params["ContentType"] = "text%2Fxml";
	$params["Condition"] = "New";

	function renderPageLink($page, $text) {
		global $_REQUEST;
		global $PAGE;
		
		$result = "<form class=\"inlineform\" method=\"post\" id=\"".$text."page\" onsubmit=\"do_ajax('".$PAGE['SEARCH_RESULTS']."?KEYWORDS=".urlencode($_REQUEST["KEYWORDS"])."&ItemTypeSelection=".urlencode($_REQUEST["ItemTypeSelection"])."&Page=".urlencode($page)."', 'searchresults', null); return false;\" >";
		$result .= "<input value=\"".$text."\" type=\"submit\" class=\"inputbutton\"/></form>";

		return $result;
	}
	
	function renderSimplePageLink($thispage) {
		global $_REQUEST;
		global $PAGE;
		
		$result = "<a href=\"#\" onclick=\"do_ajax('".$PAGE['SEARCH_RESULTS']."?KEYWORDS=".urlencode($_REQUEST["KEYWORDS"])."&ItemTypeSelection=".urlencode($_REQUEST["ItemTypeSelection"])."&Page".urlencode($thispage)."', 'searchresults', null); return false;\" >".$thispage."</a>";

		return $result;
	}

	if (isset($_REQUEST["KEYWORDS"])) {

		$params[$lookfor] = urlencode($_REQUEST["KEYWORDS"]);
		$request_start_time = microtime(true);

		$serializedkey = "ItemSearch-".$user->getLocale()."-".$_REQUEST["ItemTypeSelection"]."-".$_REQUEST["KEYWORDS"]."-".$page;
		
		$xmlresult = CacheManager::get($serializedkey);
		$cached = true;

		if (!$xmlresult) {
			$cached = false;
			$xmlresult = URLManager::getURL("http://ecs.".$AMAZON_LOCALE_AWS_URL[$user->getLocale()]."/onca/xml", $params);
			CacheManager::set($serializedkey, $xmlresult);
		}

		$request_end_time = microtime(true);
		$xml = new SimpleXMLElement($xmlresult);

		$total_pages = $xml->Items->TotalPages;
		if ($page < $total_pages)
			$next_page_link = renderPageLink($page + 1, "next");
		if ($page > 1)
			$previous_page_link = renderPageLink($page - 1, "previous");

		include $TEMPLATE["SEARCH_TABLE_TOP"];

		$firstresult = true;

		foreach ($xml->Items->Item as $Item) {
			echo Amazon::renderItemSearchEntry($Item, $search_type, $firstresult, $user);

			if ($firstresult)
				$firstresult = false;
		}
		
		echo Analytics::Page("searched_for.html?userid=".$userid."&search=".urlencode($_REQUEST["KEYWORDS"]));
	} else {
		$total_pages = 1;
		$page_number = 1;
		include $TEMPLATE["SEARCH_TABLE_TOP"];
	}


	?>
</table>
<?php
$render_end_time = microtime(true);
$request_time = $request_end_time - $request_start_time;
$render_time = $render_end_time - $render_start_time - $request_time;
//echo ($cached?"Memcached":"Amazon")." request time: ".$request_time.". Page render time: ".$render_time.".<br><br>";

$active_range_start = $page - 1;
$active_range_end = $page + 2;
if ($active_range_start < 3) { // page close to the start
	if ($active_range_end + 1 >= $total_pages) {
		// render all, eg. 1 2 3 4 5 6
		for ($i = 1; $i <= $total_pages; $i++)
		 	$result .= ($i == $page?"<b>".$i."</b>":renderSimplePageLink($i))." ";
		$result = substr($result, 0, -1);
	}	
	else {
		// render all beginning, eg. 1 2 3 4 5...129
		for ($i = 1; $i <= $active_range_end; $i++)
		 	$result .= ($i == $page?"<b>".$i."</b>":renderSimplePageLink($i))." ";
		$result = substr($result, 0, -1)."...".renderSimplePageLink($total_pages);
	}
} elseif ($page < $total_pages - 3) { // page in the middle
	$result = renderSimplePageLink(1)."...";
	for ($i = $active_range_start; $i <= $active_range_end; $i++)
		$result .= ($i == $page?"<b>".$i."</b>":renderSimplePageLink($i))." ";
	$result = substr($result, 0, -1)."...".renderSimplePageLink($total_pages);
} else { // page close to the end
	$result = renderSimplePageLink(1)."...";
	for ($i = $active_range_start; $i <= $total_pages; $i++)
		$result .= ($i == $page?"<b>".$i."</b>":renderSimplePageLink($i))." ";
	$result = substr($result, 0, -1);
}

if ($total_pages > 0) echo "<form id=\"dummy_form\"></form><div class=\"result_pages\">Results pages: ".$result."</div>";
else echo "<br /><div class=\"result_pages\">No result on ".$AMAZON_LOCALE_URL[$user->getLocale()]."</div>"
?>
</div>
<hr />
<div>
<form method="post" id="manual" onsubmit="do_ajax('<?php echo $PAGE['SHARED_RESULTS']."?freeform_type=".$search_type."&freeform=true&freeform_title="; ?>' + escape(document.getElementById('freeform_title').getValue()) + '&freeform_description=' + escape(document.getElementById('freeform_description').getValue()), 'sharedresults', 'searchresults'); return false;">
You can enter the <?php echo $item_text; ?>'s information manually:<br /><br />
Title:<br /> <input type="text" id="freeform_title" size=60 value="<?php echo htmlentities($_REQUEST["KEYWORDS"]); ?>"/><br />
Description:<br /> <textarea id="freeform_description" cols=85 rows=3 /><br />
<input type="submit" class="inputbutton" value="Add to shared items" />
</div>

<br />
<br />
<br />