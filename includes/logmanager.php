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

require_once (dirname(__FILE__)."/../settings.php");
require_once (dirname(__FILE__).'/constants.php');

class LogManager {
	private static function log($level, $classname, $message) {
		global $CURRENT_LOG_LEVEL;
		global $LOG_LEVEL;
		global $LOG_FILE;
		global $LOG_TIME_FORMAT;
		global $LOG_FILE_PATH;
		
		if ($CURRENT_LOG_LEVEL <= $LOG_LEVEL[$level]) {
			if (!file_exists($LOG_FILE[$classname])) {
				$fp = fopen($LOG_FILE[$classname], "w+");
				fclose($fp);
				chmod($LOG_FILE[$classname], 0666);
			}
			$fp = fopen($LOG_FILE[$classname], "a+");
			fwrite($fp, date($LOG_TIME_FORMAT)." ".$level." ".$message."\n");
			fclose($fp);
		}
	}
	
	public static function trace($classname, $message) {
		LogManager::log("TRACE", $classname, $message);
	}
	
	public static function debug($classname, $message) {
		LogManager::log("DEBUG", $classname, $message);
	}
	
	public static function info($classname, $message) {
		LogManager::log("INFO", $classname, $message);
	}
	
	public static function error($classname, $message) {
		LogManager::log("ERROR", $classname, $message);
	}
}

?>