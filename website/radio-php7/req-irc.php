<?php
require("config.php"); 
?>
<html>
<head>
</head>
<body>
<?php

function DoError($code)  
{
	global $samhost, $samport, $errno, $errstr;

	switch ($code)
	{
		case 800 : $message = "SAM host must be specified"; break;
		case 801 : $message = "SAM host can not be 127.0.0.1 or localhost"; break;
		case 802 : $message = "Song ID must be valid";  break;
		case 803 : $message = "Unable to connect to $samhost:$samport. Station might be offline.<br>The error returned was $errstr ($errno).";  break;
		case 804 : $message = "Invalid data returned!";  break;
	}
	echo "<i>$message</i></body></html>";
	exit;
}


$songid = intval($_GET['songid']);
$host = $_GET['host'];
if ($songid < 1) {
	die('<i>Invalid song requested</i></body></html>');
}
if (empty($host)) {
	die('<i>Invalid host specified</i></body></html>');
}

$dedicated = false;
$samhost = $sam["host"];
$samport = $sam["port"];

if(empty($samhost)) DoError(800);

if($songid == -1) DoError(802);

$request = "GET /req/?songid=".$songid."&host=".$_GET['host']." HTTP\1.0\r\n\r\n";
$xmldata = "";
$fd = @fsockopen($samhost,$samport, $errno, $errstr, 30);	

// send the request to the dj program
if(!empty($fd))
{		
	fputs ($fd, $request);

	$line="";
	while(!($line=="\r\n"))
		{ $line=fgets($fd,128); }	// strip out the header
	while ($buffer = fgets($fd, 4096))
		{  $xmldata  .= $buffer; }
	fclose($fd);
}
else DoError(803);

if(empty($xmldata)) DoError(804);

//#################################
//      Initialize data
//#################################
$responseXML = new SimpleXMLElement($xmldata);
$code = $responseXML->status[0]->code;
$message = $responseXML->status[0]->message;
$requestid = $responseXML->status[0]->requestID;
	
if(empty($code)) DoError(804);

if($code==200)
	echo "<b>Your request has been submitted!</b>"; 
else
	echo "<i>$message</i>";
?>
</body>
</html>
