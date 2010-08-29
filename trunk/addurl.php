<?php
/**
 * dG52 PHP SourceMod (SM) Plugin Updater
 *
 * @author Douglas Stridsberg
 * @url http://code.google.com/p/dg52-php-sm-plugin-updater/
 * @email doggie52@gmail.com
 */

	// Include the class
	include "class.php";
	
	// Include the configuration
	include "config.php";

	// Initiate database
	if($preferredDatabase == "sqlite")
	{
		$db = new SQLiteDatabase("db.sqlite");
	}
	elseif($preferredDatabase == "mysql")
	{
		$socket = mysql_connect($mysqlHost, $mysqlUsername, $mysqlPassword) or die(mysql_error());
		mysql_select_db($mysqlDatabase, $socket);
	}
	
	// If the form has not been submitted
	if(!isset($_GET['submit']))
	{
		echo "<form method=\"GET\">
				<table>
				<tr><td>Plugin's exact name:</td><td><input type=\"text\" name=\"name\" value=\"".$_GET['name']."\" /></td></tr>
				<tr><td>Plugin's forum URL:</td><td><input type=\"text\" name=\"url\" /></td></tr>
				<tr><td><input type=\"submit\" name=\"submit\" /></td></tr>
				</table>
			</form>";
	}
	else
	{
		// Secure the input
		$name = secure_sql_input($_GET['name']);
		$url = secure_sql_input($_GET['url']);
		// Insert the URL into a new row in the database along with the name of the plugin
		$sql = "INSERT INTO plugins (name, url) VALUES ('".$name."', '".$url."')";
		if($preferredDatabase == "sqlite")
		{
			$db->query($sql);
		}
		elseif($preferredDatabase == "mysql")
		{
			mysql_query($sql, $socket);
		}
		echo "<p>Plugin URL added, please <a href=\"plugins.php\">re-run the updater</a>!";
	}

?>