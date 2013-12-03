<?php

// This script may take a while, so we're giving it 90 minutes to run!
ini_set('max_execution_time', 5400); 

// Get db.
require dirname(__FILE__).'/../php/db.php';
$db = get_db();

// we need this library to update the user_records table.
require dirname(__FILE__).'/user_records_helper.php';

// set up fb instance.
require dirname(__FILE__).'/../../src/facebook.php';

// get fb config
require dirname(__FILE__).'/../../fb_config.php';

$facebook = new Facebook(array(
  'appId'  => $appId,
  'secret' => $secret,
));

// close the session to not block.
session_write_close();

// Determine what action to take, based on the post variables.
if ($_POST['request'] == 'Get Messages') {
  // gets and stores messages for one year back.
  getAndStoreMessages(52, $facebook, $db);
}
else if ($_POST['request'] == 'Get Posts') {
  // gets and stores posts for one year back.
  getAndStorePosts(52, $facebook, $db);
}

/**
 * Get threads from user and store them in db.
 *
 * Takes in a the user's facebook object and the database object for the server.
 *
 * Returns an associative array of threads.
 */
function getAndStoreThreads($facebook, $db) {
  $userId = $facebook->getUser();
  $hashedUserId = hash('sha512', $userId);

  // finally, mark the threads as retrieved in the user_details table.
  setThreadsAsPresent($hashedUserId, $db);

  try {
    $threads = array();
    $inboxThreadsFinished = 0;
    $outboxThreadsFinished = 0;
    $offset = 0;

    // Get all the threads from the inbox
    while (!$inboxThreadsFinished) {
      $fql = 'SELECT viewer_id, thread_id, message_count, subject, originator, recipients, updated_time, unseen, unread, parent_message_id, has_attachment 
              from thread where folder_id=0 LIMIT 50 OFFSET '.$offset;
      $ret_obj = $facebook->api(array(
                                 'method' => 'fql.query',
                                 'query' => $fql,
                               ));
      // sleep(5); // sleep 5 seconds for small preservation of api rate limit
      usleep(0.5 * 1000000); // sleep half a second.
      if (empty($ret_obj)) {
        $inboxThreadsFinished = 1;
      }
      else {
        foreach ($ret_obj as $index => $thread) {
          $threads[$thread['thread_id']] = $thread;
        }
        $offset += 50;
      }
    }
    
    // reset the offset.
    $offset = 0;

    // Get all the threads from the outbox
    while (!$outboxThreadsFinished) {
      $fql = 'SELECT viewer_id, thread_id, message_count, subject, originator, recipients, updated_time, unseen, unread, parent_message_id, has_attachment 
              from thread where folder_id=0 LIMIT 50 OFFSET '.$offset;
      $ret_obj = $facebook->api(array(
                                 'method' => 'fql.query',
                                 'query' => $fql,
                               ));
      // sleep(5); // sleep 5 seconds for small preservation of api rate limit
      usleep(0.5 * 1000000); // sleep one-half a second.
      if (empty($ret_obj)) {
        $outboxThreadsFinished = 1;
      }
      else {
        // Go through all the outbox threads and if the thread isn't already added, add it.
        foreach ($ret_obj as $index => $thread) {
          if (!array_key_exists($thread['thread_id'], $threads)) {
            $threads[$thread['thread_id']] = $thread;
          }
        }
        $offset += 50;
      }
    }
    echo '<pre>number of threads: ', count($threads), '</pre>';

    // Store the threads in the database
    foreach ($threads as $threadId => $threadRow) {
      $stmt = $db->prepare("INSERT INTO facebook_threads (viewer_id, thread_id, message_count, originator, recipients, updated_time, unseen, unread, parent_message_id, has_attachment)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $viewer_id = hash('sha512', $threadRow['viewer_id']);
      $thread_id = hash('sha512', $threadRow['thread_id']);
      $message_count = $threadRow['message_count'];
      $originator = hash('sha512', $threadRow['originator']);
      foreach ($threadRow['recipients'] as $index => $recipient) {
        $threadRow['recipients'][$index] = hash('sha512', $recipient);
      }
      $recipients = json_encode($threadRow['recipients']);
      $updated_time = $threadRow['updated_time'];
      $unseen = $threadRow['unseen'];
      $unread = $threadRow['unread'];
      $parent_message_id = hash('sha512', $threadRow['parent_message_id']);
      $has_attachment = $threadRow['has_attachment'];
      $stmt->bind_param('ssisssiisi', $viewer_id, $thread_id, $message_count, $originator, $recipients, $updated_time, $unseen, $unread, $parent_message_id, $has_attachment);
      if (!$stmt->execute()) {
        echo '<br />Statement failed :(<br />';
      }
      $stmt->close();
    }

    return $threads;
  }
  catch (FacebookApiException $e) {
    error_log($e);
    print_r($e);
  }
}

/**
 * Get and store a user's messages for $howManyWeeksBack amount of time back.
 * This function assumes that all the threads for the user have been stored in the db.
 *
 * Takes in the facebook object for the user, and the database object.
 */
function getAndStoreMessages($howManyWeeksBack, $facebook, $db) {
  // Get userId.
  $userId = $facebook->getUser();
  $hashedUserId = hash('sha512', $userId);

  // Set the messages as obtained.
  setMessagesAsPresent($hashedUserId, $db);

  // get and store the user's threads.
  $threads = getAndStoreThreads($facebook, $db);

  // get earliest date to start from
  $earliestDate = strtotime('-'. $howManyWeeksBack .' week', time()); 

  try {
    // Get all the messages
    $messages = array();

    // Iterate over all threads
    foreach($threads as $threadId => $threadRow) {
      $threadDone = 0;
      $offset = 0;
      // In each thread, go over each message.
      while (!$threadDone) {
        $fql = "SELECT viewer_id, message_id, author_id, created_time, body, source, thread_id, attachment 
                FROM message 
                WHERE thread_id = ". $threadId . " AND created_time > " . $earliestDate . "
                LIMIT 30 OFFSET " . $offset;
        $ret_obj = $facebook->api(array(
                                   'method' => 'fql.query',
                                   'query' => $fql,
                                 ));              
        usleep(2.0 * 1000000); // sleep two seconds.
        if (empty($ret_obj)) {
          $threadDone = 1;
        }
        else {
          foreach ($ret_obj as $index => $message) {
            $messages[] = $message;
          }
          $offset += 30;
        }
      }

      // Store the messages in the database
      foreach ($messages as $index => $threadRow) {
        $stmt = $db->prepare("INSERT INTO facebook_messages (viewer_id, message_id, author_id, created_time, source, thread_id, has_attachment)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
        $viewer_id = hash('sha512', $threadRow['viewer_id']);
        $message_id = hash('sha512', $threadRow['message_id']);
        $author_id = hash('sha512', $threadRow['author_id']);
        $created_time = $threadRow['created_time'];
        $source = $threadRow['source'];
        $thread_id = hash('sha512', $threadRow['thread_id']);
        // get whether or not the message has an attachment.
        if (empty($threadRow['attachment'])) {
          $attachment = 0;
        }
        else {
          $attachment = 1;
        }
        $stmt->bind_param('ssssssi', $viewer_id, $message_id, $author_id, $created_time, $source, $thread_id, $attachment);
        if (!$stmt->execute()) {
          echo '<br />Statement failed :(<br />';
        }
        $stmt->close();
      }
      // Refresh messages array as to not run out of memory.
      $messages = array();
    }
  }
  catch (FacebookApiException $e) {
    error_log($e);
    print_r($e);
  }
}

/**
 * Get and store a user's posts for $howManyWeeksBack amount of time back.
 * 
 * Takes in the facebook object for the user and the database object.
 */
function getAndStorePosts($howManyWeeksBack, $facebook, $db) {
  // Get userId.
  $userId = $facebook->getUser();

  // get earliest date to start from
  $earliestDate = strtotime('-'. $howManyWeeksBack .' week', time());
  
  try {
    // Get all the posts.
    $posts = array();
    
    $postsFinished = 0;
    $offset = 0;

    // Get all the threads from the inbox
    //while (!$postsFinished) {
      //$fql = 'SELECT message, time
      //        FROM status
      //        WHERE uid=615474653
      //        ORDER BY time desc
      //        LIMIT 100 OFFSET 0';
      $fql = 'SELECT type, permalink, actor_id, target_id, message, created_time
              from stream where source_id=me() and created_time < 1335995546
              LIMIT 10000';
      $ret_obj = $facebook->api(array(
                                 'method' => 'fql.query',
                                 'query' => $fql,
                               ));
      usleep(2.0 * 1000000); // sleep for two seconds.
      //if (empty($ret_obj)) {
      //  $postsFinished = 1;
      //}
      //else {
        foreach ($ret_obj as $index => $post) {
          $posts[] = $post;
        }
      //  $offset += 2000;
      //}
    //}
    
    print_r($posts);
  }
  catch (FacebookApiException $e) {
    error_log($e);
    print_r($e);
  }
}
?>