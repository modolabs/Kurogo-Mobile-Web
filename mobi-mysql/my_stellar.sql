CREATE TABLE `MyStellarSubscription` (
  `device_id` INT NOT NULL,
  `device_type` CHAR(20) NOT NULL,
  `subject_id`  CHAR(10) NOT NULL,
  `term` CHAR(6) NOT NULL
) ENGINE=INNODB;