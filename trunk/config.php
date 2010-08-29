<?php
/**
 * dG52 PHP SourceMod (SM) Plugin Updater
 *
 * @author Douglas Stridsberg
 * @url http://code.google.com/p/dg52-php-sm-plugin-updater/
 * @email doggie52@gmail.com
 */
 
	/**
	 * Configurable variables.
	 * Edit these to fit your environment.
	 */
	$serverAddress = '192.168.0.175';
	$serverPort = '27015';
	$serverRCONPassword = 'k?w+a7$PhuVU@r7*UC*-k&daswup!@Ux8D!j&Gu4w95WEw=a874a-uMUnuspEdrE';
	
	// The database you intend to use. 'sqlite' and 'mysql' are valid parameters.
	$preferredDatabase = "sqlite";
	
	// If you have selected 'mysql', please configure the following variables
	if($preferredDatabase == "mysql")
	{
		$mysqlHost = "localhost";
		$mysqlDatabase = "sm_plugin_updater";
		$mysqlUsername = "root";
		$mysqlPassword = "Password123";
	}
	/**
	 * End of configurable variables.
	 */

?>