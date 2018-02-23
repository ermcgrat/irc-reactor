<?php
require_once("config.php"); 
require_once("common/form.php");

/*
Requesting a song takes you to req.php?songid=<songid>

Assuming a successful request path
req.php -> 
	req/req.php - makes http request to sam server using songid from querystring, gets back the requestid from inserted requestlist.ID ->
	req/req.success.html - spits out song info, song is not dedicated at this point ->
	req/dedication.form.html - creates the dedication form, which (if submitted) will POST to MAIN req.php the following variables ->
		requestid, songid, rname, rmessage

Assuming the user then submits the dedication
req.php (requestid now set) ->
	req/req.dedication.php - UPDATES the requestlist table with dedicated by name/message ->
	req/req.success.html - spits out song info, song IS dedicated at this point ->
	req/dedication.info.html - spits out dedication info
*/
 
// This is empty when clicking on request button
if(empty($_POST['requestid']))  {   
	require_once("req/req.php");
} else {                  // but after hitting 'dedicate it' this becomes set
	require_once("req/req.dedication.php");
}
?>