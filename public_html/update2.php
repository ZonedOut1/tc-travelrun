<?php
## $Id$

require '.config.php';
require 'fx.inc.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] != 'POST') die('No!');

$cch = 'm';
$prize = 0;

// open the database connection
$conn = mysql_connect(SQL_HOST, SQL_USER, SQL_PASS) or die(mysql_error());
mysql_select_db(SQL_DATA);

// save raw $_POST['data'] in the database
$utc = gmdate('Y-m-d H:i:s');
$pd = mysql_real_escape_string($_POST['data']);
$s = $_SERVER['REMOTE_ADDR'];
$ua = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
$r = mysql_real_escape_string($_SERVER['HTTP_REFERER']);
$sql = "insert into post (postid, postUTC, postdata, user_agent, referer, sender) values (NULL, '$utc', '$pd', '$ua', '$r', '$s')";
mysql_query($sql) or die(mysql_error());

/*
post as of 2014-06-23 (after RESPO)
	
TORN

    News
    Forums
    Contact
    Help

    Name

Cayman Islands
Logout People Events Offshore Banking Market Travel Home
You are in Cayman Islands and have $8,175,020. You have purchased 23 / 23 items so far.
Item
Type
Name
Cost
Stock
Circulation
Amount
Buy

    Plushie Stingray Plushie $400 465 133,628 Buy
    Other Nodding Turtle $750 79 98,496 Buy
    Other Steel Drum $1,500 25 22,456 Buy
    Flower Banana Orchid $4,000 5,525 1,030,521 Buy
    Other Diving Gloves $5,000 49 23,402 Buy
    Defensive Speedo $6,000 14 12,088 Buy
    Defensive Bikini $8,000 25 33,417 Buy
    Other Flippers $10,000 44 7,089 Buy
    Other Snorkel $20,000 11 8,663 Buy
    Defensive Wetsuit $30,000 47 4,298 Buy
    Secondary Harpoon $300,000 92 3,840 Buy
    Primary Tavor TAR-21 $495,000 87 5,547 Buy
    Melee Naval Cutlass Sword $50,000,000 3 704 Buy



Faction
*/

// process $_POST['data'] and save info to the database
$n = preg_match('/are in ([A-Z a-z]+) and have/', $_POST['data'], $matches);
if ($n == 1) {
  $country = $matches[1];
  $safe_country = mysql_real_escape_string($country);

  $sql2 = "select countryid, flower, letter, itemname from country, item where country.flower = item.itemid and countryname = '$safe_country'";
  $res2 = mysql_query($sql2) or die(mysql_error());
  $cid = $fid = 0;
  if (mysql_num_rows($res2) == 1) {
    $cid = mysql_result($res2, 0, 0);
    $fid = mysql_result($res2, 0, 1);
    $cch = mysql_result($res2, 0, 2);
    $fin = mysql_result($res2, 0, 3);
  }
  mysql_free_result($res2);
  if ($cid != 0) {
    $_SESSION['recent_update'] = array();
    $_SESSION['recent_update']['country'] = $country;
    $_SESSION['recent_update']['flower'] = $fin;
    $gotflower = 0;
    $items = array();
    $sql_items = "select itemname, itemid from item";
    $res_items = mysql_query($sql_items) or die(mysql_error());
    while ($row_items = mysql_fetch_row($res_items)) {
      $items[$row_items[0]] = $row_items[1];
    }
    mysql_free_result($res_items);

#   if (preg_match_all('@\s*([0-9A-Z &a-z-]+)\s+\$([0-9,]+)\s+([0-9,]+) in stock\s+Buy@', $_POST['data'], $matches, PREG_SET_ORDER)) {
#   Flower Banana Orchid $4,000 5,525 1,030,521 Buy
    if (preg_match_all('@\s*[a-zA-Z]*\s+([0-9A-Z &a-z-]+)\s+\$([0-9,]+)\s+([0-9,]+)\s+[0-9,]+\s+Buy@', $_POST['data'], $matches, PREG_SET_ORDER)) {
      foreach ($matches as $info) {
        $itemname = trim($info[1]);
        $itemcost = 1 * str_replace(',', '', $info[2]);
        $itemleft = 1 * str_replace(',', '', $info[3]);
        $itemid = 0;
        if (isset($items[$itemname])) $itemid = $items[$itemname];
        if ($itemid != 0) {
          $sql4 = "insert into stock (stockid, utctime, country, item, price, quantity, manual, sender) values (NULL, utc_timestamp(), $cid, $itemid, $itemcost, $itemleft, 0, '$s')";
          mysql_query($sql4) or die(mysql_error());
          if ($itemid == $fid) {
            $gotflower = 1;
            $_SESSION['recent_update']['qtd'] = $itemleft;
          }
        }
      }
    }
    if (!$gotflower) {
      $_SESSION['recent_update']['qtd'] = 0;
      $sql5 = "insert into stock (stockid, utctime, country, item, price, quantity, manual, sender) values (NULL, utc_timestamp(), $cid, $fid, 0, 0, 0, '$s')";
      mysql_query($sql5) or die(mysql_error());
    }
    if (mt_rand(0, 999999) < PRIZE_PER_MILLION) {
      $mindate = gmdate('Y-m-d H:i:s', time() - 25*60);
      $sqlprize = "select count(*) from post where sender='$s' and postUTC > '$mindate'";
      $resprize = mysql_query($sqlprize) or die(mysql_error());
      $recent = mysql_result($resprize, 0, 0);
      mysql_free_result($resprize);
      if (!$recent) $prize = 1;
    }
  }

  // update count
  $sql = "update counts set value = value + 1 where vkey = 'update2'";
  mysql_query($sql) or die(mysql_error());

  # delete files starting with $cch
  $files = glob('images/' . $cch . '*');
  foreach ($files as $file) {
    if (is_file($file)) unlink($file);
  }
}

if ($prize) {
  $prizedate = gmdate('Y-m-d H:i:s');
  $prizecode = md5(PRIZE_PREFIX . $prizedate);
  $sql = "insert into prize (pdate, pcode, puser, pcountry) values ('$prizedate', '$prizecode', 0, '$safe_country')";
  mysql_query($sql) or die(mysql_error());
  mysql_close($conn);
  httpheader();
  echo htmlheader('travelrun -- prize', usercss());
  echo '<div class="prize">';
  echo '<h1>Congratulations!</h1>';
  echo '<h2>You have won a prize.</h2>';
  echo '<h3>send a in-game message to <a href="http://www.torn.com/profiles.php?XID=1757971">ebcdic</a> to reclaim it.</h3>';
  echo '<h3>include the prize date and code in the message</h3>';
  echo '<h3 style="text-align: center;"><pre>';
  echo 'date: ', $prizedate, "\n";
  echo 'code: ', $prizecode;
  echo '</pre></h3>';
  echo '<br><i>Prizes are in testing phase: they\'ll be small prizes :)</i>';
  echo '<br><br>';
  echo 'Back to <a href="index.php?c=', $cch, '">the regular travelrun page</a>.';
  echo '</div>';
  echo htmlfooter();
  exit(0);
}

// close the database connection
mysql_close($conn);

usleep(100000); // sleep 0.1 seconds: it's hardly noticeable and it, hopefully, gives time for image to be created

header('Location: index.php?c=' . $cch);
exit('Redirected to <a href="index.php?c=' . $cch . '">the start page</a>.');
?>
