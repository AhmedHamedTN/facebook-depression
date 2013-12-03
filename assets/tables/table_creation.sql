CREATE TABLE `facebook_threads` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `viewer_id` varchar(128) NOT NULL,
 `thread_id` varchar(128) NOT NULL,
 `message_count` int(11) NOT NULL,
 `originator` varchar(128) NOT NULL,
 `recipients` text NOT NULL,
 `updated_time` varchar(10) NOT NULL,
 `unseen` tinyint(1) NOT NULL,
 `unread` int(11) NOT NULL,
 `parent_message_id` varchar(128) NOT NULL,
 `has_attachment` tinyint(1) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `thread_id` (`thread_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `facebook_messages` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `viewer_id` varchar(128) NOT NULL,
 `message_id` varchar(128) NOT NULL,
 `author_id` varchar(128) NOT NULL,
 `created_time` varchar(10) NOT NULL,
 `source` int(11) NOT NULL,
 `thread_id` varchar(128) NOT NULL,
 `has_attachment` tinyint(1) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `message_id` (`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `facebook_wall_posts` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `type` int(11) NOT NULL,
 `post_id` varchar(128) NOT NULL,
 `created_time` varchar(10) NOT NULL,
 `like_count` int(11) NOT NULL,
 `attribution` varchar(128) NOT NULL,
 `comment_count` int(11) NOT NULL,
 `is_hidden` tinyint(1) NOT NULL,
 `is_published` tinyint(1) NOT NULL,
 `has_attachment` tinyint(1) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `post_id` (`post_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `user_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(128) NOT NULL,
  `has_threads` varchar(1) NOT NULL,
  `has_messages` varchar(1) NOT NULL,
  `has_wall_posts` varchar(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;