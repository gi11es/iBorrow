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

require_once (dirname(__FILE__).'/constants.php');
require_once (dirname(__FILE__).'/user.php');
require_once (dirname(__FILE__).'/growtogether.php');
require_once (dirname(__FILE__).'/../settings.php');

class UIHelper {
	public static function RenderItemTypeSelection() {
		global $ITEM_TYPE;

		$result = "<SELECT id=\"ItemTypeSelection\">";
		foreach ($ITEM_TYPE as $type) {
			$result .= "<OPTION VALUE=\"".$type."\">".$type."</OPTION>";
		}
		$result .= "</SELECT>";
		return $result;
	} 

	public static function RenderMenu($currentpage, $user, $api_client) {
		global $PAGE;
		global $PAGE_CODE;

		$result = growtogether::getTextAds("short", 4)."<br/><br/><hr/>";
		$result .= "<div style=\"padding: 10px;\">";
		$result .= "<table class=\"titletable\" cellspacing=\"0\" border=\"0\"><tr><th><a href=\"http://apps.facebook.com/iborrow/\"><img src=\"http://iborrow.darumazone.com/logo_medium.gif\"></a> </th><th> ".UIHelper::RenderRandomMessage($user, $api_client)."</th><th><div id=\"loader\" style=\"visibility: hidden;\"><img src=\"http://iborrow.darumazone.com/ajax-loader.gif\"/></div></th></tr></table>\r\n";
		$result .= "<br/> <fb:tabs><fb:tab-item href='".$PAGE['INDEX']."' title=\"Home\" ".($currentpage == $PAGE_CODE['INDEX']?"selected='true'":"")." />\r\n";
		$result .= "<fb:tab-item href='".$PAGE['THEIR_ITEMS']."' title=\"Your friends' items\" ".($currentpage == $PAGE_CODE['THEIR_ITEMS']?"selected='true'":""). "/>\r\n";
		$result .= "<fb:tab-item href='".$PAGE['MY_ITEMS']."' title='Your shared items' ".($currentpage == $PAGE_CODE['MY_ITEMS']?"selected='true'":""). "/>\r\n";
		$result .= "<fb:tab-item href='".$PAGE['PREFERENCES']."' title='Preferences' ".($currentpage == $PAGE_CODE['PREFERENCES']?"selected='true'":"")." />\r\n";
		$result .= "<fb:tab-item href='".$PAGE['INVITE']."' title='Invite' ".($currentpage == $PAGE_CODE['INVITE']?"selected='true'":"")." />\r\n";
		//$result .= "<fb:tab-item href='".$PAGE['COOL_APPS']."' title='Cool apps' ".($currentpage == $PAGE_CODE['COOL_APPS']?"selected='true'":"")." />\r\n";
		$result .= "</fb:tabs>";
		return $result;
	}
	
	private static function RenderRandomMessage($user, $api_client) {
		global $PAGE;
		
		$friends = $user->getFriendsIDs($api_client);
		$friends_amount = count($friends);
		
		srand(time());
		$random = (rand()%3);
		
		if ($friends_amount == 0)
			return "<h2>Hi <fb:name firstnameonly=\"true\" uid=\"".$user->getId()."\" useyou=\"false\"/>, none of your friends are using iBorrow, <a href=\"".$PAGE['INVITE']."\" >invite some</a>!</h2>";
			
		$result = "";
		
		switch ($random) {
			case 0:
				$result = "<h2>Hi <fb:name firstnameonly=\"true\" uid=\"".$user->getId()."\" useyou=\"false\"/>, only ".$friends_amount." of your friends ".($friends_amount > 1?"are":"is")." using iBorrow, <a href=\"".$PAGE['INVITE']."\" >invite more</a>!</h2>\r\n";
				break;
			case 1:
				$total_friends_shared = $user->getFriendsSharedCount($api_client);
				$real_friends_shared = ($total_friends_shared == 0?"no":$total_friends_shared);
				$result = "<h2>Hi <fb:name firstnameonly=\"true\" uid=\"".$user->getId()."\" useyou=\"false\"/>, your friends currently share <a href='".$PAGE['THEIR_ITEMS']."'>".$real_friends_shared." item".($real_friends_shared > 1?"s":"")."</a>.</h2>";
				break;
			case 2:
				$totalshared = count($user->getAmazonSharedItems()) + count($user->getFreeformSharedItems());
				if ($totalshared == 0) $totalshared = "no";
				$result = "<h2>You currently share <a href=\"".$PAGE['MY_ITEMS']."\">".$totalshared." item".($totalshared > 1?"s":"")."</a> your friends can borrow.</h2>";
				break;
		}

		return $result;
	}
}

?>