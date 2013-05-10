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
require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/includes/analytics.php');
require_once (dirname(__FILE__).'/includes/uihelper.php');

include $TEMPLATE["SEARCH_STYLE"];

$facebook = new Facebook($api_key, $secret);
$facebook->require_frame();
$userid = $facebook->require_install();
$user = User::getUser($userid, true);

$oldkey = $user->getSessionKey();
if ($facebook->api_client->session_key != $oldkey && isset($_REQUEST["fb_sig_expires"]) && $_REQUEST["fb_sig_expires"] == 0) {
	$user->setSessionKey($facebook->api_client->session_key);
}

$friends = $user->getFriendsIDs($facebook->api_client);

$liststring = "";
foreach ($friends as $friend) {
	$liststring .= $friend.",";
}
if (strcmp($liststring, "") != 0)
	$liststring = substr($liststring, 0, -1);

echo UIHelper::RenderMenu($PAGE_CODE['INVITE'], $user, $facebook->api_client);

?>

<fb:request-form type='Borrow my stuff' action='http://apps.facebook.com/iborrow/index.php' content="<a href='http://apps.facebook.com/iborrow/index.php'>Install iBorrow</a> to get DVDs, books, CDs, video games and more from me and your friends! It's easy to share stuff, too. <fb:req-choice url='http://apps.facebook.com/iborrow/' label='Start borrowing and lending stuff' />" invite="true">

<fb:multi-friend-selector actiontext="Select the friends you want to invite to iBorrow. (All of them.)" rows="3" max="20" <?php echo (strcmp($liststring, "") != 0?"exclude_ids=\"".$liststring."\"":""); ?>/>

</fb:request-form>

<?php //echo "<br />Page rendered in ".(microtime(true) - $start_time)." seconds."; ?>

</div>

<?php

echo Analytics::Page("invite.html?userid=".$userid);

?>