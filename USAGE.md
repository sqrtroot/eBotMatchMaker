# eBotMatchMaker Usage
First, create an instance of eBotController:

```php
// MySQL info to connect to the eBot Database
$MySQL = array(
    "hostname"=>"localhost",
    "username"=>"",
    "password"=>"",
    "database"=>"ebot"
);

// Challonge API Key and Tournament ID (in the URL)
$Challonge = array(
    "apikey"=>"",
    "tournamentid"=>""
);

// Settings for the eBot Season and the Team Flag to display on GOTV
$TeamSettings = array(
    "teamflag"=>"AU",
    "seasonid"=>""
);

// Settings for the matches, is set up for MR3 10k Overtime with ESL5on5 rules
$MatchSettings = array(
	"maxround"=>15,
	"rules"=>"esl5on5",
	"overtime_startmoney"=>10000,
	"overtime_mr"=>3,
	"overtime"=>1,
	"streamer"=>0,
	"knife"=>1
);

$ebot = new eBotController($MySQL, $Challonge, $TeamSettings, $MatchSettings);
```

You now need to connect to the MySQL database:

```php
$ebot->connectMySQL();
```

Once you have connected to the database you can create your matches and teams as well as add the servers:

```php
$ebot->createTeams($ebot->updateJSON());

$ebot->createMatches($ebot->updateJSON())

// Settings for the servers
$server_ips = array("192.168.1.1", "192.168.1.2");
$server_ports = array("27015");
$gotv_ports = array("27020");
$rcon_password = "MyCoolPassword";
$hostname_prefix = "Doesplay"; // Naming system for servers, will be named like "$prefix_$serverid_$portid" eg. "Doesplay_1_1"

$ebot->createServers($server_ips, $server_ports, $gotv_ports, $rcon_password, $hostname_prefix);

```

If you want to do your own queries there are commands built into eBotController that can also do this:

```php
$query = $ebot->query("SELECT * FROM `matchs`");

$numrows = $ebot->getNumRows($query);

$assoc = $ebot->getAssoc($query);
```


