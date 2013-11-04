<?php
/**
 * Creates a row in the user_records table for the user if there is not
 * one already.
 */
function ensureEntryForUser($userId, $db) {
  // select something arbitrary to see if there is a row.
  $stmt = $db->prepare("SELECT 1 FROM user_records 
                        WHERE user_id='{$userId}'");
  $stmt->execute();
  $res = $stmt->get_result();

  // check for the case where there is no match in the db.
  if ($res->num_rows == 0) {
    // close the last statement and insert a new row in the table.
    $stmt->close();
    $stmt = $db->prepare("INSERT INTO user_records (user_id, has_threads, has_messages, has_posts)
                          Values (?, ?, ?, ?)");
    $hasThreads = '0';
    $hasMessages = '0';
    $hasPosts = '0';
    $stmt->bind_param('ssss', $userId, $hasThreads, $hasMessages, $hasPosts);

    if (!$stmt->execute()) {
      echo 'Failed to make row for user in user records<br />';
      echo $db->error, '<br />';
    }

    $stmt->close();
  }
}

/**
 * Checks whether a user's messages have been retrieved before.
 * Takes in a user id and a mysqli object to perform the lookup on.
 * Returns true if the messages have been retrieved before, false otherwise.
 */
function areMessagesPresent($userId, $db) {
  $stmt = $db->prepare("SELECT has_messages FROM user_records 
           						  WHERE user_id='{$userId}'");
  $stmt->execute();
  $res = $stmt->get_result();

  // check for the case where there is no match in the db.
  if ($res->num_rows == 0) {
    return false;
  }

  $row = $res->fetch_assoc();

  if ($row['has_messages'] == '1') {
    return true;
  }

  return false;
}

/**
 * Checks whether a user's threads have been retrieved before.
 * Takes in a user id and a mysqli object to perform the lookup on.
 * Returns true if the threads have been retrieved before, false otherwise.
 */
function areThreadsPresent($userId, $db) {
  $stmt = $db->prepare("SELECT has_threads FROM user_records 
              					WHERE user_id='{$userId}'");
  $stmt->execute();
  $res = $stmt->get_result();

  // check for the case where there is no match in the db.
  if ($res->num_rows == 0) {
    return false;
  }

  $row = $res->fetch_assoc();

  if ($row['has_threads'] == '1') {
    return true;
  }

  return false;
}

/**
 * Sets the user record as having retrieved messages before.
 * Takes in a user id and a mysqli object to perform the setting on.
 */
function setMessagesAsPresent($userId, $db) {
  $stmt = $db->prepare("UPDATE user_records SET has_messages = '1'
                        WHERE user_id = '{$userId}'");
  if (!$stmt->execute()) {
  	echo 'Failed to set messages as present in records<br />';
    echo $db->error, '<br />';
  }
  $stmt->close();
}

/**
 * Sets the user record as having retrieved threads before.
 * Takes in a user id and a mysqli object to perform the setting on.
 */
function setThreadsAsPresent($userId, $db) {
  $stmt = $db->prepare("UPDATE user_records SET has_threads = '1'
                        WHERE user_id = '{$userId}'");
  if (!$stmt->execute()) {
  	echo 'Failed to set threads as present in records<br />';
    echo $db->error, '<br />';
  }
  $stmt->close();
}
?>