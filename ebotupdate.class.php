<?php

include('ebotcontrol.class.php');

/* eBotUpdate.class.php - Takes scores from eBot and updates them on Challonge */

class eBotUpdate {
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
}

?>