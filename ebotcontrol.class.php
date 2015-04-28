<?php

//include ('Encryption.php');

/*

ebotcontrol.class.php

Contains functions for eBot to Challonge and Vice Versa

*/


class eBotController {
	public $eBotMySQL = null;
	public $challongeInfo = null;
	public $eBotTeamSettings = array("teamflag"=>"AU", "seasonid"=>"1");
	public $eBotMatchSettings = null;

	public $MySQLcon = null;

	public function __construct($emsql, $chinf, $ebteam, $ebmatch) {
		$this->eBotMySQL = $emsql;
		$this->challongeInfo = $chinf;
		$this->eBotTeamSettings = $ebteam;
		$this->eBotMatchSettings = $ebmatch;
	}

	public function connectMySQL() {
		$con = mysql_connect($this->eBotMySQL['hostname'], $this->eBotMySQL['username'], $this->eBotMySQL['password']) or die(mysql_error());
		$this->MySQLcon = $con;
		$db = mysql_select_db($this->eBotMySQL['database']);
		$this->query("DELETE * FROM `matchmaker`");
		return $con;
	}

	public function query($query) {
		$query = mysql_query($query) or die(mysql_error());
		return $query;
	}

	public function getAssoc($query) {
		$assoc = mysql_fetch_assoc($query);
		return $assoc;
	}

	public function getNumRows($query) {
		$numrow = mysql_num_rows($query);
		return $numrow;
	}

	public function createServers($server_ips, $server_ports, $gotv_ports, $rcon_password, $hostname_prefix) {
		foreach($server_ips as $key => $value) {
			foreach($server_ports as $key2 => $value2) {
				$server_ip = $value . ":" . $value2;
				$gotv_ip = $value . ":" . $gotv_ports[$key2];
				$key_1 = $key + 1;
				$key2_1 = $key2 + 1;
				$hostname = $hostname_prefix . "_" . $key_1 . "_" . $key2_1;
				echo $hostname . ": " . $server_ip . "; " . $gotv_ip . "\r\n";

				$this->query("INSERT INTO `servers` (`id`, `ip`, `rcon`, `hostname`, `tv_ip`, `created_at`, `updated_at`) VALUES (NULL, '" . $server_ip . "', '" . $rcon_password . "', '" . $hostname . "', '" . $gotv_ip . "', CURRENT_DATE(), CURRENT_DATE())");
			}
		}
	}

	public function createTeams($teamjson) {
		foreach($teamjson->tournament->participants as &$value) {   
			$teamname = $value->participant->name;
			$query_team = $this->query("INSERT INTO `teams` (`id`, `name`, `shorthandle`, `flag`, `link`, `created_at`, `updated_at`) VALUES (NULL, '" . $teamname . "', '', '" . $this->eBotTeamSettings['teamflag'] . "', NULL, CURRENT_DATE(), CURRENT_DATE())");
			echo "Team Name: " . $teamname . "; Team Flag: " . $this->eBotTeamSettings['teamflag'] . "\r\n";
			$query_getteam = $this->query("SELECT * FROM `teams` WHERE `name`='$teamname'");
			$assoc_getteam = $this->getAssoc($query_getteam);
			$query_seasonteam = $this->query("INSERT INTO `teams_in_seasons` (`id`, `season_id`, `team_id`, `created_at`, `updated_at`) VALUES (NULL, '" . $this->eBotTeamSettings['seasonid'] . "', '" . $assoc_getteam['id'] . "', CURRENT_DATE(), CURRENT_DATE())");
		}
	}

	public function checkForMatchStatus($json) {
		$query_getmatches = $this->query("SELECT * FROM `matchs`");

		while($row = $this->getAssoc($query_getmatches)) {
			$team1_name_db = $row['team_a_name'];
			$team2_name_db = $row['team_b_name'];
			$team1_id = null;
			$team2_id = null;
			$match_status = $row['status'];

			if($match_status == 13) {
				$team1_score = $row['score_a'];
				$team2_score = $row['score_b'];

				$matchid = null;
				$scores_csv = $team1_score . ":" . $team2_score;
				$winnerid = null;

				$json = $this->updateJSON();
				
				foreach($json->tournament->participants as &$value) {
					if($json->participant->name == $team1_name_db) {
						$team1_id = $json->participant->id;
					}

					if($json->participant->name == $team2_name_db) {
						$team2_id = $json->participant->id;
					}
				}

				foreach($json->tournament->matches as &$value) {
					if($value->match->player1_id == $team1_id && $value->match->player2_id == $team2_id) {
						$matchid = $value->match->id;
					}
				}

				if($team1_score > $team2_score) {
					$winnerid = $team1_id;
				}elseif($team1_score < $team2_score) {
					$winnerid = $team2_id;
				}

				//$scores = json_encode(array("scores_csv"=>$scores_csv, "winner_id"=>$winnerid));

				$update_match = $this->updateMatch($matchid, $scores_csv, $winnerid);
			}else{
				echo "Match has not finished yet, skipping...\r\n";
			}
		}
	}

	public function createMatches($json) {
		$team1_name;
		$team2_name;

		foreach($json->tournament->matches as &$value) {
			echo "Round " . $value->match->round . ", Match Identifier " . $value->match->identifier . ": ";

			if($value->match->state == "pending") {
				echo "Matches before have not finished. Skipping...\r\n";
			}else{
				$team1_id = $value->match->player1_id;
				$team2_id = $value->match->player2_id;
				$matchid = $value->match->id;

				$query_checkmatch = $this->query("SELECT * FROM `matchmaker` WHERE `matchid`='$matchid'");
				$numrow_checkmatch = $this->getNumRows($query_checkmatch);

				if($numrow_checkmatch == 0) {
					foreach($json->tournament->participants as &$team) {
						if($team->participant->id == $team1_id) {
							$team1_name = $team->participant->name;
						}

						if($team->participant->id == $team2_id) {
							$team2_name = $team->participant->name;
						}
					}
					
					echo $team1_name . " (" . $team1_id . ") vs. " . $team2_name . " (" . $team2_id . ")\r\n";
					$query_mminsert = $this->query("INSERT INTO `matchmaker`(`id`, `matchid`) VALUES (NULL, $matchid)");

					$query_team1 = $this->query("SELECT * FROM `teams` WHERE `name`='$team1_name'");
					$query_team2 = $this->query("SELECT * FROM `teams` WHERE `name`='$team2_name'");
					$assoc_team1 = $this->getAssoc($query_team1);
					$assoc_team2 = $this->getAssoc($query_team2);

					$randomString = $this->randomString();
					$creatematch = "INSERT INTO `matchs` (`id`, `ip`, `server_id`, `season_id`, `team_a`, `team_a_flag`, `team_a_name`, `team_b`, `team_b_flag`, `team_b_name`, `status`, `is_paused`, `score_a`, `score_b`, `max_round`, `rules`, `overtime_startmoney`, `overtime_max_round`, `config_full_score`, `config_ot`, `config_streamer`, `config_knife_round`, `config_switch_auto`, `config_auto_change_password`, `config_password`, `config_heatmap`, `config_authkey`, `enable`, `map_selection_mode`, `ingame_enable`, `current_map`, `force_zoom_match`, `identifier_id`, `startdate`, `auto_start`, `auto_start_time`, `created_at`, `updated_at`) VALUES (NULL, NULL, NULL, " . $this->eBotTeamSettings['seasonid'] . ", NULL, '" . $this->eBotTeamSettings['teamflag'] . "', '$team1_name', NULL, '" . $this->eBotTeamSettings['teamflag'] . "', '$team2_name', '0', NULL, '0', '0', '" . $this->eBotMatchSettings['maxround'] . "', '" . $this->eBotMatchSettings['rules'] . "', '" . $this->eBotMatchSettings['overtime_startmoney'] . "', '" . $this->eBotMatchSettings['overtime_mr'] . "', '0', '" . $this->eBotMatchSettings['overtime'] . "', '" . $this->eBotMatchSettings['streamer'] . "', '" . $this->eBotMatchSettings['knife'] . "', NULL, NULL, '" . $randomString . "', NULL, NULL, NULL, 'normal', NULL, NULL, NULL, NULL, CURRENT_DATE(), '0', '5', CURRENT_DATE(), CURRENT_DATE())";
					$query_creatematch = $this->query($creatematch);
					$query_matchid = $this->query("SELECT * FROM `matchs` WHERE `team_a_name`='$team1_name' AND `team_b_name`='$team2_name'");
					$assoc_matchid = $this->getAssoc($query_matchid);
					$matchid2 = $assoc_matchid['id'];
					$query_maps = $this->query("INSERT INTO `maps` (`id`, `match_id`, `map_name`, `score_1`, `score_2`, `current_side`, `status`, `maps_for`, `nb_ot`, `identifier_id`, `tv_record_file`, `created_at`, `updated_at`) VALUES ('" . $matchid2 . "', '" . $matchid2 . "', 'tba', '0', '0', 'ct', '0', 'default', '0', NULL, NULL, CURRENT_DATE(), CURRENT_DATE())");
					$query_updatemap = $this->query("UPDATE `matchs` SET `current_map`='$matchid2' WHERE `id`='$matchid2'");
				}else{
					echo "Already inserted match. Going onto next match...\r\n";
				}	
			}
		}
		echo "===============================================\r\n";
	}

	public function updateJSON() {
		$tournament = $this->curl_get_contents("http://api.challonge.com/v1/tournaments/" . $this->challongeInfo['tournamentid'] . ".json?api_key=" . $this->challongeInfo['apikey'] . "&include_participants=1&include_matches=1");
		return json_decode($tournament);
	}

	public function updateMatch($matchid, $scores_csv, $winnerid) {
		/*$tournament = $this->curl_get_contents("http://api.challonge.com/v1/tournaments/" . $this->challongeInfo['tournamentid'] . "/matches/" . $matchid . ".json?api_key=" . $this->challongeInfo['apikey'] . "&scores_csv=" . $scores_csv . "&winner_id=" . $winner_id);*/
		$tournament = $this->curl_get_contents("http://api.challonge.com/v1/tournaments/" . $this->challongeInfo['tournamentid'] . "/matches/" . $matchid . ".json?api_key=" . $this->challongeInfo['apikey'] . "&match[scores_csv]=" . $scores_csv . "&match[winner_id]=" . $matchid);
		return json_decode($tournament);
	}

	public function randomString($length = 10) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	function curl_get_contents($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		$data = curl_exec($curl);
		curl_close($curl);
		return $data;
	}

	public function eBotEncryptCommand($mid, $edata, $ip, $auth) {
		$data = $mid . " " . $edata . " " . $ip;
		$data = Encryption::encrypt($data, $auth, 256);
		$content = json_encode(array($data, $ip));
		return $content;
	}

	public function eBotSendCommand($message) {
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_sendto($socket, $message, strlen($message), 0, "", 12360);
	}
}


?>