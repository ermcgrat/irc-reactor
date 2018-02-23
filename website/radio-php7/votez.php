<?php
require("config.php");
require("header.php"); 
?>

<?php

$songid = intval( $_GET['id'] );
if ( $songid <= 0 ) {
	die ('Please specify a valid Song ID.');
}

$link = new mysqli($db_host, $db_username, $db_password, $db_dbname, $db_port);
if ($link->connect_errno) {
	die("Failed to connect to database: (" . $link->connect_errno . ") " . $link->connect_error);
}

$sql = "Select id, artist, title, album From songlist Where ID = $songid";
$result = $link->query($sql);
$song = $result->fetch_assoc();

if ($song['id'] > 0) {
	$song_desc = $song['title'] . " by " . $song['artist'];
	if (!empty($song['album'])) {
		$song_desc .= " (" . $song['album'] . ")";
	}
} else {
	die ('Please specify a valid Song ID.');
}

?>

<table border="0" width="98%" cellspacing="0" cellpadding="4">
  <tr bgcolor="#002E5B">
    <td colspan="3" nowrap align="left">
      <b><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF">Voting History for <?php echo $song_desc; ?></font></b>
    </td>
  </tr>
  <tr bgcolor="#dadada">
    <td>Host Mask</td>
    <td>Rating</td>
    <td>Timestamp</td>
  </tr>

  <?php
	
	$color = $darkrow;

	$sql = "Select score, t_stamp, host From votez Where songID = $songid";
	$result = $link->query($sql);

    while ( $row = $result->fetch_assoc() ) {

        $score = $row['score'];
        $t_stamp = $row['t_stamp'];
        $host = $row['host'];
        if ( $color == "#F6F6F6") {
            $color = "#dadada";
        } else {
            $color = "#F6F6F6";
        }

        echo '<tr bgcolor="' . $color . '">';
        echo '<td>' . $host . '</td>';
        echo '<td>';
        for ( $x = 1; $x <= $score; $x++ ) {
            echo '<img style="border:none;" src="images/star.png">';
        }
        echo '</td>';
        echo '<td>' . $t_stamp . '</td>';

        echo '</tr>';

    }

  ?>


</table>
<br />
<a href="#" onClick="history.back(); return false;">Back to Playlist & Requests</a>
</body>
</html>
