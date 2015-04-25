<?php

function curl_get_contents($url)
{
  $curl = curl_init($url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  $data = curl_exec($curl);
  curl_close($curl);
  return $data;
}

class ebotmmFunc {
	public function ebotMySQL($array) {
		mysql_connect($array['hostname'], $array['username'], $array['password']) or die("Failed to connect to the MySQL database! Error: " . mysql_error());
		mysql_select_db($array['database']) or die("Could not select the database! Error: " . mysql_error());
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
	
	public function updateJSON($tourneyid, $apikey) {
		$tournament = curl_get_contents("http://api.challonge.com/v1/tournaments/" . $tourneyid . ".json?api_key=" . $apikey . "&include_participants=1&include_matches=1");
		return json_decode($tournament);
	}
}

?>
    
