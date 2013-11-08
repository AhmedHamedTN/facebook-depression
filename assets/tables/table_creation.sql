CREATE TABLE `facebook_threads` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `viewer_id` varchar(60) NOT NULL,
 `thread_id` varchar(60) NOT NULL,
 `message_count` int(11) NOT NULL,
 -- `subject` varchar(200) NOT NULL,
 `originator` varchar(60) NOT NULL,
 `recipients` text NOT NULL,
 `updated_time` varchar(10) NOT NULL,
 `unseen` tinyint(1) NOT NULL,
 `unread` int(11) NOT NULL,
 `parent_message_id` varchar(60) NOT NULL,
 `has_attachment` tinyint(1) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `thread_id` (`thread_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `facebook_messages` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `viewer_id` varchar(60) NOT NULL,
 `message_id` varchar(60) NOT NULL,
 `author_id` varchar(60) NOT NULL,
 `created_time` varchar(10) NOT NULL,
 `source` int(11) NOT NULL,
 `thread_id` varchar(60) NOT NULL,
 `attachment` text NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `message_id` (`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `user_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(60) NOT NULL,
  `has_threads` varchar(1) NOT NULL,
  `has_messages` varchar(1) NOT NULL,
  `has_posts` varchar(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;