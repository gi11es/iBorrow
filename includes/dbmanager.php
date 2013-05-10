<?php

/* 
 	Copyright (C) 2007 Gilles Dubuc.
 
 	This file is part of iBorrow.

    Grow Together is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    Grow Together is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Grow Together.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once (dirname(__FILE__).'/../settings.php');
require_once (dirname(__FILE__).'/logmanager.php');
require_once (dirname(__FILE__).'/constants.php');

require_once 'MDB2.php';

class DBManager {
	private static $started = false;
	private static $mysql_write = null;
	private static $mysql_read = null;

	private static function initCheck(){
		global $DATABASE_WRITE;
		global $DATABASE_READ;

		if (!DBManager::$started) {
			LogManager::trace(__CLASS__, "*** starting ***");
			DBManager::$mysql_write = MDB2::connect("mysqli://".$DATABASE_WRITE["USER"].":".$DATABASE_WRITE["PASSWORD"]."@".$DATABASE_WRITE["HOST"]."/".$DATABASE_WRITE["NAME"]."");
			DBManager::$mysql_write->setFetchMode(MDB2_FETCHMODE_ASSOC);
			DBManager::$mysql_read = MDB2::connect("mysqli://".$DATABASE_READ["USER"].":".$DATABASE_READ["PASSWORD"]."@".$DATABASE_READ["HOST"]."/".$DATABASE_READ["NAME"]."");
			DBManager::$mysql_read->setFetchMode(MDB2_FETCHMODE_ASSOC);

			register_shutdown_function(array('DBManager', 'shutdown'));
			DBManager::$started = true;
		}
	}

	public static function shutdown() {
		if (DBManager::$started) {
			LogManager::trace(__CLASS__, "*** stopping ***");
			DBManager::$mysql_write->disconnect();
			DBManager::$mysql_read->disconnect();
			DBManager::$started = false;
		}
	}	
	
	public static function prepareWriteMasterDB($query, $types) {
		DBManager::initCheck();
	
		return DBManager::$mysql_write->prepare($query, $types, MDB2_PREPARE_MANIP);
	}
	
	public static function prepareReadMasterDB($query, $types) {
		DBManager::initCheck();
	
		return DBManager::$mysql_write->prepare($query, $types, MDB2_PREPARE_RESULT);
	}
	
	public static function prepareWriteSlaveDB($query, $types) {
		DBManager::initCheck();
	
		return DBManager::$mysql_read->prepare($query, $types, MDB2_PREPARE_MANIP);
	}
	
	public static function prepareReadSlaveDB($query, $types) {
		DBManager::initCheck();
	
		return DBManager::$mysql_read->prepare($query, $types, MDB2_PREPARE_RESULT);
	}
	
	public static function queryMasterDB($query) {
		DBManager::initCheck();
	
		return DBManager::$mysql_write->query($query);
	}
	
	public static function querySlaveDB($query) {
		DBManager::initCheck();
		return DBManager::$mysql_read->query($query);
	}
	
	public static function insertidWriteDB() {
		DBManager::$mysql_write->loadModule('native');
		return DBManager::$mysql_write->native->getInsertID();
		
	}
	
	public static function createMasterDBTable($tablename, $columnarray) {
		global $TABLE;
		global $COLUMN;
		global $COLUMN_TYPE;
		global $COLUMN_TYPE_ATTRIBUTES;
		global $DATABASE_WRITE;
		DBManager::initCheck();
		
		$creationstring = "";
		foreach ($columnarray as $column) {
			$creationstring .= $COLUMN[$column]." ".$COLUMN_TYPE[$column].$COLUMN_TYPE_ATTRIBUTES[$column].", ";
		}
		// Strip the last ", "
		$creationstring = substr($creationstring, 0, -2);
		DBManager::$mysql_write->query("CREATE TABLE ".$DATABASE_WRITE["PREFIX"].$TABLE[$tablename]."( ".$creationstring." )");
	}
	
	public static function dropMasterDBTable($tablename) {
		global $DATABASE_WRITE;
		global $TABLE;
		DBManager::initCheck();
		
		DBManager::$mysql_write->query("DROP TABLE IF EXISTS ".$DATABASE_WRITE["PREFIX"].$TABLE[$tablename]);
	}
	
	public static function alterMasterDBTablePrimaryKey($tablename, $keyarray) {
		global $DATABASE_WRITE;
		global $TABLE;
		global $COLUMN;
		
		DBManager::initCheck();
		
		$creationstring = "";
		foreach ($keyarray as $column) {
			$creationstring .= $COLUMN[$column].", ";
		}
		// Strip the last ", "
		$creationstring = substr($creationstring, 0, -2);
		
		DBManager::$mysql_write->query("ALTER TABLE ".$DATABASE_WRITE["PREFIX"].$TABLE[$tablename]." DROP PRIMARY KEY");
		DBManager::$mysql_write->query("ALTER TABLE ".$DATABASE_WRITE["PREFIX"].$TABLE[$tablename]." ADD PRIMARY KEY ( ".$creationstring." )");
	}
}

?>