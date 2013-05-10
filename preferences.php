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

$start_time = microtime(true);

require_once (dirname(__FILE__).'/../client/facebook.php');
require_once (dirname(__FILE__).'/settings.php');
require_once (dirname(__FILE__).'/includes/constants.php');
require_once (dirname(__FILE__).'/includes/uihelper.php');
require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/includes/analytics.php');

include $TEMPLATE["SEARCH_STYLE"];

$facebook = new Facebook($api_key, $secret);
$facebook->require_frame();
$userid = $facebook->require_install();
$user = User::getUser($userid, true);

$oldkey = $user->getSessionKey();
if ($facebook->api_client->session_key != $oldkey && isset($_REQUEST["fb_sig_expires"]) && $_REQUEST["fb_sig_expires"] == 0) {
	$user->setSessionKey($facebook->api_client->session_key);
}

$displaymessage = false;

if (isset($_REQUEST["WebsiteSelection"])) {
	if (isset($AMAZON_LOCALE[$_REQUEST["WebsiteSelection"]])) {
		$user->setLocale($AMAZON_LOCALE[$_REQUEST["WebsiteSelection"]]);
		
		if(ereg("^[0-9]+$", $_REQUEST["quantity_dvd"])) {
			$quantity_dvd = (int)$_REQUEST["quantity_dvd"];
			if ($user->getProfileDisplay($ITEM_TYPE_ID["DVD"]) != $quantity_dvd)
				$user->setProfileDisplay($ITEM_TYPE_ID["DVD"], $quantity_dvd);
		}
		
		if(ereg("^[0-9]+$", $_REQUEST["quantity_book"])) {
			$quantity_book = (int)$_REQUEST["quantity_book"];
			if ($user->getProfileDisplay($ITEM_TYPE_ID["BOOK"]) != $quantity_book)
				$user->setProfileDisplay($ITEM_TYPE_ID["BOOK"], $quantity_book);
		} 
		
		if(ereg("^[0-9]+$", $_REQUEST["quantity_cd"])) {
			$quantity_cd = (int)$_REQUEST["quantity_cd"];
			if ($user->getProfileDisplay($ITEM_TYPE_ID["CD"]) != $quantity_cd)
				$user->setProfileDisplay($ITEM_TYPE_ID["CD"], $quantity_cd);
		}
		
		if(ereg("^[0-9]+$", $_REQUEST["quantity_game"])) {
			$quantity_game = (int)$_REQUEST["quantity_game"];
			if ($user->getProfileDisplay($ITEM_TYPE_ID["VIDEO_GAME"]) != $quantity_game)
				$user->setProfileDisplay($ITEM_TYPE_ID["VIDEO_GAME"], $quantity_game);
		}
		
		if(ereg("^[0-9]+$", $_REQUEST["quantity_other"])) {
			$quantity_cd = (int)$_REQUEST["quantity_other"];
			if ($user->getProfileDisplay($ITEM_TYPE_ID["OTHER"]) != $quantity_other)
				$user->setProfileDisplay($ITEM_TYPE_ID["OTHER"], $quantity_other);
		}
		
		$facebook->api_client->profile_setFBML('', $userid, $user->generateProfileFBML());
		
		$displaymessage = true;
	}		
}

echo UIHelper::RenderMenu($PAGE_CODE['PREFERENCES'], $user, $facebook->api_client);

if ($displaymessage) echo '<fb:success message="Your preferences have been updated successfully!" />';
?>
<br />
<div style="clear: both;"/>
<form method="post" action=<?php echo $PAGE['PREFERENCES'];?>>We use Amazon's database to retrieve the descriptions of items. You can change which Amazon website we use to retrieve the information: <SELECT NAME="WebsiteSelection">
	
<?php
	foreach ($AMAZON_LOCALE as $key => $localeid) {
		echo " <OPTION VALUE=\"".$key."\" ".($user->getLocale() == $localeid?"SELECTED":"").">".$AMAZON_LOCALE_URL[$localeid]."</OPTION> ";
	}

?>
</SELECT>
<br />
Feel free to mix items from different Amazon websites in your shared list.
<br />
<hr />
Items displayed on your profile:<br />
<br />
Display latest <input type="TEXT" name="quantity_dvd" value="<?php echo $user->getProfileDisplay($ITEM_TYPE_ID["DVD"]); ?>" size=2 maxlength=2/> DVDs, <input type="TEXT" name="quantity_book" value="<?php echo $user->getProfileDisplay($ITEM_TYPE_ID["BOOK"]); ?>" size=2 maxlength=2/> books, <input type="TEXT" name="quantity_cd" value="<?php echo $user->getProfileDisplay($ITEM_TYPE_ID["CD"]); ?>" size=2 maxlength=2/> CDs, <input type="TEXT" name="quantity_game" value="<?php echo $user->getProfileDisplay($ITEM_TYPE_ID["VIDEO_GAME"]); ?>" size=2 maxlength=2/> video games and <input type="TEXT" name="quantity_other" value="<?php echo $user->getProfileDisplay($ITEM_TYPE_ID["OTHER"]); ?>" size=2 maxlength=2/> other (miscellaneous) items. <br /><i>Setting the quantity to 0 will hide the corresponding section on your profile.</i><br />
<br />
<br />
<INPUT TYPE="submit" class="inputbutton" value="Save changes"/>
</form>

<?php //echo "<br />Page rendered in ".(microtime(true) - $start_time)." seconds."; ?>

</div>

<?php

echo Analytics::Page("preferences.html?userid=".$userid);

?>