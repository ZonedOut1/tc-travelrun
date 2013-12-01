<?php
## $Id$
## $URL#

require '.config.php';

session_start();
$_SESSION['timecheck'] = date('YmdHis', time() + 1);

$cc = 'm';
if (isset($_GET['c'])) $cc = substr(trim($_GET['c']), 0, 1);
if (!in_array($cc, array('m', 'i', 'c', 'h', 'u', 'a', 's', 'j', 'x', 'e', 'z'))) $cc = 'm';

// open the database connection
$conn = mysql_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error());
mysql_select_db(SQL_DATA);

// update count
$sql = "update counts set value = value + 1 where vkey = '$cc'";
mysql_query($sql) or die(mysql_error());

// delete old data
$sql = "delete from stock where utctime < now() - interval 32 day";
mysql_query($sql) or die(mysql_error());
$sql = "delete from post where postUTC < now() - interval 32 day";
mysql_query($sql) or die(mysql_error());

// get last update date/time from the database
$sql = <<<SQL_LAST
select country.countryname, max(stock.utctime)
from country, stock
where stock.country = country.countryid
  and stock.manual = 0
group by country.countryname
order by max(stock.utctime) desc
limit 1
SQL_LAST;
$res = mysql_query($sql) or die(mysql_error());
$lastcountry = mysql_result($res, 0, 0);
$lastupdate = mysql_result($res, 0, 1);
mysql_free_result($res);

$sql = "select max(stock.utctime) from stock, country where stock.country = country.countryid and country.letter = '$cc' and stock.manual = 0";
$res = mysql_query($sql) or die(mysql_error());
$lastlocalupdate = mysql_result($res, 0, 0);
mysql_free_result($res);

$sql = "select countryname, countryid from country where letter = '$cc'";
$res = mysql_query($sql) or die(mysql_error());
$cname = mysql_result($res, 0, 0);
$ccode = mysql_result($res, 0, 1);
mysql_free_result($res);

$extradata = array();
$sql =<<<SQL_EXTRADATA
select itemtype.itemtypename, item.itemname, stock.price, stock.quantity, itemtype.bgcolor
from stock, item, itemtype
where stock.item = item.itemid
  and item.itemtype = itemtype.itemtypeid
  and stock.utctime = '$lastlocalupdate'
  and stock.country = $ccode
order by itemtype.itemtypename, item.itemname
SQL_EXTRADATA;
$res = mysql_query($sql) or die(mysql_error());
while ($row = mysql_fetch_row($res)) {
  $extradata[] = array($row[0], $row[1], $row[2], $row[3], $row[4]);
}
mysql_free_result($res);

// get the data for drugs
$drugs = array();
$sql = <<<SQL_DRUGS
select stock.item, item.itemname, stock.country, country.countryname, stock.utctime, stock.price, stock.quantity
from stock, lastdrugs, item, country
where stock.item = lastdrugs.item
  and stock.item = item.itemid
  and stock.country = country.countryid
  and stock.country = lastdrugs.country
  and stock.utctime = lastdrugs.lastutc
order by item.itemname, stock.price
SQL_DRUGS;
$res = mysql_query($sql) or die(mysql_error());
while ($row = mysql_fetch_row($res)) {
  $drugs[] = array($row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6]);
}
mysql_free_result($res);

// get update frequency
$cvk = array();
$upk = $viewk = 0;
$sql = "select vkey, value from counts";
$res = mysql_query($sql) or die(mysql_error());
while ($row = mysql_fetch_row($res)) {
  if ($row[0] == 'update2') {
    $upk = $row[1];
  } else {
    $viewk += $row[1];
    $cvk[$row[0]] = $row[1];
  }
}
mysql_free_result($res);

// close the database connection
mysql_close($conn);

echo 'Current time is ', gmdate('Y-m-d H:i:s'), ' GMT.<br>';
echo 'Last update was made at ', $lastupdate, ' GMT for ', $lastcountry, '.<br>';
echo '<br>';

$ratio = $upk / $viewk;
echo '<h3>When you\'re in a foreign country, please <a href="update.php" title="update ratio: ';
if ($ratio < 0.0095) {
  echo number_format(1000 * $ratio, 2), '&permil;';
} else {
  echo number_format(100 * $ratio, 2), '%';
}
echo '">update the data</a>. Thank you.<br>';
echo '<span style="font-size: small">There is also an <a href="manupdate.php">manual update feature</a> for isolated data on the flower thread.</span></h3><br>';
echo '<br>';

echo 'Choose a country: ';
if ($cc == 'm') echo '<b>';
echo '<a href="default.php?c=m" title="', $cvk['m'], ' views">Mexico</a>, ';
if ($cc == 'm') echo '</b>';

if ($cc == 'i') echo '<b>';
echo '<a href="default.php?c=i" title="', $cvk['i'], ' views">Cayman Islands</a>, ';
if ($cc == 'i') echo '</b>';

if ($cc == 'c') echo '<b>';
echo '<a href="default.php?c=c" title="', $cvk['c'], ' views">Canada</a>, ';
if ($cc == 'c') echo '</b>';

if ($cc == 'h') echo '<b>';
echo '<a href="default.php?c=h" title="', $cvk['h'], ' views">Hawaii</a>, ';
if ($cc == 'h') echo '</b>';

if ($cc == 'u') echo '<b>';
echo '<a href="default.php?c=u" title="', $cvk['u'], ' views">United Kingdom</a>, ';
if ($cc == 'u') echo '</b>';

if ($cc == 'a') echo '<b>';
echo '<a href="default.php?c=a" title="', $cvk['a'], ' views">Argentina</a>, ';
if ($cc == 'a') echo '</b>';

if ($cc == 's') echo '<b>';
echo '<a href="default.php?c=s" title="', $cvk['s'], ' views">Switzerland</a>, ';
if ($cc == 's') echo '</b>';

if ($cc == 'j') echo '<b>';
echo '<a href="default.php?c=j" title="', $cvk['j'], ' views">Japan</a>, ';
if ($cc == 'j') echo '</b>';

if ($cc == 'x') echo '<b>';
echo '<a href="default.php?c=x" title="', $cvk['x'], ' views">China</a>, ';
if ($cc == 'x') echo '</b>';

if ($cc == 'e') echo '<b>';
echo '<a href="default.php?c=e" title="', $cvk['e'], ' views">UAE</a>, ';
if ($cc == 'e') echo '</b>';

if ($cc == 'z') echo '<b>';
echo '<a href="default.php?c=z" title="', $cvk['z'], ' views">South Africa</a>';
if ($cc == 'z') echo '</b>';

echo '<br><br>';
$gmnow = gmdate('Y-m-d H:i:s');
$unixlast = strtotime($lastlocalupdate);
$unixnow = strtotime($gmnow);
$delta = $unixnow - $unixlast;
if ($delta < 60) {
  $deltaunits = 'second';
} else {
  $delta /= 60;
  if ($delta < 60) {
    $deltaunits = 'minute';
  } else {
    $delta /= 60;
    if ($delta < 24) {
      $deltaunits = 'hour';
    } else {
      $delta /= 24;
      $deltaunits = 'day';
    }
  }
}
echo 'Items in ', $cname, ' at ', $lastlocalupdate, ' GMT ';
      echo '<b>(', number_format($delta, 0), ' ', $deltaunits, (($delta >= 1.5) ? 's' : ''), ' ago)</b><br>';
echo '<table border="1">';
echo '<tr><th>TYPE</th><th>ITEM</th><th>PRICE</th><th>QUANTITY</th></tr>';
foreach ($extradata as $extra) {
  echo '<tr bgcolor="', $extra[4], '">';
  echo '<td>&nbsp;', $extra[0], '&nbsp;</td>';
  echo '<td>&nbsp;', $extra[1], '&nbsp;</td>';
  echo '<td align="right">&nbsp;$', number_format($extra[2], 0, '', ','), '&nbsp;</td>';
  echo '<td align="right">&nbsp;', number_format($extra[3], 0, '', ','), '&nbsp;</td>';
  echo '</tr>';
}
echo '</table>';

echo '<br><br>';

echo 'Most recent drug information<br>';
echo '<table border="1">';
echo '<tr><th>Drug</th><th>Country</th><th>oldness</th><th>Quantity</th><th>Price</th></tr>';
$bgcolors = array('#ededed', '#cbcbcb');
$olddrug = '';
$lastcolor = 0;
foreach ($drugs as $d) {
  if ($d[1] != $olddrug) $lastcolor = 1 - $lastcolor;
  $olddrug = $d[1];

  $gmnow = gmdate('Y-m-d H:i:s');
  $unixlast = strtotime($d[4]);
  $unixnow = strtotime($gmnow);
  $delta = $unixnow - $unixlast;
  if ($delta < 60) {
    $deltaunits = 'second';
  } else {
    $delta /= 60;
    if ($delta < 60) {
      $deltaunits = 'minute';
    } else {
      $delta /= 60;
      if ($delta < 24) {
        $deltaunits = 'hour';
      } else {
        $delta /= 24;
        $deltaunits = 'day';
      }
    }
  }

  echo '<tr bgcolor="', $bgcolors[$lastcolor], '">';
  echo '<td>&nbsp;', $d[1], '&nbsp;</td>';
  echo '<td>&nbsp;', $d[3], '&nbsp;</td>';
  echo '<td align="center">&nbsp;', number_format($delta, 0), ' ', $deltaunits, (($delta >= 1.5) ? 's' : ''), ' ago&nbsp;</td>';
  echo '<td align="right">&nbsp;', number_format($d[6], 0, '', ','), '&nbsp;</td>';
  echo '<td align="right">&nbsp;$', number_format($d[5],0, '', ','), '&nbsp;</td>';
  echo '</tr>';
}
echo '</table>';

echo '<br><br>';

echo '<i><font size="smaller">If the graph does not appear, refresh the page</font></i><br>';
echo '<img src="fgraph.php?c=', $cc, '">';

echo '<br><br>';
echo '<i>Travelrun site hosted free at <a href="http://www.000webhost.com/">http://www.000webhost.com/</a>.</i>';
?>