<?php

include ('ebotmm.class.php');

/*

eBotMatchMaker - Makes your matches for you

Once you have started your bracket on challonge run this script and it will take the matches and make them on eBot

*/

/* CHALLONGE SETTINGS */

$challongeAPIKey = "";
$tournament_id = "";

/* eBot SETTINGS */

$seasonID = "1";
$teamFlag = "AU";
$mysqlInfo = array("hostname"=>"", "username"=>"", "password"=>"", "database"=>"");
$ebotMatchSettings = array(
	"maxround"=>15,
	"rules"=>"esl5on5",
	"overtime_startmoney"=>10000,
	"overtime_mr"=>3,
	"overtime"=>1,
	"streamer"=>0,
	"knife"=>1,
	
	);

/* eBotMatchMaker - DO NOT EDIT FROM HERE - */
echo "=================== eBotMatchMaker ====================\r\n";
echo "Putting all teams into database and assigning season...\r\n";
echo "=======================================================\r\n";
/* Teams into Database and Season */

$ebotmm = new ebotmmFunc();

/* $tournament = curl_get_contents("http://api.challonge.com/v1/tournaments/" . $tournament_id . ".json?api_key=" . $challongeAPIKey . "&include_participants=1&include_matches=1");
$tournament_json = json_decode($tournament); */

$ebotmm->ebotMySQL($mysqlInfo);

$tournament_json = $ebotmm->updateJSON($tournament_id, $challongeAPIKey);

foreach($tournament_json->tournament->participants as &$value) {
	$teamname = $value->participant->name;
	$query_team = mysql_query("INSERT INTO `teams` (`id`, `name`, `shorthandle`, `flag`, `link`, `created_at`, `updated_at`) VALUES (NULL, '" . $teamname . "', '', '" . $teamFlag . "', NULL, CURRENT_DATE(), CURRENT_DATE())") or die("Error: Could not create team \"" . $teamname . "\". Error: " . mysql_error() . "\r\n");
	echo "Team Name: " . $teamname . "; Team Flag: " . $teamFlag . "\r\n";
	$query_team_get = mysql_query("SELECT * FROM `teams` WHERE `name`='$teamname'");
	$team_get_assoc = mysql_fetch_assoc($query_team_get);
	$season_team = mysql_query("INSERT INTO `teams_in_seasons` (`id`, `season_id`, `team_id`, `created_at`, `updated_at`) VALUES (NULL, '" . $seasonID . "', '" . $team_get_assoc['id'] . "', CURRENT_DATE(), CURRENT_DATE());") or die("Error: Could not assign team \"" . $teamname . "\" to the season. Error: " . mysql_error() . "\r\n");
	
}
echo "===========================================================\r\n";
echo "          Finished adding teams. Now adding matches        \r\n";
echo "===========================================================\r\n";


$team1_name;
$team2_name;

foreach($tournament_json->tournament->matches as &$value) {
	echo "Round " . $value->match->round . ", Match identifier " . $value->match->identifier . ": ";
	if($value->match->state == "pending") {
		echo "Matches before have not finished. Skipping...\r\n";
	}else{
		$team1_id = $value->match->player1_id;
		$team2_id = $value->match->player2_id;
		$matchid_2 = $value->match->id;
		
		foreach($tournament_json->tournament->participants as &$value2) {
			if($value2->participant->id == $team1_id) {
				$team1_name = $value2->participant->name;
			}
			
			if($value2->participant->id == $team2_id) {
				$team2_name = $value2->participant->name;
			}
		}
		
		echo $team1_name . " (" . $team1_id . ") vs. " . $team2_name . " (" . $team2_id . ")\r\n";
		
		$query_insertmm = mysql_query("INSERT INTO `matchmaker`(`id`, `matchid`) VALUES (NULL, $matchid_2)");
		
 		$query_team1 = mysql_query("SELECT * FROM `teams` WHERE `name`='$team1_name'");
		$query_team1_assoc = mysql_fetch_assoc($query_team1);
		$query_team2 = mysql_query("SELECT * FROM `teams` WHERE `name`='$team2_name'");
		$query_team2_assoc = mysql_fetch_assoc($query_team2);
		
		// Setting up map/match
		$randomString = $ebotmm->randomString();
		$creatematch = "INSERT INTO `matchs` (`id`, `ip`, `server_id`, `season_id`, `team_a`, `team_a_flag`, `team_a_name`, `team_b`, `team_b_flag`, `team_b_name`, `status`, `is_paused`, `score_a`, `score_b`, `max_round`, `rules`, `overtime_startmoney`, `overtime_max_round`, `config_full_score`, `config_ot`, `config_streamer`, `config_knife_round`, `config_switch_auto`, `config_auto_change_password`, `config_password`, `config_heatmap`, `config_authkey`, `enable`, `map_selection_mode`, `ingame_enable`, `current_map`, `force_zoom_match`, `identifier_id`, `startdate`, `auto_start`, `auto_start_time`, `created_at`, `updated_at`) VALUES (NULL, NULL, NULL, " . $seasonID . ", NULL, '$teamFlag', '$team1_name', NULL, '$teamFlag', '$team2_name', '0', NULL, '0', '0', '" . $ebotMatchSettings['maxround'] . "', '" . $ebotMatchSettings['rules'] . "', '" . $ebotMatchSettings['overtime_startmoney'] . "', '" . $ebotMatchSettings['overtime_mr'] . "', '0', '" . $ebotMatchSettings['overtime'] . "', '" . $ebotMatchSettings['streamer'] . "', '" . $ebotMatchSettings['knife'] . "', NULL, NULL, '" . $ebotmm->randomString() . "', NULL, NULL, NULL, 'normal', NULL, NULL, NULL, NULL, CURRENT_DATE(), '0', '5', CURRENT_DATE(), CURRENT_DATE())";
		$query_match = mysql_query($creatematch);
		$query_getmatchid = mysql_query("SELECT * FROM `matchs` WHERE `team_a_name`='$team1_name' AND `team_b_name`='$team2_name'");
		$assoc_matchid = mysql_fetch_assoc($query_getmatchid);
		$matchid = $assoc_matchid['id'];
		$query_maps = mysql_query("INSERT INTO `maps` (`id`, `match_id`, `map_name`, `score_1`, `score_2`, `current_side`, `status`, `maps_for`, `nb_ot`, `identifier_id`, `tv_record_file`, `created_at`, `updated_at`) VALUES ('" . $matchid . "', '" . $matchid . "', 'tba', '0', '0', 'ct', '0', 'default', '0', NULL, NULL, CURRENT_DATE(), CURRENT_DATE())") or die(mysql_error());
		$query_updatemap = mysql_query("UPDATE `matchs` SET `current_map` =  '" . $matchid . "' WHERE `id`=". $matchid);
	}
}
echo "=============================================================\r\n";
echo "Finished adding matches. Starting challonge update checker...\r\n";
echo "=============================================================\r\n";


while(true) {
	$team1_name2;
	$team2_name2;
	$tournament_json2 = $ebotmm->updateJSON($tournament_id, $challongeAPIKey);
	
	foreach($tournament_json2->tournament->matches as &$value) {
		echo "Round " . $value->match->round . ", Match identifier " . $value->match->identifier . ": ";
	
		if($value->match->state == "pending") {
			echo "Matches before have not finished. Skipping...\r\n";
		}else{
			$team1_id2 = $value->match->player1_id;
			$team2_id2 = $value->match->player2_id;
			$matchid2_2 = $value->match->id;
			
			$query_checkmatch = mysql_query("SELECT * FROM `matchmaker` WHERE `matchid`='$matchid2_2'");
			$numrow_checkmatch = mysql_num_rows($query_checkmatch);
			
			if($numrow_checkmatch == 0) {
				foreach($tournament_json2->tournament->participants as &$value2) {
					if($value2->participant->id == $team1_id2) {
						$team1_name2 = $value2->participant->name;
					}
					
					if($value2->participant->id == $team2_id2) {
						$team2_name2 = $value2->participant->name;
					}
				}
				
				echo $team1_name2 . " (" . $team1_id2 . ") vs. " . $team2_name2 . " (" . $team2_id2 . ")\r\n";
				$query_insertmm = mysql_query("INSERT INTO `matchmaker`(`id`, `matchid`) VALUES (NULL, $matchid2_2)");
				
				$query_team1_2 = mysql_query("SELECT * FROM `teams` WHERE `name`='$team1_name2'");
				$query_team1_2_assoc = mysql_fetch_assoc($query_team1_2);
				$query_team2_2 = mysql_query("SELECT * FROM `teams` WHERE `name`='$team2_name2'");
				$query_team2_2_assoc = mysql_fetch_assoc($query_team2_2);
				
				$randomString = $ebotmm->randomString();
				$creatematch = "INSERT INTO `matchs` (`id`, `ip`, `server_id`, `season_id`, `team_a`, `team_a_flag`, `team_a_name`, `team_b`, `team_b_flag`, `team_b_name`, `status`, `is_paused`, `score_a`, `score_b`, `max_round`, `rules`, `overtime_startmoney`, `overtime_max_round`, `config_full_score`, `config_ot`, `config_streamer`, `config_knife_round`, `config_switch_auto`, `config_auto_change_password`, `config_password`, `config_heatmap`, `config_authkey`, `enable`, `map_selection_mode`, `ingame_enable`, `current_map`, `force_zoom_match`, `identifier_id`, `startdate`, `auto_start`, `auto_start_time`, `created_at`, `updated_at`) VALUES (NULL, NULL, NULL, " . $seasonID . ", NULL, '$teamFlag', '$team1_name2', NULL, '$teamFlag', '$team2_name2', '0', NULL, '0', '0', '" . $ebotMatchSettings['maxround'] . "', '" . $ebotMatchSettings['rules'] . "', '" . $ebotMatchSettings['overtime_startmoney'] . "', '" . $ebotMatchSettings['overtime_mr'] . "', '0', '" . $ebotMatchSettings['overtime'] . "', '" . $ebotMatchSettings['streamer'] . "', '" . $ebotMatchSettings['knife'] . "', NULL, NULL, '" . $ebotmm->randomString() . "', NULL, NULL, NULL, 'normal', NULL, NULL, NULL, NULL, CURRENT_DATE(), '0', '5', CURRENT_DATE(), CURRENT_DATE())";
				$query_match = mysql_query($creatematch);
				$query_getmatchid = mysql_query("SELECT * FROM `matchs` WHERE `team_a_name`='$team1_name2' AND `team_b_name`='$team2_name2'");
				if(!$query_getmatchid) {echo mysql_error(); die;}
				$assoc_getmatchid = mysql_fetch_assoc($query_getmatchid);
				$matchid_03 = $assoc_getmatchid['id'];
				$query_maps = mysql_query("INSERT INTO `maps` (`id`, `match_id`, `map_name`, `score_1`, `score_2`, `current_side`, `status`, `maps_for`, `nb_ot`, `identifier_id`, `tv_record_file`, `created_at`, `updated_at`) VALUES ('" . $matchid_03 . "', '" . $matchid_03 . "', 'tba', '0', '0', 'ct', '0', 'default', '0', NULL, NULL, CURRENT_DATE(), CURRENT_DATE())") or die(mysql_error());
				$query_updatemap = mysql_query("UPDATE  `matchs` SET `current_map` =  '" . $matchid_03 . "' WHERE `id`=". $matchid_03);
			}else{
				echo "Already done match, going onto the next one...\r\n";
			}
		}
	}
	echo "===================================================\r\n";
	echo "Finished with updating matches, waiting 1 minute...\r\n";
	echo "===================================================\r\n";
	
	sleep(60);
}

?>


