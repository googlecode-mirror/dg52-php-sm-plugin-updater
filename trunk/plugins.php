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
	/**
	 * End of configurable variables.
	 */

	// Include the RCON class and other functions
	include "class.php";

?>
<head>
	<title>dG52 PHP SourceMod (SM) Plugin Updater</title>
	<style type="text/css">
		#data {
			font-family: Verdana;
			}
		#data small {
			color: grey;
			}
	</style>
</head>
<body>
	<div id="data">
		<h1>dG52 PHP SourceMod (SM) Plugin Updater</h1>
		<table>
			<tr>
				<td><b>Title</b></td>
				<td><b>Status</b></td>
				<td><b>Cached</b></td>
			</tr>
<?php

	// Start execution time counter
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$starttime = $mtime; 

	// Set the script execution limit
	set_time_limit("120");

	// Initiate the database
	$db = new SQLiteDatabase("db.sqlite");

	// Get a list of all plugins which already have a forum URL associated with them
	$database = array();
	$query = $db->query("SELECT * FROM plugins");
	while($query->valid())
	{
		$database[] = $query->current();
		// Move pointer to next row
		$query->next();
	}

	$Iplugins = array();
	// For each entry in the database, grab its corresponding array
	foreach($database as $id => $array)
	{
		// For each one of these arrays, get the type (name of column) and its value
		foreach($array as $type => $value)
		{
			// If the plugin's name is found, enter it into a new array
			if($type == "name")
			{
				$Iplugins['names'][] = $value;
			}
		}
	}

	// Connect to the server and issue the appropriate command
	$oTest = new clsRcon($serverAddress, $serverPort, $serverRCONPassword);
	$oTest->connect();
	$aResponse = $oTest->rcon('sm plugins list');

	// Match the list of plugins against patterns for title, version and author
	$plugins = $aResponse[0]['String1'];
	$pluginpattern = "/(?:\")(.*?)(?:\")(?: \()(.*?)(?:\))(?: by )(.*?)(?:\\n)/i";
	preg_match_all($pluginpattern, $plugins, $pluginarray);
	
	/**
	 * Note:
	 * Naming variables with 'I' indicates they represent Installed plugins
	 * Variables named 'N' are variables that are New or Not yet installed
	 */

	// Put it all in variables to facilitate reading
	$Iplugintitles = $pluginarray[1];
	$Ipluginversions = $pluginarray[2];
	$Ipluginauthors = $pluginarray[3];

	// Initiate the main loop that goes through every plugin returned from the RCON command
	foreach($Iplugintitles as $id => $title)
	{
		// Create a URL-friendly title for later
		$urltitle = str_replace(" ", "+", strtolower($title));
		
		// Make sure not to check core SourceMod plugins
		if($Ipluginauthors[$id] != "AlliedModders LLC")
		{
			// Unsert the forum URL variable
			unset($forumurl);
			// If the database is empty or if the plugin's title doesn't have a matching forum URL already
			if(empty($Iplugins['names'])||!in_array($Iplugintitles[$id], $Iplugins['names']))
			{
				$url = "http://www.sourcemod.net/plugins.php?title=".$urltitle."&search=1";
				$pluginpage = get_html_data($url);
				$pluginpage = str_replace("\n", "", $pluginpage);
				// Match the plugin search page against patterns for URL and author
				$searchpattern = "/(?:title=\"Approved\")(?:.*?)(?:\")(.*?)(?:\")(?:.*?)(?:<a href=\")(?:.*?)(?:\">)(.*?)(<\/a>)/i";
				preg_match_all($searchpattern, $pluginpage, $searcharray);
				
				// Start a counter to count what URL we are at
				$i = "0";
				// Unset the previous forum URL variable to ensure that we're grabbing a fresh page
				unset($forumurl);
				// Unset the handle-variable to ensure that it hasn't been set before.
				$handled = "";
				
				foreach($searcharray[2] as $searchauthor)
				{
					// If an author is found we do not want to overwrite him (the second alternative is not likely to be better than the first)
					if(!$handled == TRUE)
					{
						// Check if the plugin author's name is found within the forum author's name or vice versa
						if(preg_match("/$searchauthor/i", $Ipluginauthors[$id])||preg_match("/$Ipluginauthors[$id]/i", $searchauthor))
						{
							$forumurl = $searcharray[1][$i];
							// Insert the URL into a new row in the database along with the name of the plugin
							$db->query("INSERT INTO plugins (name, url)
								VALUES ('$title', '$forumurl')");
							$handled = TRUE;
						}
					}
					$i++;
				}
				// Unset the handle variable
				unset($handled);
				$cached = "no";
			}
			else
			{
				// If the plugin already exists in our database, simply use the stored URL
				$query = $db->query("SELECT url FROM plugins WHERE name = '$Iplugintitles[$id]'");
				$forumurl = $query->current();
				$forumurl = $forumurl['url'];
				$cached = "yes";
			}
			
			// Was a forum URL found? If so, determine plugin version
			if(isset($forumurl))
			{
				$forumpage = get_html_data($forumurl);
				$forumpage = str_replace("\n", "", $forumpage);
				// Match the plugin's forum page against a pattern for version
				$forumpattern = "/(?:Plugin Version)(?:.*?)(?:bold;\">)(.*?)(?:<\/div>)/i";
				preg_match_all($forumpattern, $forumpage, $forumarray);
				// Store in variables for easier reading
				$Npluginversion = $forumarray[1][0];
				$Ipluginversion = $Ipluginversions[$id];
				
				// Use version_compare() to compare versions
				// This might or might not work with all version numbers
				$versionstate = version_compare($Npluginversion, $Ipluginversion);
				
				if($versionstate == '0')
				{
					$status = "no update necessary ($Ipluginversion == $Npluginversion)";
				}
				elseif($versionstate == '1')
				{
					$status = "<a href=\"$forumurl\">update needed, click here</a> ($Ipluginversion < $Npluginversion)";
				}
				elseif($versionstate == '-1')
				{
					$status = "newest version is older than yours' - <a href=\"$forumurl\">an oversight might be necessary</a> ($Ipluginversion > $Npluginversion)";
				}
			}
			else
			{
				$status = "no matching forum URL was found (installed version: $Ipluginversion, author: $Ipluginauthors[$id])";
				// Allow users to add their own URL
				$forumurl = "addurl.php?name=$title";
			}
			echo "
			<tr>
				<td><b><a href=\"$forumurl\">$title</a></b></td>
				<td>$status</td>
				<td>$cached</td>
			</tr>";
			
			// Flush the output
			ob_flush();
			flush();
		}
	}

?>

		</table>

<?php

	// End page execution timer
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = ($endtime - $starttime);
	echo "<small>Script completed in ".substr($totaltime, 0, -7)." seconds.</small>";

?>
		<br />
		<small>Made by Doggie52 (doggie52@gmail.com). <a href="http://code.google.com/p/dg52-php-sm-plugin-updater/issues/entry">Report any issues you encounter!</a></small>
	</div>
</body>