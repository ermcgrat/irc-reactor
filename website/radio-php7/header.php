<html>

<head>
<title><?php echo $station; ?></title>
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
<style>
body {  font-family: Arial, Helvetica, sans-serif; color : gray; }
A { text-decoration : none; color : teal; }
A:Hover { text-decoration : underline; color : teal; }
</style>


<script>
function PictureFail(picname) {
	if (document.images)
	{
		document.images[picname].width   = 1;
		document.images[picname].height  = 1;
	}
 }
function songinfo(songid)
{
	songwin = window.open("songinfo.php?songid="+songid, "songinfowin", "location=no,status=no,menubar=no,scrollbars=yes,resizeable=yes,height=400,width=650");
}
function request(songid)
{
	reqwin = window.open("req.php?songid="+songid, "_AR_request", "location=no,status=no,menubar=no,scrollbars=no,resizeable=yes,height=420,width=668");
}
</script>
</head>

<body>

<table border=0 cellspacing=5 cellpadding=5>
<tr>
 <td align="center" valign="top" width="1%">
 <?php require("partners.php"); ?>
 <br>
 <?php require("nav.php"); ?>

 </td>
 <td align="left" valign="top" width="99%">



