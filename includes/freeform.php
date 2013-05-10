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

class Freeform {
	
	public static function renderSharedItem($freeform_id, $title, $description, $search_type, $status, $firstresult) {
		global $PAGE;
		global $ITEM_TYPE_ID;
			
		$result= "<tr>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";

		if (strlen($title) > 70) {
			$title_clean = trim(substr($title, 0, 68))."...";
		} else $title_clean = $title;
		$title_clean = htmlentities(stripslashes($title_clean));
		
		if (strlen($description) > 400) {
			$description_clean = trim(substr($description, 0, 400))."...";
		} else $description_clean = $description;
		$description_clean = htmlentities(stripslashes($description_clean));

		$actionstring = "<br><br><form method=\"post\" onsubmit=\"do_ajax('".$PAGE['SHARED_RESULTS']."?freeform_type=".urlencode($search_type)."&freeform_id=".urlencode($freeform_id)."&REMOVE=true&freeform=true"."', 'sharedresults', 'searchresults'); return false;\"> <input value=\"Remove from your shared items\" type=\"submit\" class=\"inputbutton\" /></form>";

		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".$title_clean."</h2>".$description_clean.$actionstring."</div></td>";
		$result.= "</tr>";

		return $result;
	}
	
	public static function renderFriendsItem($friendsid, $freeform_id, $title, $description, $search_type, $status, $borrower_id, $firstresult) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		global $STATUS;
		
		if ($search_type == $ITEM_TYPE_ID["DVD"]) {
			$button_text = "Borrow this DVD";
		}
		elseif ($search_type == $ITEM_TYPE_ID["BOOK"]) {
			$button_text = "Borrow this book";
		}
		elseif ($search_type == $ITEM_TYPE_ID["CD"]) {
			$button_text = "Borrow this CD";
		}
		elseif ($search_type == $ITEM_TYPE_ID["VIDEO_GAME"]) {
			$button_text = "Borrow this video game";
		} else $button_text = "Borrow this item";
			
		$result= "<tr>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";
		
		if (strlen($description) > 400) {
			$description_clean = trim(substr($description, 0, 400))."...";
		} else $description_clean = $description;
		$description_clean = htmlentities(stripslashes($description_clean));
		
		if ($status == $STATUS["REQUESTED"]) {
			$sharedstring = "<br /><i>Requested by <b><fb:name uid=\"".$borrower_id."\" firstnameonly=\"true\"/></b>, waiting for an answer from <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i>";
			$actionstring = "";
		} else {
			$sharedstring = "<br /><i>Shared by <b><fb:name uid=\"".$friendsid."\" firstnameonly=\"true\"/></b></i>";

			$actionstring = "<br><form method=\"post\" action=".$PAGE['BORROW']."> <input type=\"hidden\" name=\"FRIEND_ID\" value=\"".$friendsid."\"/> <input type=\"hidden\" name=\"FREEFORM_ID\" value=\"".$freeform_id."\"/> <input type=\"hidden\" name=\"SEARCH_TYPE\" value=\"".$search_type."\"/> <input value=\"".$button_text."\" type=\"submit\" class=\"inputbutton\" /> </form>";
		}
		
		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".htmlentities(stripslashes($title))."</h2>".$description_clean.$sharedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderRequestedItem($borrower_id, $freeform_id, $title, $description, $search_type, $status, $firstresult, $message) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		global $STATUS;
			
		$result= "<tr>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";
	
		if (strlen($description) > 400) {
			$description_clean = trim(substr($description, 0, 400))."...";
		} else $description_clean = $description;
		$description_clean = htmlentities(stripslashes($description_clean));
		

		$sharedstring = "<br /><i>Requested by <b><fb:name uid=\"".$borrower_id."\" firstnameonly=\"true\"/></b></i><br /><br />".$message;

		$actionstring = "<br  /><br  /><form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['REQUESTS']."?fb_sig_user=".urlencode($_REQUEST["fb_sig_user"])."&ACCEPT=Accept&BORROWER_ID=".urlencode($borrower_id)."&TITLE=".urlencode($title)."&FREEFORM_ID=".urlencode($freeform_id)."&SEARCH_TYPE=".urlencode($search_type)."', 'requests', null); do_ajax('".$PAGE['BORROWED']."', 'borrowed', null); return false;\"> <input name=\"ACCEPT\" value=\"Accept\" type=\"submit\" class=\"inputbutton\" /> </form> 
		
		<form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['REQUESTS']."?fb_sig_user=".urlencode($_REQUEST["fb_sig_user"])."&DECLINE=Decline&BORROWER_ID=".urlencode($borrower_id)."&TITLE=".urlencode($title)."&FREEFORM_ID=".urlencode($freeform_id)."&SEARCH_TYPE=".urlencode($search_type)."', 'requests', null); return false;\"> <input name=\"DECLINE\" value=\"Decline\" type=\"submit\" class=\"inputbutton\" /> </form>";
		
		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".htmlentities(stripslashes($title))."</h2>".$description_clean.$sharedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$borrower_id."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderBorrowedItem($borrower_id, $freeform_id, $title, $description, $search_type, $status, $firstresult, $message) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		global $STATUS;
			
		$result= "<tr>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"></div></td>";
		
		if (strlen($description) > 400) {
			$description_clean = trim(substr($description, 0, 400))."...";
		} else $description_clean = $description;
		$description_clean = htmlentities(stripslashes($description_clean));

		$sharedstring = "<br /><i>Borrowed by <b><fb:name uid=\"".$borrower_id."\" firstnameonly=\"true\"/></b></i><br /><br />".$message;

		$actionstring = "<br  /><br  /><form style=\"display: inline; margin: 0;\" method=\"post\" onsubmit=\"do_ajax('".$PAGE['BORROWED']."?RETURNED=true&BORROWER_ID=".urlencode($borrower_id)."&TITLE=".urlencode($title)."&FREEFORM_ID=".urlencode($freeform_id)."&SEARCH_TYPE=".urlencode($search_type)."', 'borrowed', null); return false;\"> <input name=\"RETURNED\" value=\"This item was returned to me\" type=\"submit\" class=\"inputbutton\" /> </form>";
		
		$result.= "<td class=\"title\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><h2>".htmlentities(stripslashes($title))."</h2>".$description_clean.$sharedstring.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item".($firstresult?"first":"")." clearfix\"><fb:profile-pic uid=\"".$borrower_id."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
	
	public static function renderBorrowLink($freeformid, $search_type, $userid, $title) {
		global $PAGE;
		
		return "<a href=\"".$PAGE["BORROW"]."?FRIEND_ID=".$userid."&FREEFORM_ID=".$freeformid."&SEARCH_TYPE=".$search_type."\" title=\"".$title."\">".$title."</a>";
	}
	
	public static function renderBorrowItem($friendsid, $freeform_id, $search_type, $unavailable=false, $confirmation=false) {
		global $PAGE;
		global $ITEM_TYPE_ID;
		
		$the_item = null;
		
		$user = User::getUser($friendsid);
		foreach ($user->getFreeformSharedItems() as $item)
			if ($item["FREEFORM_ID"] == $freeform_id)
				$the_item = $item;
				
		if ($the_item != null) {
			$title = $the_item["TITLE"];
			$description = $the_item["DESCRIPTION"];
		}
					
		$result= "<tr>";
		$result.= "<td class=\"image\"><div class=\"list_itemfirst clearfix\"></div></td>";
		
		$description_clean = htmlentities(stripslashes($description));
		
		$actionstring = "<br /><br /><form method=\"post\" action=".$PAGE['BORROW']."> <input type=\"hidden\" name=\"BORROW\" value=\"true\"/> <input type=\"hidden\" name=\"FRIEND_ID\" value=\"".$friendsid."\"/> <input type=\"hidden\" name=\"FREEFORM_ID\" value=\"".$freeform_id."\"/> <input type=\"hidden\" name=\"SEARCH_TYPE\" value=\"".$search_type."\"/>  <b>Please enter details about your request (e.g. when to meet to get the item):</b><br /> <textarea name=\"borrowal_description\" cols=60 rows=3 /> <br /> <input value=\"Send request to your friend\" type=\"submit\" class=\"inputbutton\" /></form>";
		
		if ($confirmation)
			$actionstring = "<br /><br /><b>Your request was successfully sent to <fb:name firstnameonly=\"true\" uid=\"".$friendsid."\"/>.</b>";
			
		if ($unavailable)
			$actionstring = "<br /><br /><b>Unfortunately this item has become unavailable and cannot be borrowed at this time.</b>";

		$result.= "<td class=\"title\"><div class=\"list_item clearfix\"><h2>".htmlentities(stripslashes($title))."</h2>".$description_clean.$actionstring."</div></td>";
		$result.= "<td class=\"image\"><div class=\"list_item clearfix\"><fb:profile-pic uid=\"".$friendsid."\" linked=\"yes\" /></div></td></tr>";

		return $result;
	}
}

?>