<?php

include('ebotcontrol.class.php');
include('settings.php');

/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

/* Variables - DO NOT CHANGE!! */

$MySQL = null;

echo "===============================================\r\n";
echo "=               eBotMatchMaker v2             =\r\n";
echo "=   Created for Incept eSports and Doesplay   =\r\n";
echo "===============================================\r\n";
echo "\r\n";
echo "===============================================\r\n";

$ebot = new eBotController($eBotMySQL, $challongeInfo, $eBotTeamSettings, $eBotMatchSettings);

echo "=       Created eBotController Instance!      =\r\n";
echo "===============================================\r\n";
echo "\r\n";
echo "===============================================\r\n";
echo "=           Connecting to MySQL...            =\r\n";

$MySQL = $ebot->connectMySQL();

echo "=        Connected to the MySQL server!       =\r\n";
echo "===============================================\r\n";
echo "\r\n";
echo "===============================================\r\n";
echo "=   Inserting all teams into the database...  =\r\n";

$ebot->createTeams($ebot->updateJSON());

echo "===============================================\r\n";
echo "\r\n";
echo "===============================================\r\n";
echo "=        Adding all servers to eBot...        =\r\n";

$ebot->createServers($server_ips, $server_ports, $gotv_ports, $rcon_password, $hostname_prefix);

echo "===============================================\r\n";
echo "\r\n";

echo "=         Creating all matches now...         =\r\n";

$ebot->createMatches($ebot->updateJSON());

echo "===============================================\r\n";
echo "\r\n";
echo "===============================================\r\n";
echo "=  All matches have been made. Checking for   =\r\n";
echo "=         updates every 30 seconds...         =\r\n";
echo "===============================================\r\n";

sleep(30);
while(true) {
	$ebot->createMatches($ebot->updateJSON());
	sleep(30);
}



?>
