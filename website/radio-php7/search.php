<?php require_once('common/form.php'); ?>
<form method="GET" action="playlist.php">
  <p>Search 
<?php InputText("search",$search,'',20); ?> <input type="submit" value="Go" name="B1">
&nbsp;&nbsp;Display <?php InputCombo("limit",$limit,25,'5,10,25,50,100'); ?> results

</p>
</form>

Search by Artist:<br><a href='?letter=0'>0 - 9</a>
<?php
 for($c=ord('A');$c<=ord('Z');$c++)
 {
  $v = chr($c);
  echo ", <a href='?letter=$v'>$v</a>";
 }
?>
<br>
