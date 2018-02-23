<?php
// rmessage and rname are post data coming from the dedication form
$rmessage = $_POST['rmessage'];
$rname = $_POST['rname'];
$requestid = intval($_POST['requestid']);
$songid = intval($_POST['songid']);

$link = new mysqli($db_host, $db_username, $db_password, $db_dbname, $db_port);
if ($link->connect_errno) {
	die("Failed to connect to database: (" . $link->connect_errno . ") " . $link->connect_error);
}

$rmessage_sql = $link->real_escape_string($rmessage);
$rname_sql = $link->real_escape_string($rname);


$sql = "Update requestlist Set name = '$rname_sql', msg = '$rmessage_sql' Where ID = $requestid";
$result = $link->query($sql);

$sql = "SELECT songlist.*, songlist.ID as songID FROM songlist WHERE songlist.ID = $songid";
$result = $link->query($sql);
$song = $result->fetch_assoc();
$song["requestid"] = $requestid;

$dedicated = true;
 
require("req.success.html");
?>