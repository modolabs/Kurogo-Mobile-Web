CREATE TABLE `AppleDevice` (
  `device_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pass_key` CHAR(10) NOT NULL,
  `device_token` CHAR(64) NULL,
  `last_updated` INT(12) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 0,
  `unread_notifications` VARCHAR(500) NOT NULL DEFAULT '[]'
);

CREATE TABLE `ApplePushNotification` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` INT NOT NULL,
  `payload` VARCHAR(256) NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  `has_badge` INT DEFAULT 1,
  `created_unixtime` INT(12) NOT NULL,
  `sent_unixtime` INT(12) NULL,
  `undeliverable_unixtime` INT(12) NULL
);