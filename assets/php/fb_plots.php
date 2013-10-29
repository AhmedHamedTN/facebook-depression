<?php
// This script may take a while, so we're giving it 30 minutes to run!
ini_set('max_execution_time', 1800); 

// Ensure that all time-related operations happen in Central Time.
date_default_timezone_set('America/Mexico_City');

// Get db.
require dirname(__FILE__).'/db.php';
$db = get_db();

// Determine what action to take, based on the post variables.
if ($_POST['request'] == 'Sent:Receieved Graph') {
  showSentReceievedGraph('615474653', 52, 1600, 768, $db);
}
else if ($_POST['request'] == 'Show Sent:Receieved Graph vs. Best Friends') {
  showSentReceievedWithTopFriendsGraph('615474653', 10, 3, 1600, 768, $db);
}

/**
 * Show graph of sent/receieved data from $user for the past $numWeeks.
 *
 * Also requires a db to pull data from.
 */
function showSentReceievedGraph($user, $numWeeks, $width, $height, $db) {
  // get library for processing data
  require dirname(__FILE__).'/fb_analysis.php';

  $weekMessagesMap = getNumberMessagesPerWeek(getAllMessages($user, $numWeeks, $db));

  // echo prelude
  echo '<html>',
       '<head>',
       '<script type="text/javascript"',
       'src="../js/dygraph-combined.js"></script>',
       '</head>',
       '<body>',
       '<div id="graphdiv" style="width: ', $width, 'px; height: ', $height, '"></div>',
       '<script type="text/javascript">',
       'g = new Dygraph(',
       'document.getElementById("graphdiv"),',
       '',
       '"Date,Sent:Receieved\n" +';

  // echo data.
  $count = 0;
  foreach ($weekMessagesMap as $timestamp => $numMessages) {
    // get ratio
    $ratio = $numMessages['sent'] / $numMessages['receieved'];

    // get timestamp formatted as date
    $date = date('Y-m-d', $timestamp);

    if ($count < count($weekMessagesMap) - 1) {
      echo '"', $date, ',', $ratio, '\n" +';
    }
    else {
      echo '"', $date, ',', $ratio, '\n"';
    }
    $count++;
  }

  // echo options
  echo ', {title: "Sent:Received Message Ratio by Week"}';

  // echo closing tags.
  echo ');',
       '</script>',
       '</body>',
       '</html>';
}

/**
 * Show graph of sent/receieved data from $user for the past $numWeeks.
 *
 * Also requires a db to pull data from.
 */
function showSentReceievedWithTopFriendsGraph($user, $numWeeks, $numTopFriends, $width, $height, $db) {
  // get library for processing data
  require dirname(__FILE__).'/fb_analysis.php';

  $messages = getAllMessages($user, $numWeeks, $db);

  // get top friends
  $topFriends = getTopFriends($user, $messages, $numTopFriends);
  print_r($topFriends);

  $weekMessagesMap = getNumberMessagesPerWeek($messages);

  // get the first timestamp
  reset($weekMessagesMap);
  $firstDate = key($weekMessagesMap);

  // get the messages per week from top friends.
  $topFriendsMessagesMap = getMessagesPerWeekFromFriends($user, $firstDate, $messages, $topFriends, $db);

  // echo prelude
  echo  '<html>',
        '<head>',
        '<script type="text/javascript"',
        'src="../js/dygraph-combined.js"></script>',
        '</head>',
        '<body>',
        '<div id="graphdiv" style="width: ', $width, 'px; height: ', $height, '"></div>',
        '<script type="text/javascript">',
        'g = new Dygraph(',
        'document.getElementById("graphdiv"),',
        '',
        '"Date,Total,';

        $count = 1;
        foreach ($topFriends as $index => $friend) {
          if ($count == count($topFriends)) {
            echo '#', $count, ' Top Friend';
          }
          else {
            echo '#', $count, ' Top Friend,';
          }
          $count++;
        }
        echo '\n" +';

  // echo data.
  $count = 0;
  foreach ($weekMessagesMap as $timestamp => $numMessages) {
    // get ratio
    $ratio = $numMessages['sent'] / $numMessages['receieved'];

    // get timestamp formatted as date
    $date = date('Y-m-d', $timestamp);

    echo '"', $date, ',', $ratio, ',';

    // echo data for each friend.
    $friendCount = 1;
    foreach ($topFriends as $index => $friend) {
      $ratio;
      if ($topFriendsMessagesMap[$timestamp][$friend]['receieved'] == 0) {
        $ratio = 3;
      }
      else {
        $ratio = $topFriendsMessagesMap[$timestamp][$friend]['sent'] / $topFriendsMessagesMap[$timestamp][$friend]['receieved'];
      }

      if ($friendCount == count($topFriends)) {
        echo $ratio;
      }
      else {
        echo $ratio, ',';
      }
      $friendCount++;
    }

    // whether or not to include '+'.
    if ($count < count($weekMessagesMap) - 1) {
      echo '\n" +';
    }
    else {
      echo '\n"';
    }
    $count++;
  }

  // echo options
  echo ', {title: "Sent:Received Message Ratio by Week"}';

  // echo closing tags.
  echo ');',
       '</script>',
       '</body>',
       '</html>';
}