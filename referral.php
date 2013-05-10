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

	require_once (dirname(__FILE__).'/includes/constants.php');
	require_once (dirname(__FILE__).'/includes/analytics.php');

	echo Analytics::Page("referral.html?referrer=".urlencode($_REQUEST['r']));
	echo '<fb:redirect url="' . $APP_ADD_PAGE . '"/>';
?>