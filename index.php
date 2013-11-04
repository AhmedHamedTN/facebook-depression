<?php

/* Get db. */
require 'assets/php/db.php';
$db = get_db();

/* set up db instance. */
require 'src/facebook.php';

require 'fb_config.php';

require 'assets/php/user_records_helper.php';

$facebook = new Facebook(array(
  'appId'  => $appId,
  'secret' => $secret,
));

/* Get user. */
$userId = $facebook->getUser();

/* Create login/logout links. */
if ($userId) {
  // $logoutUrl = $facebook->getLogoutUrl(array(
  //   'next' => dirname($_SERVER["SERVER_NAME"] . $_SERVER['REQUEST_URI']) . '/logout.php'
  // ));
  $logoutUrl = 'logout.php';

  // also make sure there is a user_records entry for this user.
  ensureEntryForUser($userId, $db);
} else {
  $params = array(
  'scope' => 'read_mailbox, read_stream, read_insights',
  'redirect_uri' => 'http://localhost/facebook-depression/index.php'
  );  
  $loginUrl = $facebook->getLoginUrl($params);
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Lucy</title>

    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
    <style>

    /* GLOBAL STYLES
    -------------------------------------------------- */
    /* Padding below the footer and lighter body text */

    body {
      padding-bottom: 40px;
      color: #ffffff;
    }

    .actionButton {
      cursor: default;
    }

    /* CUSTOMIZE THE NAVBAR
    -------------------------------------------------- */

    /* Special class on .container surrounding .navbar, used for positioning it into place. */
    .navbar-wrapper {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 10;
      margin-top: 20px;
      margin-bottom: -90px; /* Negative margin to pull up carousel. 90px is roughly margins and height of navbar. */
    }
    .navbar-wrapper .navbar {

    }

    /* Remove border and change up box shadow for more contrast */
    .navbar .navbar-inner {
      border: 0;
      -webkit-box-shadow: 0 2px 10px rgba(0,0,0,.25);
         -moz-box-shadow: 0 2px 10px rgba(0,0,0,.25);
              box-shadow: 0 2px 10px rgba(0,0,0,.25);
    }

    /* Downsize the brand/project name a bit */
    .navbar .brand {
      padding: 14px 20px 16px; /* Increase vertical padding to match navbar links */
      font-size: 16px;
      font-weight: bold;
      text-shadow: 0 -1px 0 rgba(0,0,0,.5);
    }

    /* Navbar links: increase padding for taller navbar */
    .navbar .nav > li > a {
      padding: 15px 20px;
    }

    /* Offset the responsive button for proper vertical alignment */
    .navbar .btn-navbar {
      margin-top: 10px;
    }



    /* RESPONSIVE CSS
    -------------------------------------------------- */

    @media (max-width: 979px) {

      .container.navbar-wrapper {
        margin-bottom: 0;
        width: auto;
      }
      .navbar-inner {
        border-radius: 0;
        margin: -20px 0;
      }

      .carousel .item {
        height: 700px;
      }
      .carousel img {
        width: auto;
        height: 700px;
      }

      .featurette {
        height: auto;
        padding: 0;
      }
      .featurette-image.pull-left,
      .featurette-image.pull-right {
        display: block;
        float: none;
        max-width: 40%;
        margin: 0 auto 20px;
      }
    }


    @media (max-width: 767px) {

      .navbar-inner {
        margin: -20px;
      }

      .carousel {
        margin-left: -20px;
        margin-right: -20px;
      }
      .carousel .container {

      }
      .carousel .item {
        height: 300px;
      }
      .carousel img {
        height: 300px;
      }
      .carousel-caption {
        width: 65%;
        padding: 0 70px;
        margin-top: 100px;
      }
      .carousel-caption h1 {
        font-size: 30px;
      }
      .carousel-caption .lead,
      .carousel-caption .btn {
        font-size: 18px;
      }

      .marketing .span4 + .span4 {
        margin-top: 40px;
      }

      .featurette-heading {
        font-size: 30px;
      }
      .featurette .lead {
        font-size: 18px;
        line-height: 1.5;
      }

    }
    </style>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="../assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
      <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
                    <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
                                   <link rel="shortcut icon" href="../assets/ico/favicon.png">
</head>

<body>
	<p></p>
	<!-- NAVBAR
	    ================================================== -->
	<div class="navbar-wrapper"><!-- Wrap the .navbar in .container to center it within the absolutely positioned parent. -->
	<div class="container">
	<div class="navbar navbar-inverse">
	<div class="navbar-inner"><!-- Responsive Navbar Part 1: Button for triggering responsive navbar (not covered in tutorial). Include responsive CSS to utilize. --> <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button> <a class="brand" href="index.php">Lucy</a> <!-- Responsive Navbar Part 2: Place all navbar contents you want collapsed withing .navbar-collapse.collapse. -->
	<div class="nav-collapse collapse">
    <ul class="nav">
    </ul>
	</div>

	<!--/.nav-collapse --></div>
	<!-- /.navbar-inner --></div>
	<!-- /.navbar --></div>
	<!-- /.container --></div>
	<!-- /.navbar-wrapper -->
	<p><br /><br /><br /><br /></p>
	<div class="container">
	<div class="row-fluid">
	<div class="hero-unit span12">
	<div class="span4"><img src="assets/images/rice.png" /></div>
	<div class="span8">
	<p>
    <!-- Login/Logout links. -->
    <?php if ($userId): ?>
      <a href="<?php echo $logoutUrl; ?>">Logout</a>
    <?php else: ?>
      <div>
        <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
      </div>
    <?php endif ?>

    <!-- If the user is logged in, do things. -->
    <?php if ($userId): ?>
      <h3>You</h3>
      <img src="https://graph.facebook.com/<?php echo $userId; ?>/picture">
      <div>
        <h3>Raw Data Fetching</h3>
        <?php
          // generate buttons, based on whether they have been activated before.
          if(areThreadsPresent($userId, $db)) {
            echo '<button id="getThreads" class="actionButton" disabled>Get Threads</button>';
          }
          else {
            echo '<button id="getThreads" class="actionButton">Get Threads</button>';
          }

          // generate buttons, based on whether they have been activated before.
          if(areMessagesPresent($userId, $db)) {
            echo '<button id="getMessages" class="actionButton" disabled>Get Messages</button>';
          }
          else {
            echo '<button id="getMessages" class="actionButton">Get Messages</button>';
          }
        ?>
        <button id="getPosts" class="actionButton">Get Posts</button>
      </div>

      <div>
        <h3>Data Processing</h3>
        <form action="assets/php/fb_analysis.php" method="post">
          <input type="Submit" name="request" value="process some data for me" />
          <input type="Submit" name="request" value="Debug" />
        </form>
      </div>

      <div>
        <h3>Graph Generation</h3>
        <form action="assets/php/fb_plots.php" method="post">
          <input type="Submit" name="request" value="Sent:Receieved Graph" />
          <input type="Submit" name="request" value="Show Sent:Receieved Graph vs. Best Friends" />
        </form>
      </div>


    <?php else: ?>
      <strong><em>You are not Connected.</em></strong>
    <?php endif ?>
	</p>
	</div>
	</div>
	</div>
	</div>
	<!-- Le javascript
	    ================================================== -->
	<p></p>
	<!-- Placed at the end of the document so the pages load faster -->
	<script src="bootstrap/js/jquery.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-transition.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-alert.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-modal.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-dropdown.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-scrollspy.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-tab.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-tooltip.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-popover.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-button.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-collapse.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-carousel.js" type="text/javascript"></script>
	<script src="bootstrap/js/bootstrap-typeahead.js" type="text/javascript"></script>
  <script type="text/javascript">// <![CDATA[
  
  $("#getThreads").click(function() {
    if($(this).attr("disabled") !== true) {
      $.ajax({
        type: "POST",
        url: "assets/php/fb_dumping.php",
        data: {request: "Get Threads"}
      });

      alert("Your Facebook message threads are now being processed!");

      $(this).attr("disabled", true);
    }
  });

  $("#getMessages").click(function() {
    if($(this).attr("disabled") !== true) {
      $.ajax({
        type: "POST",
        url: "assets/php/fb_dumping.php",
        data: {request: "Get Messages"}
      });

      alert("Your Facebook messages are now being processed!");

      $(this).attr("disabled", true);
    }
  });

	!function ($) {
	        $(function(){
	          // carousel demo
	          $('#myCarousel').carousel()
	        })
	      }(window.jQuery)
	// ]]></script>
	<script src="bootstrap/js/holder.js" type="text/javascript"></script>
</body>
</html>