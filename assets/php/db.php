<?php
  // Gets db object.
  function get_db() {
    require_once dirname(__FILE__).'/../../config.php';

    static $db;

    if (!$db) {
      $db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
    }

    return $db;
  }
?>