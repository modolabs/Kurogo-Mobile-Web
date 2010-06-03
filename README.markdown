# Requirements
* A LAMP(Linux Apache MySQL PHP) server.
* PHP 5.2 or greater (including the command line interface to PHP)
* MySQL 5 or greater
* mit-mobile-browser-detection installed on a server
* pngcrush

## Required PHP dependencies
* MySQL module
* LDAP module
* PEAR
* pear library System_Daemon-0.9.2 (only for daemon processes that send notifications to iPhones)  
``pear install System_Daemon-0.9.2``
* pear library Log  
``pear install Log``

## Notes about the directory structure
* mobi-web/  
Contains the outside facing web scripts.
* mobi-lib/  
Contains libraries that mobi-web uses, to talk to various services at MIT
* mobi-config/  
Contains configuration files, each file needs to be copied and stripped of .init extension.
* mobi-push/  
Contains the script which starts the background processes with send push notifications to the iPhone, sometimes called Apple Push Notifications or (APNS) for short.
* iPhone-app/  
Contains the XCode project and all the objective-C and other resources used to build the MIT iPhone application
* mobi-web/api/  
Contains the front facing scripts that the iPhone application calls.
* mobi-web/api/push/  
Contains the scripts that run in the background processing notifications


## Installation Process
Install the source code such that the mobi-web directory is in a web readable directory, this is where the web facing scripts live.  
``$ cd mobi-config``  
``$ cp mobi_constants.php.init mobi_constants.php``  
``$ cp mobi_lib_config.php.init mobi_lib_config.php``  
``$ cp mobi_lib_constants.php.init mobi_lib_constants.php``  
``$ cp ldap_config.php.init ldap_config.php``  
``$ cp web_constants.php.init web_constants.php``    

Create a MySQL database, and configure the username, database name, and password in
mobi\-config/mobi\_lib\_config.php. Next, populate the MySQL database, this can be done by running the SQL script found at mobi-mysql/source\_all.sql  
``mysql> create database database_name;``  
``mysql> use database_name;``  
``mysql> source source_all.sql;``  

Next configure mobi-config/mobi\_lib\_config.php with the database settings you just set up.

Next in mobi-config/mobi\_web\_constants.php configure MOBI\_SERVICE\_URL to point to your instance, of the mit browser detection software, it by default points to an MIT development server, which may work for testing purposes.

Some files and log are stored outside of the main path, you will need to create an auxillary path, with the same permissions as the web server.  Then configure the variable AUX_PATH in mobi-config/mobi_constants.php to point to this path.  To set up the directory structure inside this path run:  
``$ php setup_aux_dirs.php``

## Running Apple Push Notification Daemon Scripts
Need to save the push certificates as .pem files somewhere on the server, and configure the following variables in mobi-config/mobi\_web\_constants.php: ``APNS_CERTIFICATE_DEV``, ``APNS_CERTIFICATE_DEV_PASSWORD``, ``APNS_CERTIFICATE_PROD``, ``APNS_CERTIFICATE_PROD_PASSWORD``, ``APNS_SANDBOX``, ``APPLE_RELEASE_APP_ID``.

Also need to configure the start-up script, (as the web user)  
``$ cd mobi-push``  
``$ cp configure_paths.sh.init configure_paths.sh``    
The AUX\_PATH should be the same path used in mobi-config/mobi\_web\_constants.php, and DOCUMENT\_ROOT should be the web servers DOCUMENT\_ROOT    
Now you can start the daemon processes with  
``$ cd scripts``  
``$ ./mobi-daemons.sh start``

## Downloading iPhone map tiles
The map module of iPhone application requires the web server to download all the map tiles from the maps.mit.edu server, after they are downloaded they are processed by pngcrush (this needs to be installed on the server). As with the mobi-daemons.sh, you need to make sure configure\_paths.sh is configured correctly, the map tiles are saved in the auxillary path.  To download and process all the map tiles run:  
``$ cd scripts``  
``$ ./mobi-maptiles.sh``  
(This can take quite a long time, ran locally from MIT it takes about 3-4 hours)

## Building the iPhone application
You can build the iPhone application on a Mac by opening ``"iPhone-app/MIT Mobile.xcodeproj"`` in XCode.  By default the iPhone app connects to MIT mobile servers, either development, staging or production.  If you would like it to connect to the webserver you have installed and configure, then edit the domain names and URLs at the top of iPhone-app/Common/MITConstants.m

In order to compile the application for a device you will need to change the APP ID saved in iPhone-app/MIT_Mobile-Info.plist from edu.example.mitmobile to the APP ID you configured in your apple developers portal.

## Notes
* php magic quotes must be disabled
* error_reporting is set as follows:  
``error_reporting = E_ALL & ~E_NOTICE``


