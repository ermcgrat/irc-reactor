<?php
require("config.php"); 
require("header.php"); 
?>
<?php
    // determine timeout for javascript refresh
    $refreshURL = "?buster=".date('dhis').rand(1,1000);
?>

<SCRIPT LANGUAGE="JavaScript">
<!---
 var refreshID = "";
// 1 minute refresh
 refreshID = setTimeout("DoRefresh()", 60000);

function DoRefresh()
{
  document.location.href = '<?php echo $refreshURL; ?>';
}
//--->
</SCRIPT>

<?php

function Text2Html($txt)
{
	return str_replace("\n","\n<br>",$txt);
}

$link = new mysqli($db_host, $db_username, $db_password, $db_dbname, $db_port);
if ($link->connect_errno) {
	die("Failed to connect to database: (" . $link->connect_errno . ") " . $link->connect_error);
}

$sql = "SELECT songlist.*, historylist.listeners as listeners, historylist.requestID as requestID, historylist.date_played as starttime FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC limit 0,1";
$result = $link->query($sql);
$row = $result->fetch_assoc();

if ( $row['listeners'] > 0 ) {
	echo "There are currently " . $row['listeners'] . " listeners tuned into this station!<br><br>";
}

?>



<table border="0" width="98%" cellspacing="0" cellpadding="4"><!--lol-->
  <tr bgcolor="green">
    <td colspan="2" nowrap align="left">
      <p><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF"><b>[On Air] Currently Playing:</b></font>
    </td>
	<td colspan="3" nowrap align="center">
      <p><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF"><b>Links</b></font>
    </td>
    <td nowrap align="left">
      <p><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF"><b>Album</b></font>
    </td>
	<td nowrap align="Right">
      <p><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF"><b>Time</b></font>
    </td>
  </tr>

  <?php

    $requestid = $row['requestID']; // store for dedication

    echo '<tr bgcolor="' . $darkrow . '">';
    echo '<td valign="middle" width="1%">';
    if ( !empty($row['picture']) ) {
        echo '<img src="pictures/' . $row['picture'] . '" width="60" height="60" border="0">';
    }
    echo '</td>';

    echo '<td><font size="2" color="#003366"><small>';

    if(empty($row["artist"]))
        echo $row["title"];
    else
        echo $row["artist"] . " - ". $row["title"];

    if ( $row['requestID'] != 0 ) {
        echo " ~requested~ ";
    }

    echo '</small></font></td>';
    
        if (!empty($row['buycd'])) {
            $buy_url = $row['buycd'];
        } else {
            $buy_url = "http://www.amazon.com";
        }    

    echo '<td nowrap width="1%">';
    echo '&nbsp;';
    echo '</td>';
    
        if (!empty($row['website'])) {
            $website_url = $row['website'];
        } else {
            $website_url = "http://www.google.com/search?q=" . urlencode ($row['artist']);
        }    

    echo '<td nowrap width="1%">';
    echo '<p align="center"><font size="2" color="#003366"><a href="' . $website_url . '" target="_blank"><img src="images/home.gif" alt="Artist homepage" border="0"></a></font>';
    echo '</td>';

	echo '<td nowrap align="center" nowrap width="1%">';
    echo '<font size="2" color="#003366"><a href="javascript:songinfo(' . $row["ID"] . ')"><img src="images/info.gif" alt="Song information" border="0"></a></font>';
    echo '</td>';

    echo '<td nowrap><font color="#003366" size="2"><small>' . $row["album"] . '</small></font></td>';

    echo '<td nowrap>';

    $ss = round($row["duration"] / 1000);
    $mm = (int)($ss / 60);
    $ss = ($ss % 60);
    if($ss<10) $ss="0$ss";

    echo '<p align="right"><font color="#003366" size="2"><small><strong>' . "$mm:$ss" . '</strong></small></font>';
    echo '</td></tr>';

    // end currently playing

    // start queue list

    $sql = "SELECT songlist.*, queuelist.requestID as requestID FROM queuelist, songlist WHERE (queuelist.songID = songlist.ID)  AND (songlist.songtype='S') AND (songlist.artist <> '') ORDER BY queuelist.sortID ASC limit 0,5";
	$result = $link->query($sql);

    $i = 0;

    while ( $row = $result->fetch_assoc() ) {
        if ( $i == 0 ) {
            echo '<tr bgcolor="' . $lightrow . '"><td colspan="7"><b><font size="2" color="#DF7401">Coming Up Next: </font></b><font size="2" color="003366"><b>';

            if(empty($row["artist"]))
                echo 'Unknown';
            else
                echo $row['artist'];
			
			echo ' - ' . $row['title'];

            if ( $row['requestID'] != 0 )
                echo " (requested)";

        } else {
            echo ', ';

            if(empty($row["artist"]))
                echo 'Unknown';
            else
                echo $row['artist'];
			
			echo ' - ' . $row['title'];

            if ( $row['requestID'] != 0 )
                echo " (requested) ";
        }
        $i++;
    }

    if ( $i > 0)
        echo "</b></font></td></tr>";

    // end queue list



    // start recently played

  ?>

  <tr bgcolor="#0066CC">
    <td colspan="7" nowrap>
      <p align="left"><b><font size="1" face="Verdana, Arial, Helvetica, sans-serif" color="#FFFFFF">Recently
        Played Songs:</font></b>
    </td>
  </tr>

  <?php

    $sql = "SELECT songlist.*, historylist.listeners as listeners, historylist.requestID as requestID, historylist.date_played as starttime FROM historylist,songlist WHERE (historylist.songID = songlist.ID) AND (songlist.songtype='S') ORDER BY historylist.date_played DESC limit 1,10";
    $result = $link->query($sql);

    $rowcolor = $darkrow;

    while ($row = $result->fetch_assoc() ) {

        echo '<tr bgcolor="' . $rowcolor . '">';
        echo '<td valign="middle" width="1%">';
        if ( !empty($row['picture']) ) {
            echo '<img src="pictures/' . $row['picture'] . '" width="60" height="60" border="0">';
        }
        echo '</td>';

        echo '<td><font size="2" color="#003366"><small>';

        if(empty($row["artist"]))
            echo $row["title"];
        else
            echo $row["artist"] . " - ". $row["title"];

        if ( $row['requestID'] != 0 ) {
            echo " ~requested~ ";
        }

        echo '</small></font></td>';

        if (!empty($row['buycd'])) {
            $buy_url = $row['buycd'];
        } else {
            $buy_url = "http://www.amazon.com";
        }

        echo '<td nowrap width="1%">';
        echo '&nbsp;';
        echo '</td>';

        if (!empty($row['website'])) {
            $website_url = $row['website'];
        } else {
            $website_url = "http://www.google.com/search?q=" . urlencode($row['artist']);
        }

        echo '<td nowrap width="1%">';
        echo '<p align="center"><font size="2" color="#003366"><a href="' . $website_url . '" target="_blank"><img src="images/home.gif" alt="Artist homepage" border="0"></a></font>';
        echo '</td>';

    	echo '<td nowrap align="center" nowrap width="1%">';
        echo '<font size="2" color="#003366"><a href="javascript:songinfo(' . $row["ID"] . ')"><img src="images/info.gif" alt="Song information" border="0"></a></font>';
        echo '</td>';

        echo '<td nowrap><font color="#003366" size="2"><small>' . $row["album"] . '</small></font></td>';

        echo '<td nowrap>';

        $ss = round($row["duration"] / 1000);
        $mm = (int)($ss / 60);
        $ss = ($ss % 60);
        if($ss<10) $ss="0$ss";

        echo '<p align="right"><font color="#003366" size="2"><small><strong>' . "$mm:$ss" . '</strong></small></font>';
        echo '</td></tr>';

        if ($rowcolor == $darkrow)
            $rowcolor = $lightrow;
        else
            $rowcolor = $darkrow;


    }

    // end recently played
  ?>

</table>

</td><td valign='top' align='center'>
<!--right side-->

<?php
if($requestid>0)
{
 $requestid = intval($requestid); //Make sure it is an integer to avoid SQL injection
$sql = "SELECT name, msg FROM requestlist WHERE (ID = $requestid) Limit 0,1";
$result = $link->query($sql);
$row = $result->fetch_assoc();
if (!empty($row['name']))
{
?>
<table width="100%" bgcolor="<?php echo $lightrow; ?>" border="0" cellspacing="0" cellpadding="5">
<tr bgcolor="<?php echo $darkrow; ?>"><td nowrap><b><font size="1" face="Verdana, Arial, Helvetica, sans-serif" color="#555555">Dedication</font></b></td></tr>
<tr><td>
<font size="2" face="Verdana, Arial, Helvetica, sans-serif" color="#464646"><small>
<?php
$row['msg'] = stripslashes($row['msg']);
echo Text2Html(trim($row['msg'])); 
?>
<br>
</small></font>
<br>
<font size="2" face="Verdana, Arial, Helvetica, sans-serif" color="#003366"><small>Dedicated by <br>&nbsp;&nbsp;&nbsp;<b><?php echo stripslashes($row['name']); ?></b></small></font>

</td></tr>
</table>
<br>
<?php
}}
?>


<table width="100%" bgcolor="<?php echo $lightrow; ?>" border="0" cellspacing="0" cellpadding="5">
<tr bgcolor="<?php echo $darkrow; ?>"><td nowrap><b><font size="1" face="Verdana, Arial, Helvetica, sans-serif" color="#555555">Top 10 Requests</font></b></td></tr>
<tr><td nowrap>
<?php
    $this_date = "'" . date("Y") . "-" . date("m") . "-01'";
    $sql = "SELECT songlist.ID, songlist.title, songlist.artist, count(songlist.ID) as cnt FROM requestlist, songlist WHERE (requestlist.songID = songlist.ID) AND (requestlist.code=200) AND (requestlist.t_stamp>=$this_date) GROUP BY songlist.ID, songlist.artist, songlist.title ORDER BY cnt DESC Limit 0,10";
    $result = $link->query($sql);
    $i = 1;
    while ( $row = $result->fetch_assoc() ) {

         echo '<font size="2" color="#003366"><small>' . $i . '.';
         echo '<a href="javascript:songinfo(' . $row["ID"] . ')">' . $row["artist"] . '</a></small></font> <font size="2" color="#9F9F9F"><small>(' . $row["cnt"] . ')</small></font><br>';
    	 echo '<font size="2" color="#003366"><small>&nbsp;&nbsp;&nbsp;&nbsp;' . $row["title"] . '</small></font><br>';
        $i++;
    }
?>
</td></tr>
</table>
<br>

<?php 
	require("footer.php"); 
?>
