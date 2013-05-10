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

require_once (dirname(__FILE__).'/includes/constants.php');
require_once (dirname(__FILE__).'/includes/user.php');
require_once (dirname(__FILE__).'/includes/analytics.php');
require_once (dirname(__FILE__).'/includes/uihelper.php');
require_once (dirname(__FILE__).'/../client/facebook.php');
require_once (dirname(__FILE__).'/settings.php');

include $TEMPLATE["SEARCH_STYLE"];

$facebook = new Facebook($api_key, $secret);
$facebook->require_frame();
$userid = $facebook->require_install();
$user = User::getUser($userid);

$oldkey = $user->getSessionKey();
if ($facebook->api_client->session_key != $oldkey && isset($_REQUEST["fb_sig_expires"]) && $_REQUEST["fb_sig_expires"] == 0) {
	$user->setSessionKey($facebook->api_client->session_key);
}

echo UIHelper::RenderMenu($PAGE_CODE['MY_ITEMS'], $user, $facebook->api_client);
?>
  
    <form method="post" onsubmit="do_ajax('<?php echo $PAGE['SEARCH_RESULTS']."?fb_sig_user=".urlencode($_REQUEST["fb_sig_user"]); ?>' + '&KEYWORDS=' + escape(document.getElementById('KEYWORDS').getValue()) + '&ItemTypeSelection=' + escape(document.getElementById('ItemTypeSelection').getValue()), 'searchresults', null); return false;" >
<?php
        echo '<br/>Add a '.UIHelper::RenderItemTypeSelection().' your friends can borrow from you: ';
        echo '<input type="TEXT" id="KEYWORDS" />';
?>
      <input value="GO" type="submit" class="inputbutton" />
    </form>
	<br>
<?php

if (count($user->getAmazonSharedItems()) + count($user->getFreeformSharedItems()) == 0) {
	echo "<div align=center><img src=\"http://iborrow.darumazone.com/youcanstart.gif\" /></div>";
}

?>
	<div id="searchresults" style="display: none;"></div>
	<div id="sharedresults">
	<?php include 'shared_results.php'; ?>	
	</div>

  <div style="clear: both;"/>

<?php //echo "<br />Page rendered in ".(microtime(true) - $start_time)." seconds."; ?>

</div>

<?php

echo Analytics::Page("my_items.html?userid=".$userid);

?>
