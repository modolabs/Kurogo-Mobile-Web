# Requirements
* A LAMP(Linux Apache MySQL PHP) server.
* PHP 5.2 or greater (including the command line interface to PHP)
* MySQL 5 or greater
* mit-mobile-browser-detection installed on a server

## Required PHP dependencies
* MySQL module
* PEAR

## Installation Process
Install the source code such that DOCUMENT\_ROOT points to mobi-web directory. In mobi-config directory copy the three configuration files.  
``$ cd mobi-config  
$ cp mobi_lib_config.php.init mobi_lib_config.php  
$ cp mobi_web_config.php.init mobi_web_config.php  
$ cp mobi_web_constants.php.init mobi_web_constats.php  
``

Create a MySQL database, and configure the username, database name, and password in
mobi\-config/mobi\_lib\_config.php. Next, populate the MySQL database, this can be done by running the SQL script found at mobi-sql/source\_all.sql
