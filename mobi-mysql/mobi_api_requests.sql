
CREATE TABLE `mobi_api_requests` (
  `day` date default NULL,
  `platform` char(31) default NULL,
  `module` char(31) default NULL,
  `viewcount` int(11) default NULL,
  UNIQUE KEY `api_unique` (`day`,`platform`,`module`)
)

