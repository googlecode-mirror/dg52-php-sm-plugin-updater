<?php

/**
 * dG52 PHP SourceMod (SM) Plugin Updater
 *
 * @author Douglas Stridsberg
 * @url http://code.google.com/p/dg52-php-sm-plugin-updater/
 * @email doggie52@gmail.com
 */

	// Include the configuration
	include "config.php";
	
	if($preferredDatabase == "sqlite")
	{
		$db = new SQLiteDatabase("db.sqlite");
		echo "<strong>SQLite</strong> database created.<br />";
		
		// Does the table already exist?
		$result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugins'");
		if($result->numRows() == '0')
		{
			// Create the table and the columns
			$db->query("BEGIN;
					CREATE TABLE `plugins` (name CHAR(255) PRIMARY KEY, url CHAR(255));
					COMMIT;");
			echo "Table `plugins` created.";
		}
		else
		{
			echo "Table `plugins` already exists!";
		}
	}
	elseif($preferredDatabase == "mysql")
	{
		$socket = mysql_connect($mysqlHost, $mysqlUsername, $mysqlPassword) or die(mysql_error());
		mysql_select_db($mysqlDatabase, $socket);
		
		// Does the table already exist?
		$result = mysql_query("SELECT * FROM `plugins` LIMIT 0,1");
		if(!$result)
		{
			// Create the table and the columns
			mysql_query("CREATE TABLE `plugins` (name CHAR(255) PRIMARY KEY, url CHAR(255))", $socket) or die(mysql_error());
			echo "Table `plugins` created.";
		}
		// If $result is indeed a resource, this means the table exists
		else
		{
			echo "Table `plugins` already exists!";
		}
	}
?>