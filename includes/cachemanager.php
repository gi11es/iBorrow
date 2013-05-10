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
require_once (dirname(__FILE__).'/../settings.php');

class CacheManager {
	private static $started = false;
	private static $memcache = null;

	private static function initCheck() {
		global $MEMCACHE;
		
		if (!CacheManager::$started) {
			LogManager::info(__CLASS__, "*** starting ***");
			CacheManager::$memcache = new Memcache;
			$result = CacheManager::$memcache->connect($MEMCACHE["HOST"], $MEMCACHE["PORT"]);
			if (!$result)
				throw new Exception("Could not locate memcache on ".$MEMCACHE["HOST"].":".$MEMCACHE["PORT"]);
			CacheManager::$started = true;
			register_shutdown_function(array('CacheManager', 'shutdown'));
		}
	}

	public static function shutdown() {
		LogManager::info(__CLASS__, "*** stopping ***");
		if (CacheManager::$started && CacheManager::$memcache) {
			CacheManager::$memcache->close();
		}
	}
	
	public static function get($key) {
		global $MEMCACHE;
		try {
			CacheManager::initCheck();
		} catch (Exception $e) {
			LogManager::error(__CLASS__, $e->getMessage());
			return false;
		}
		LogManager::trace(__CLASS__, "getting object with key=".$MEMCACHE["PREFIX"].$key);
		return CacheManager::$memcache->get($MEMCACHE["PREFIX"].$key);
	}
	
	// The default is 24 hours expiry for a cache element
	public static function set($key, $obj, $compressed=false, $duration=86400) {
		global $MEMCACHE;
		try {
			CacheManager::initCheck();
		} catch (Exception $e) {
			LogManager::error(__CLASS__, $e->getMessage());
			return false;
		}
		LogManager::trace(__CLASS__, "setting object with key=".$MEMCACHE["PREFIX"].$key);
		return CacheManager::$memcache->set($MEMCACHE["PREFIX"].$key, $obj, $compressed, $duration);
	}
	
	public static function replace($key, $obj, $compressed=false, $duration=86400) {
		global $MEMCACHE;
		try {
			CacheManager::initCheck();
		} catch (Exception $e) {
			LogManager::error(__CLASS__, $e->getMessage());
			return false;
		}
		LogManager::trace(__CLASS__, "replacing object with key=".$MEMCACHE["PREFIX"].$key);
		return CacheManager::$memcache->replace($MEMCACHE["PREFIX"].$key, $obj, $compressed, $duration);
	}
	
	public static function delete($key) {
		global $MEMCACHE;
		try {
			CacheManager::initCheck();
		} catch (Exception $e) {
			LogManager::error(__CLASS__, $e->getMessage());
			return false;
		}
		LogManager::trace(__CLASS__, "deleting object with key=".$MEMCACHE["PREFIX"].$key);
		return CacheManager::$memcache->delete($MEMCACHE["PREFIX"].$key);
	}
	
	public static function flush() {
		try {
			CacheManager::initCheck();
		} catch (Exception $e) {
			LogManager::error(__CLASS__, $e->getMessage());
			return false;
		}
		LogManager::trace(__CLASS__, "Flushing cache");
		return CacheManager::$memcache->flush();
	}
}

?>