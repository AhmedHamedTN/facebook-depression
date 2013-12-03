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
else if ($_POST['request'] == 'Get Wall Posts') {
  // gets and stores wall posts for one year back.
  getAndStoreWallPosts(52, $facebook, $db);
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
 * Get and store a user's wall posts for $howManyWeeksBack amount of time back.
 * 
 * Takes in the facebook object for the user and the database object.
 */
function getAndStoreWallPosts($howManyWeeksBack, $facebook, $db) {
  // Get userId.
  $userId = $facebook->getUser();

  // set the wall posts as present in the database.
  $hashedUserId = hash('sha512', $userId);
  setWallPostsAsPresent($hashedUserId, $db);

  // get earliest date to start from
  $earliestDate = strtotime('-'. $howManyWeeksBack .' week', time());
  
  try {
    // Get all the posts.
    $posts = array();
    
    $postsFinished = 0;
    $offset = 0;
    $timeToStepFrom = time();

    // Get all the threads from the inbox
    while (!$postsFinished) {
      $fql = 'SELECT type, post_id, actor_id, tagged_ids, share_count, like_info, is_hidden, is_published, comment_info, attribution, attachment, created_time
              from stream where source_id=me() and created_time < ' . $timeToStepFrom . '
              LIMIT 10000';
      $ret_obj = $facebook->api(array(
                                 'method' => 'fql.query',
                                 'query' => $fql,
                               ));

      usleep(2.0 * 1000000); // sleep for two seconds.

      // go through each post and return
      $lastTime;

      // go through each returned item in the stream and check if it is a post.
      foreach ($ret_obj as $index => $post) {
        if ($post['created_time'] < $earliestDate) {
          $postsFinished = 1;
          break;
        }
        else if ($post['type'] == 46 || $post['type'] == 247 || $post['type'] == 128 || $post['type'] == 56 || $post['type'] == 308) {
          // post types: 46 - status, 247 - picture post, 128 - video posted, 56 - post on wall from other user, 308 - post in group.
          $posts[] = $post;
        }

        // keep track of the last time in the stream.
        $lastTime = $post['created_time'];
      }

      $timeToStepFrom = $lastTime;
    }

    // now that we have posts, store them.
    foreach ($posts as $index => $post) {
      $stmt = $db->prepare("INSERT INTO facebook_wall_posts (type, post_id, created_time, like_count, attribution, comment_count, is_hidden, is_published, has_attachment)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $postType = $post['type'];
      $postId = hash('sha512', $post['post_id']);
      $createdTime = $post['created_time'];
      $likeCount = $post['like_info']['like_count'];
      $attribution = "";
      if ($post['attribution'] != null) {
        $attribution = hash('sha512', $post['attribution']);
      }
      $commentCount = $post['comment_info']['comment_count'];
      // whether the post is hidden.
      $isHidden = 0;
      if ($post['is_hidden'] != null) {
        $isHidden = 1;
      }
      // whether the post is published.
      $isPublished = 0;
      if ($post['is_published'] != null) {
        $isPublished = 1;
      }
      $hasAttachment = 1;
      // get whether or not the post has an attachment.
      if (empty($post['attachment'])) {
        $hasAttachment = 0;
      }

      // bind each param, and perform the insert.
      $stmt->bind_param('issisiiii', $postType, $postId, $createdTime, $likeCount, $attribution, $commentCount, $isHidden, $isPublished, $hasAttachment);
      if (!$stmt->execute()) {
        echo '<br />Statement failed :(<br />';
      }
      $stmt->close();
    }
  }
  catch (FacebookApiException $e) {
    error_log($e);
    print_r($e);
  }
}
?>