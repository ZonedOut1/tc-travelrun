<?php
## $Id$
## $URL$

require '.config.php';

// open the database connection
$conn = mysql_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error());
mysql_select_db(SQL_DATA);

// update count
$sql = "select count(*) from lotto";
$res = mysql_query($sql) or die(mysql_error());
$k = mysql_result($res, 0, 0);
mysql_free_result($res);
mysql_close($conn);
?>
Go back to the <a href="default.php">start page</a>.<br>
<br>
<form method="post" action="update2.php">
  <input type="hidden" name="sender" value="web">
  Copy all of the torn country item page <i>(<b>Ctrl+A</b>, <b>Ctrl+C</b> probably works)</i><br>
  paste in the text box below <i>(<b>Ctrl+V</b> perhaps)</i> 
  <!--font size="-2">(if you're paranoid, you can edit the amount you have to,
  for example, <b>$100</b>, but leave that line in with no other changes)</font--><br>
  and click the "UPDATE" button<br>
  <i>The pasted text has to contain the line with the country name and your money; you can safely edit the amount of money but leave that line otherwise unchanged</i><br><br>
  <textarea name="data" rows="20" cols="80"></textarea>
  <br><input type="submit" value="UPDATE"> <i>Thank you.</i>
</form>