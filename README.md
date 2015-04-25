# eBotMatchMaker
Takes matches from Challonge and puts them directly into eBot

Make sure to set your Challonge API key, MySQL information, Challonge Bracket ID and eBot SeasonID. If you want to change any match settings do that as well.

Make sure you create the table `matchmaker` inside your eBot database with this layout:
```
`matchmaker`:

+---------+---------+------+-----+---------+----------------+
| Field   | Type    | Null | Key | Default | Extra          |
+---------+---------+------+-----+---------+----------------+
| id      | int(11) | NO   | PRI | NULL    | auto_increment |
| matchid | int(11) | NO   |     | NULL    |                |
+---------+---------+------+-----+---------+----------------+
```
