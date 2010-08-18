<?php
include "class.php";

/**
 * Configuration! Only edit the following part.
 */
$serverAddress = '127.0.0.1';
$serverPort = '27015';
$serverRCONPassword = 'k?w+a7$PhuVU@r7*UC*-k&daswup!@Ux8D!j&Gu4w95WEw=a874a-uMUnuspEdrE';

?>
<style type="text/css">
	#data {
		font-family: Verdana;
		}
</style>
<div id="data">
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

// Put it all in variables to facilitate reading
$Iplugintitles = $pluginarray[1];
$Ipluginversions = $pluginarray[2];
$Ipluginauthors = $pluginarray[3];

// Initiate the main loop
foreach($Iplugintitles as $id => $title)
{
	// Create a URL-friendly title
	$urltitle = str_replace(" ", "+", $title);
	// Make sure not to check core SourceMod plugins
	if($Ipluginauthors[$id] != "AlliedModders LLC")
	{
		// Unsert the forum URL variable
		unset($forumurl);
		// If the database is empty or if the plugin's title doesn't have a matching forum URL already
		if(empty($Iplugins['names'])||!in_array($Iplugintitles[$id], $Iplugins['names']))
		{
			$url = "http://www.sourcemod.net/plugins.php?title=".$urltitle."&search=1";
			// $pluginpage = file_get_contents($url);
			$pluginpage = get_data($url, '8030-50000');
			$pluginpage = str_replace("\n", "", $pluginpage);
			// Match the plugin search page against patterns for URL and author
			$searchpattern = "/(?:title=\"Approved\")(?:.*?)(?:\")(.*?)(?:\")(?:.*?)(?:<a href=\")(?:.*?)(?:\">)(.*?)(<\/a>)/i";
			preg_match_all($searchpattern, $pluginpage, $searcharray);
			
			// Start a counter to check what URL we are at
			$i = "0";
			// Unset the previous forum URL variable to ensure that we're grabbing a fresh page
			unset($forumurl);
			foreach($searcharray[2] as $searchauthor)
			{
				// If an author is found we do not want to overwrite him (the second alternative is not likely to be better than the first)
				if(!$handled)
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
			$query = $db->query("SELECT url FROM plugins WHERE name = '$Iplugintitles[$id]'");
			$forumurl = $query->current();
			$forumurl = $forumurl['url'];
			$cached = "yes";
		}
		
		// Check the forum for the latest version if a URL was found
		if(isset($forumurl))
		{
			// Make sure to get only the part of the page concerning the plugin version
			// $forumpage = file_get_contents($forumurl, NULL, NULL, NULL, 21367);
			$forumpage = get_data($forumurl, '19003-21400');
			$forumpage = str_replace("\n", "", $forumpage);
			// Match the plugin's forum page against a pattern for version
			$forumpattern = "/(?:Plugin Version)(?:.*?)(?:bold;\">)(.*?)(?:<\/div>)/i";
			preg_match_all($forumpattern, $forumpage, $forumarray);
			// Store in variables for easier reading
			$Npluginversion = $forumarray[1][0];
			$Ipluginversion = $Ipluginversions[$id];
			
			// Remove all non-numbers from the versions in order to compare them (this breaks 'a' and 'b' releases and does not work when longer version numbers are converted into smaller ones)
			$fNpluginversion = ereg_replace("[^0-9]", "", $Npluginversion);
			$fIpluginversion = ereg_replace("[^0-9]", "", $Ipluginversion);
			
			if($fIpluginversion == $fNpluginversion)
			{
				$status = "no update necessary ($Ipluginversion == $Npluginversion)";
			}
			elseif($fIpluginversion < $fNpluginversion)
			{
				$status = "<a href=\"$forumurl\">update needed, click here</a> ($Ipluginversion < $Npluginversion)";
			}
			elseif($fIpluginversion > $fNpluginversion)
			{
				$status = "newest version is older than yours' - <a href=\"$forumurl\">an oversight might be necessary</a> ($Ipluginversion > $Npluginversion)";
			}
		}
		else
		{
			$status = "Error! No matching forum URL was found for the author '$Ipluginauthors[$id]'!";
			// Help users search for the plugin using Google
			$forumurl = "http://www.google.com/search?q=$urltitle+plugin+sourcemod";
		}
		echo "<tr><td><b><a href=\"$forumurl\">$title</a></b></td><td>$status</td><td>$cached</td></tr>";
	}
}

$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
echo "<small>Script completed in ".$totaltime." seconds.</small>"; 

?>
</div>