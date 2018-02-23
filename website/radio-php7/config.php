<?php

// mysql database connection information
// In php7, 'localhost' doesn't seem to work, while 127.0.0.1 does. Weird
$db_host = "127.0.0.1";
$db_port = "3306";
$db_dbname = "radio";
$db_username = "radio";
$db_password = "getrekt99";

//Station general details
$station  = "iRC-Reactor Tunez";
$email    = "tunez@irc-reactor.com";
$logo     = "images/react1.gif";

$stationid   = 0;           //The ID of your registered station on AudioRealm.com
$sam["host"] = "173.62.72.79"; //The IP address of the machine SAM is running on (DO NOT use a local IP address like 127.0.0.1 or 192.x.x.x)
$sam["port"] = "3308";      //The port SAM handles HTTP requests on. Usually 1221.

// General options
$privaterequests = true;  //If False, AudioRealm.com will handle the requests
$showtoprequests = true;  //Must we show the top 10 requests on the now playing page? 
$requestdays     = 30;    //Show the top10 requests for the last xx days

$showpic     = true; //Must we show pictures in now playing section?   
$picture_dir = "pictures/"; //Directory where all your album pictures are stored
$picture_na  = $picture_dir."na.jpeg"; //Use this picture if the song has no picture

// Row colors used
$darkrow  = "#dadada";
$lightrow = "#F6F6F6";  
 
?>