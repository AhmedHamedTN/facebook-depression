<?php
// // This script may take a while, so we're giving it 30 minutes to run!
// ini_set('max_execution_time', 1800); 

// // Ensure that all time-related operations happen in Central Time.
// date_default_timezone_set('America/Mexico_City');

// // Get db.
// require dirname(__FILE__).'/db.php';
// $db = get_db();

// // Determine what action to take, based on the post variables.
// if ($_POST['request'] == 'Debug') {
//   $user = '615474653';
//   $messages = getAllMessages($user, 4, $db);
//   $numberOfFriends = 1;
//   $topFriends = getTopFriends($user, $messages, $numberOfFriends);
//   $friends = $topFriends;
//   print_r($friends);

//   echo '<br />';

//   $startDate = $messages[0]['created_time'];
//   print_r(getMessagesPerWeekFromFriends($user, $startDate, $messages, $friends, $db));
// }

/**
 * Gets all of the user's ($user) messages, with $howManyWeeks worth of weeks.
 * Also takes in database object to get data from.
 */
function getAllMessages($user, $howManyWeeks, $db) {
  // Get the unix timestamp that is $howManyMonths months ago
  $earliestDate = strtotime('-'. $howManyWeeks .' week', time()); 

  $stmt = $db->prepare("SELECT viewer_id, message_id, author_id, created_time, body, source, thread_id, attachment 
                        from facebook_messages
                        where viewer_id = '{$user}' and created_time > ".$earliestDate." ORDER BY created_time ASC");

  $stmt->execute();
  $res = $stmt->get_result();

  $messages = array();

  // get all the rows.
  while ($row = $res->fetch_assoc()) {
    $messages[] = $row;
  }

  return $messages;
}

/**
 * Gets the number of in/out messages per week.
 * Takes in an array of raw messages.
 * WARNING: Assumes that the messages are sorted by date.
 * Returns an associative array that maps a unix timestamp to another
 * associative array with keys "sent" and receieved".
 */
function getNumberMessagesPerWeek($messages) {
  $weekToMessagesMap = array();

  // get the user.
  $user = $messages[0]['viewer_id'];

  // get the first time stamp (we're assuming $messages is sorted).
  $weekTimeStamp = $messages[0]['created_time'];

  // initialize the first "bucket" of the map
  $weekToMessagesMap[$weekTimeStamp]['sent'] = 0;
  $weekToMessagesMap[$weekTimeStamp]['receieved'] = 0;

  for ($i = 0; $i < count($messages); $i++) {
    $messageTimestamp = $messages[$i]['created_time'];
    // if the next timestamp isk over a week away from the last week, shift by a week.
    if ($messageTimestamp >= $weekTimeStamp + 604800) {
      $weekTimeStamp = $weekTimeStamp + 604800;
      // initialize the first "bucket" of the map
      $weekToMessagesMap[$weekTimeStamp]['sent'] = 0;
      $weekToMessagesMap[$weekTimeStamp]['receieved'] = 0;
    }

    // if the message is a sent message, increment the map.
    if ($messages[$i]['author_id'] == $user) {
      $weekToMessagesMap[$weekTimeStamp]['sent'] += 1;
    }
    else {
      $weekToMessagesMap[$weekTimeStamp ]['receieved'] += 1; 
    }
  }

  return $weekToMessagesMap;
}

/**
 * Gets the sent/received messages from $friends in $messages, beginning at $startDate
 */
function getMessagesPerWeekFromFriends($user, $startDate, $messages, $friends, $db) {
  $weekToMessagesMap = array();

  // get the first time stamp (we're assuming $messages is sorted).
  $weekTimeStamp = $startDate;

  // initialize the first "bucket" of the map
  foreach ($friends as $index => $friend) {
    $weekToMessagesMap[$weekTimeStamp][$friend]['sent'] = 0;
    $weekToMessagesMap[$weekTimeStamp][$friend]['receieved'] = 0;
  }

  for ($i = 0; $i < count($messages); $i++) {
    $message = $messages[$i];
    $author = $message['author_id'];

    $messageTimestamp = $messages[$i]['created_time'];
    // if the next timestamp isk over a week away from the last week, shift by a week.
    if ($messageTimestamp >= $weekTimeStamp + 604800) {
      $weekTimeStamp = $weekTimeStamp + 604800;
      // initialize the first "bucket" of the map
      foreach ($friends as $index => $friend) {
        $weekToMessagesMap[$weekTimeStamp][$friend]['sent'] = 0;
        $weekToMessagesMap[$weekTimeStamp][$friend]['receieved'] = 0;
      }
    }

    // if the message is a sent message, increment the map.
    if ($author == $user) {
      // find out who the recipient was!
      $messageRecipients = getMessageRecipients($message, $user, $db);

      // find out if any recipients are friends.
      foreach ($messageRecipients as $index => $recipient) {
        if (in_array($recipient, $friends)) {
          $weekToMessagesMap[$weekTimeStamp][$recipient]['sent'] += 1;
        }
      }
    }
    else {
      // find out if the author is a firned
      if (in_array($author, $friends)) {
        $weekToMessagesMap[$weekTimeStamp][$author]['receieved'] += 1; 
      }
    }
  }

  return $weekToMessagesMap;
}

/**
 * Get the recipients of a message, where $user is the viewer of the message.
 */
function getMessageRecipients($message, $user, $db) {
  $thread = $message['thread_id'];
  // Get all the threads, to get the messages from.
  $stmt = $db->prepare("SELECT recipients from facebook_threads where thread_id={$thread}");
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();

  return json_decode($row['recipients']);
}

/**
 * Gets a list of friends, sorted in descending order based on the total
 * number of sent and receieved messages.
 *
 * Takes in array of messages.
 */
function getTopFriends($user, $messages, $numberOfFriends) {
  $friendCountMap = array();

  // get the number of messages related to each friend.
  foreach ($messages as $index => $message) {
    $author = $message['author_id'];
    if (isset($friendCountMap[$author])) {
      $friendCountMap[$author] += 1;
    }
    else {
      $friendCountMap[$author] = 1;
    }
  }

  // get the list of friends in descending order.
  arsort($friendCountMap);

  $friends = array();
  foreach ($friendCountMap as $friend_id => $numMessages) {
    if (count($friends) >= $numberOfFriends) return $friends;
    if ($friend_id != $user) {
      $friends[] = $friend_id;
    }
  }

  return $friends;
}

/**
 * Gets assoc array that maps friend id's to length of the thread
 * Note: this code is incomplete
 */
function getFriendToThreadLength($user) {
  // set up db
  require dirname(__FILE__).'/db.php';
  $db = get_db();

  $stmt = $db->prepare("SELECT message_count, recipients
                        from facebook_threads
                        where viewer_id = '{$user}'");

  $stmt->execute();
  $res = $stmt->get_result();
  $allRecipients = array();
  while ($row = $res->fetch_assoc()) {
    $recipients = "";
    $row_json = json_decode($row['recipients']);
    // Turn recipients in to a string.
    foreach ($row_json as $index => $recipient) {
      if ($recipient != $user) {
        $recipients = $recipients . $recipient . ",";
      }
    }
    $recipients = rtrim($recipients, ',');
    $allRecipients[$recipients] = $row['message_count'];
  }

  $numMessages = array();
  foreach ($allRecipients as $key => $row)
  {
      $numMessages[$key] = $row['price'];
  }
  // Sort the recipients based on the number of messages, decending.
  arsort($allRecipients);
  // echo data for all raw data, sorted
  // foreach ($allRecipients as $recips => $count) {
  //   echo '["'. $recips. '",'. $count. '],';
  // }

  $mostInfluential = array_slice($allRecipients, 0, 30, $preserve_keys = true);
  foreach ($mostInfluential as $recips => $count) {
    echo '["'. $recips. '",'. $count. '],';
  }

}
?>