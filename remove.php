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

require_once (dirname(__FILE__).'/includes/logmanager.php');
require_once (dirname(__FILE__).'/includes/user.php');

if (isset($_REQUEST["fb_sig_user"])) {
	LogManager::info("Removal", "User ".$_REQUEST["fb_sig_user"]." uninstalled the application");
	User::deleteUser($_REQUEST["fb_sig_user"]); 
} else {
	LogManager::error("Removal", "Wrong request: ".print_r($_REQUEST, true));
}

?>