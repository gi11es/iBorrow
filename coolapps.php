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

echo UIHelper::RenderMenu($PAGE_CODE['COOL_APPS'], $user, $facebook->api_client);

$quantity = 10;

// This method is specific to my toolkit, it's a wrapper on curl 
// that retrieves the content of a URL
$xmlresult = URLManager::getURL("http://grow.darumazone.com/serve.php?growthid=$growthid"
                                    ."&quantity=$quantity&format=xml");

$xml = new SimpleXMLElement($xmlresult);

$result = "<table> <tr><th></th><th>App name</th><th>Description</th></tr>";
if (isset($xml->result) && $xml->result == 0 && !empty($xml->app)) {
    foreach ($xml->app as $app) {
        $result .= "<tr><td><a href='".$app->link."'><img src='".$app->icon."'/></a></td>";
        $result .= "<td><a href='".$app->link."'>".$app->name."</a></td>";
        $result .= "<td>".$app->text."</td></tr>";
    }
}
$result .= "</table>";

?>
<br/>
<h1><u>Check out more cool facebook applications from the Grow Together app network</u></h1>
<br/>
<?php 
echo $result;
//echo "<br />Page rendered in ".(microtime(true) - $start_time)." seconds."; 
?>

</div>

<?php

echo Analytics::Page("invite.html?userid=".$userid);

?>