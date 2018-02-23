<?php
 require("config.php");

$link = new mysqli($db_host, $db_username, $db_password, $db_dbname, $db_port);
if ($link->connect_errno) {
	die("Failed to connect to database: (" . $link->connect_errno . ") " . $link->connect_error);
}

 $where = " WHERE (songtype='S') AND (status=0) ";

if(!empty($_GET['start'])) {
	$start = intval($_GET['start']);
} else {
	$start = 0;
}
if(!empty($_GET['limit'])) {
	$limit = intval($_GET['limit']);
} else {
	$limit = 50;
}
if(!empty($_GET['search'])) {
	$search = $_GET['search'];
} else {
	$search = "";
}
if(!empty($_GET['letter'])) {
	$letter = $_GET['letter'];
} else {
	$letter = "";
}

 //########## BUILD SEARCH STRING ################

 if(!empty($_GET['search']))
 {

   $words = Array();
   $temp = explode(' ',$_GET['search']);
   reset($temp);
   while(list($key,$val) = each($temp))
   {
    $val = trim($val);
    if(!empty($val))
	 $words[] = $val;
   }


   $where2 = "";
   reset($words);
   while(list($key,$val) = each($words))
   {
     if(!empty($where2)) $where2 .= " OR ";
	   $val = "'%" . $link->real_escape_string($val) . "%'";
     $where2 .= " (title like $val) OR (artist like $val) OR (album like $val) ";
   }
   $where .= "AND ($where2) ";
}

 if(!empty($_GET['letter']))
 {
	 $letter = $_GET['letter'];
  $nextletter = chr(ord($letter)+1);
  if($letter=='0')
   $where .= " AND NOT((artist>='A') AND (artist<'ZZZZZZZZZZZ')) ";
  else
   {
    $where .= " AND ((artist>='$letter') AND (artist<'$nextletter')) ";
   }
 }
 else
 {
	$letter="";
 }

 //########## =================== ################

 //Calculate total

$sql = "SELECT count(*) as cnt FROM songlist $where ";
$result = $link->query( $sql );
$row = $result->fetch_assoc();
$cnt = $row["cnt"];

 //Now grab a section of that
$sql = "SELECT * FROM songlist $where ORDER BY artist ASC, title ASC LIMIT $start, $limit";
$result = $link->query( $sql );

 $first = $start+1;
 $last  = min($cnt,$start+$limit);
 $rc    = $start;

 $prevlnk = "";
 $nextlnk = "";
 if($cnt>0)
 {

  $searchstr = urlencode($search);
  $prev = max(0,$start-$limit);
  if($start>0)
    $prevlnk = "<a href='?start=$prev&limit=$limit&letter=$letter&search=$searchstr'>&lt;&lt; Previous</a>";

  $tmp = ($start+$limit);
  if($tmp<$cnt)
    $nextlnk = "<a href='?start=$tmp&limit=$limit&letter=$letter&search=$searchstr'>Next &gt;&gt;</a>";
 }

function PutSongRow($song)
{
 global $rc, $start, $darkrow, $lightrow, $link;

 $rc++;
 $bgcolor = $darkrow;
 if(($rc % 2)==0) $bgcolor = $lightrow;

?>
  <tr bgcolor="<?php echo $bgcolor; ?>">
    <td nowrap align="right" width="1%"><font size="2" color="#003366"><small><?php echo "$rc"; ?></small></font></td>
    <td nowrap><font size="2" color="#003366">&nbsp;
    <small>
        <?php 
 			//Make Artist+Tile combination
			 if(empty($song["artist"])) 
			  echo $song["title"];
			 else
			  echo $song["artist"] . " - ". $song["title"];
		?>

        <?php
            // do the stars

              $result = $link->query( "select avg(score) as avg_score from votez where songid=" . $song['ID'] . " group by songid");
              if ( $result ) {
                  $row = $result->fetch_assoc();
                  $avg_score = $row['avg_score'];
                  $avg_score = round( $avg_score );

                  echo '<a href="votez.php?id=' . $song['ID'] . '">';
                  for ( $x = 1; $x <= $avg_score; $x++ ) {
                      echo '<img style="border:none;" src="images/star.png">';
                  }
                  echo '</a>';
              }

        ?>

    </small>
    </font></td>

	<td nowrap width="1%">
      <p align="center"><font size="2" color="#003366"><a href="<?php echo "javascript:request($song[ID])"; ?>"><img
    src="images/request.gif" alt="Request this song now!" border="0"></a></font>
    </td>


    <td nowrap width="1%">

      <p align="center"><font size="2" color="#003366"><a href="http://www.google.com/search?q=<?php echo urlencode($song["artist"]); ?>" target="_blank"><img
    src="images/home.gif" alt="Artist homepage" border="0"></a></font>
    </td>

	<td nowrap align="center" width="1%">
      <font size="2" color="#003366"><a href="javascript:songinfo(<?php echo $song["ID"]; ?>)"><img
    src="images/info.gif" alt="Song information" border="0"></a></font>
    </td>

    <td nowrap><font color="#003366" size="2"><small><?php echo $song["album"]; ?></small></font></td>
    <td nowrap>
      <p align="right"><font color="#003366" size="2"><small><strong>
		  <?php 
			 $ss = round($song["duration"] / 1000);
			 $mm = (int)($ss / 60);
			 $ss = ($ss % 60);
			 if($ss<10) $ss="0$ss";
			 echo "$mm:$ss";
		  ?>
		  </strong></small></font>
    </td>
  </tr>
<?php
}//PutSongRow

/* ## ===================================================================== ## */
?>

<?php require("header.php"); ?>

<?php require("search.php"); ?>
<br>

<table border="0" width="98%" cellspacing="0" cellpadding="4">
  <tr bgcolor="#002E5B">
    <td colspan="7" nowrap align="left">
      <b><font face="Verdana, Arial, Helvetica, sans-serif" size="1" color="#FFFFFF">Playlist results</font></b>
    </td>
  </tr>
<?php
  while ( $song = $result->fetch_assoc() ) {
   PutSongRow($song);
  }
?>

  <tr bgcolor="#E0E0E0">
    <td colspan="7" nowrap align="center">
	<?php echo "$prevlnk"; ?>
 &nbsp; ( Showing <?php echo "$first to $last of $cnt"; ?> ) &nbsp;
	<?php echo "$nextlnk"; ?></td>
  </tr>

</table>

<br>
<?php 
  require("search.php"); 
?>
<?php
  require("footer.php"); 
?>
