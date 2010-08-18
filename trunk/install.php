<?php

/**
 * dG52 PHP SourceMod (SM) Plugin Updater
 *
 * @author Douglas Stridsberg
 * @url http://code.google.com/p/dg52-php-sm-plugin-updater/
 * @email doggie52@gmail.com
 */

	$db = new SQLiteDatabase("db.sqlite");
	echo "Database created.<br />";

	$result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='plugins'");
	if($result->numRows() == '0')
	{
		$db->query("BEGIN;
				CREATE TABLE plugins (name CHAR(255) PRIMARY KEY, url CHAR(255));
				COMMIT;");
		echo "Table `plugins` created.";
	}
	else
	{
		echo "Table `plugins` already exists!";
	}

?>