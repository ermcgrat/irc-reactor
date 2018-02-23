<?php

require("config.php");

$link = new mysqli($db_host, $db_username, $db_password, $db_dbname, $db_port);
if ($link->connect_errno) {
	die("Failed to connect to database: (" . $link->connect_errno . ") " . $link->connect_error);
}

$songid = intval($_GET['songid']);
$sql = "SELECT * FROM songlist WHERE ID = $songid";
$result = $link->query($sql);
$song = $result->fetch_assoc();
?>



<html>
<head>
<title>Song information</title>
<style>
body {  font-family: Arial, Helvetica, sans-serif; color : gray; }
A { text-decoration : none; color : teal; }
A:Hover { text-decoration : underline; color : teal; }
</style>
	
<script>

function request(songid)
{
 reqwin = window.open("req.php?songid="+songid, "_AR_request", "location=no,status=no,menubar=no,scrollbars=no,resizeable=yes,height=420,width=668");
}
</script>
	
</head>

<body bgcolor="#FFFFFF">

<table border=0 width="100%">
<tr><td align="center" valign="top" width="100%">

<div align="center"><center>

<table border="0" width="98%" cellspacing="1" cellpadding="2">
  <tr bgcolor="#002E5B">
    <td colspan="5">
      <p align="center"><font size="2"><b><font color="#FFFFFF" size="1" face="Verdana, Arial, Helvetica, sans-serif">Song Information</font></b></font>
    </td>
  </tr>
  <tr>
    <td colspan="5"><img src="images/spacer.gif" width="15" height="13"></td>
  </tr>
  <tr>
    <td bgcolor="EAEAEA" align="right"><font color="#333333" size="2">Title<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea"><b><font color="#003366" size="2"><?php echo $song["title"]; ?></font></b></td>
    <td bgcolor="eaeaea" align="right"><font color="#333333" size="2"><img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea" align="center"></td>
    <td bgcolor="EAEAEA">
      <p align="center"><font color="#333333" size="2">Picture</font>
    </td>
  </tr>
  <tr>
    <td bgcolor="#FFFFFF" align="right"><font color="#333333" size="2">Artist<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="#FFFFFF"><b><font color="#003366" size="2"><?php echo $song["artist"]; ?></font></b></td>
    <td bgcolor="#FFFFFF"></td>
    <td bgcolor="#FFFFFF"></td>
    <td bgcolor="#FFFFFF" rowspan="6" valign="middle">
		<?php
		if (!empty($song["picture"])) {
			echo '<p align="center"><img width="80" height="80" src="' . $picture_dir . $song["picture"] . '" border=0></p>';
		}
		?>
    </td>
  </tr>

  <tr>
    <td bgcolor="EAEAEA" align="right"><font color="#333333" size="2">Album<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea"><b><font color="#003366" size="2"><?php echo $song["album"]; ?></font></b></td>
    <td bgcolor="eaeaea" align="right"><font color="#333333" size="2">Home<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea" align="center"><a href="http://www.google.com/search?q=<?php echo urlencode($song["artist"]); ?>" target="_blank"><img src="images/home.gif" alt="Artist homepage" border="0"></a></td>
  </tr>

  <tr>
    <td bgcolor="#FFFFFF" align="right"><font color="#333333" size="2">Year<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="#FFFFFF"><b><font color="#003366" size="2"><?php echo $song["albumyear"]; ?></font></b></td>
    <td bgcolor="#FFFFFF"><font color="#333333" size="2"></font></td>
    <td bgcolor="#FFFFFF"></td>
  </tr>
  <tr>
    <td bgcolor="EAEAEA" align="right"><font color="#333333" size="2">Genre<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea"><b><font color="#003366" size="2"><?php echo $song["genre"]; ?></font></b></td>
     <td bgcolor="eaeaea">
      <p align="right"><font color="#333333" size="2">Request<img src="images/spacer.gif" width="15" height="13"></font>
    </td>
    <td bgcolor="eaeaea" align="center"><a href="<?php echo "javascript:request($song[ID])"; ?>"><img src="images/request.gif" alt="Request this song now!" border="0"></a></td>
  </tr>
  
  <!-- rating section-->
  
    <?php
        // do the stars
        $starsHTML = '';

		$result = $link->query( "select avg(score) as avg_score from votez where songid=" . $songid . " group by songid");
		if ( $result ) {
			$row = $result->fetch_assoc();
			$avg_score = $row['avg_score'];
			$avg_score = round( $avg_score );

			$starsHTML = '<a target="_blank" href="votez.php?id=' . $songid . '">';
			for ( $x = 1; $x <= $avg_score; $x++ ) {
				$starsHTML .= '<img style="border:none;" src="images/star.png">';
			}
			$starsHTML .= '</a>';

			if ( $avg_score == 0 ) {

				$starsHTML = '<font color="#003366" size="2"><b>Not yet rated.</b></font>';

			}
		}
    
    ?>  
  <tr>
    <td bgcolor="EAEAEA" align="right"><font color="#333333" size="2">Rating<img src="images/spacer.gif" width="15" height="13"></font></td>
    <td bgcolor="eaeaea"><?php echo $starsHTML; ?></td>
     <td bgcolor="eaeaea"></td>
    <td bgcolor="eaeaea" align="center"></td>
  </tr>  
  
  <!-- spacer -->
  <tr>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>



</table>

<?php 
	require("footer.php"); 
?>
