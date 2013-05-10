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

require_once (dirname(__FILE__).'/logmanager.php');

class URLManager {

	private static $ch = null;
	private static $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
	private static $timeout = 30;

	public static function shutdown() {
		LogManager::info(__CLASS__, "*** stopping ***");
    	curl_close(URLManager::$ch);
	}
	
	private static function checkInit() {
		if (URLManager::$ch == null) {
			LogManager::info(__CLASS__, "*** starting ***");
			URLManager::$ch = curl_init();
			register_shutdown_function('URLManager::shutdown');
		}
	}
	
	public static function getURL($request, $post=null, $authstring=null) {
		URLManager::checkInit();
		LogManager::trace(__CLASS__, "getURL ".$request);
		
		curl_setopt(URLManager::$ch, CURLOPT_URL, $request); // set url to post to
		curl_setopt(URLManager::$ch, CURLOPT_FAILONERROR, 1);              // Fail on errors
		curl_setopt(URLManager::$ch, CURLOPT_FOLLOWLOCATION, 1);    // allow redirects
		curl_setopt(URLManager::$ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
		curl_setopt(URLManager::$ch, CURLOPT_PORT, 80);            //Set the port number
		curl_setopt(URLManager::$ch, CURLOPT_TIMEOUT, URLManager::$timeout); // times out after 15s
		
		if ($post !=null) {
			curl_setopt(URLManager::$ch, CURLOPT_POST, true);
			curl_setopt(URLManager::$ch, CURLOPT_POSTFIELDS, $post);
		}
		
		if ($authstring != null) {
			curl_setopt(URLManager::$ch, CURLOPT_HTTPHEADER, array('Authorization: Basic '.base64_encode($authstring)));
		}

		curl_setopt(URLManager::$ch, CURLOPT_USERAGENT, URLManager::$user_agent);

		return curl_exec(URLManager::$ch);
	}

}

?>