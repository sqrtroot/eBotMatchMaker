<?php

/* Settings Page for eBotController */

$eBotMySQL = array("hostname"=>"localhost", "username"=>"root", "password"=>"", "database"=>"");
$challongeInfo = array("apikey"=>"", "tournamentid"=>"");
$eBotTeamSettings = array("teamflag"=>"AU", "seasonid"=>"3");
$eBotMatchSettings = array(
	"maxround"=>15,
	"rules"=>"esl5on5",
	"overtime_startmoney"=>10000,
	"overtime_mr"=>3,
	"overtime"=>1,
	"streamer"=>0,
	"knife"=>1
	);

$server_ips = array("0.0.0.0", "0.0.0.1");
$server_ports = array("27015", "27016");
$gotv_ports = array("27020", "27021");
$rcon_password = "";
$hostname_prefix = "Doesplay";

?>
