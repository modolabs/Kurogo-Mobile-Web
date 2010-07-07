-- Apple device tables

DROP TABLE IF EXISTS `AppleDevice`;

CREATE TABLE `AppleDevice` (
  `device_id` int(11) NOT NULL auto_increment,
  `pass_key` char(10) NOT NULL,
  `device_token` char(64) default NULL,
  `last_updated` int(12) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  `unread_notifications` varchar(500) NOT NULL default '[]',
  PRIMARY KEY  (`device_id`)
);

DROP TABLE IF EXISTS `ApplePushNotification`;

CREATE TABLE `ApplePushNotification` (
  `id` int(11) NOT NULL auto_increment,
  `device_id` int(11) NOT NULL,
  `payload` varchar(256) NOT NULL,
  `tag` varchar(50) NOT NULL,
  `has_badge` int(11) default '1',
  `created_unixtime` int(12) NOT NULL,
  `sent_unixtime` int(12) default NULL,
  `undeliverable_unixtime` int(12) default NULL,
  PRIMARY KEY  (`id`)
);

-- push notification tables

DROP TABLE IF EXISTS `DisabledModule`;

CREATE TABLE `DisabledModule` (
  `device_id` int(11) NOT NULL,
  `device_type` char(20) NOT NULL,
  `module` char(50) NOT NULL
);

DROP TABLE IF EXISTS `MyStellarSubscription`;

CREATE TABLE `MyStellarSubscription` (
  `device_id` int(11) NOT NULL,
  `device_type` char(20) NOT NULL,
  `subject_id` char(10) NOT NULL,
  `term` char(6) NOT NULL
);

DROP TABLE IF EXISTS `ShuttleSubscription`;

CREATE TABLE `ShuttleSubscription` (
  `device_id` int(11) NOT NULL,
  `device_type` char(20) NOT NULL,
  `route_id` char(31) NOT NULL,
  `stop_id` char(15) NOT NULL,
  `start_time` int(11) default NULL,
  UNIQUE KEY `shuttle_subscription_unique` (`device_id`,`device_type`,`route_id`,`stop_id`)
);

-- native app analytics

DROP TABLE IF EXISTS `mobi_api_requests`;

CREATE TABLE `mobi_api_requests` (
  `day` date default NULL,
  `platform` char(31) default NULL,
  `module` char(31) default NULL,
  `viewcount` int(11) default NULL,
  UNIQUE KEY `api_unique` (`day`,`platform`,`module`)
)
