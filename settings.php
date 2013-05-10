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

$api_key = '3ae82599f92f956d2f9f5f4539086cc5';
$secret  = '';
$growthid = "46c03b0d343a789e073f1aba3a6fa6812925817b";

$MEMCACHE["HOST"] = 'localhost';
$MEMCACHE["PORT"] = 11211;
$MEMCACHE["PREFIX"] = 'iBorrow-';

$CURRENT_LOG_LEVEL = $LOG_LEVEL["TRACE"];
$LOG_TIME_FORMAT = "Y-m-d H:i:s";

$LOG_FILE_PATH = "/home/daruma/logs/iborrow/";
$LOG_FILE["CacheManager"] = $LOG_FILE_PATH."CacheManager-".date("Y-m-d").".log";
$LOG_FILE["DBManager"] = $LOG_FILE_PATH."DBManager-".date("Y-m-d").".log";
$LOG_FILE["URLManager"] = $LOG_FILE_PATH."URLManager-".date("Y-m-d").".log";
$LOG_FILE["User"] = $LOG_FILE_PATH."User-".date("Y-m-d").".log";
$LOG_FILE["Feed"] = $LOG_FILE_PATH."Feed-".date("Y-m-d").".log";
$LOG_FILE["Removal"] = $LOG_FILE_PATH."Removal-".date("Y-m-d").".log";

$DATABASE_WRITE["HOST"] = "localhost";
$DATABASE_WRITE["USER"] = "daruma_iborrow";
$DATABASE_WRITE["PASSWORD"] = "";
$DATABASE_WRITE["NAME"] = "daruma_iborrow";
$DATABASE_WRITE["PREFIX"] = "iborrow_";

$DATABASE_READ["HOST"] = "localhost";
$DATABASE_READ["USER"] = "daruma_iborrow";
$DATABASE_READ["PASSWORD"] = "";
$DATABASE_READ["NAME"] = "daruma_iborrow";
$DATABASE_READ["PREFIX"] = "iborrow_";

$SHARED_ITEMS_PAGE_SIZE = 5;

$INSTALL_URL = "http://www.facebook.com/apps/application.php?api_key=".$api_key;

?>