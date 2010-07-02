# Requirements
* Apache server
* PHP 5.2 or greater (including the command line interface to PHP)
* MySQL 5 or greater (optional)
* Separate server running MIT browser detection

## PHP dependencies
* XML extension is REQUIRED.  Most installations of PHP include it, though some Linux distros may have to install it separately.
* MySQL is required if you want mobile analytics and push notification functionality.
* LDAP is required if you are using People Directory module
* PEAR is required if you are using push notification functionality.  The following PEAR modules are used:

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
* mobi-scripts/
Contains scripts to start iPhone push notification processes, and scripts to download map tiles from an ArcGIS server.
For convenience, these will be copied to the system's /path/to/mitmobile/bin directory.
* iPhone-app/  
Contains the XCode project and all the objective-C and other resources used to build the MIT iPhone application
* mobi-web/api/  
Contains the front facing scripts that the iPhone application calls.
* mobi-web/api/push/  
Contains the scripts that run in the background processing notifications
* opt/
Non-apache files that will be copied to the system.  Apache will write to some directories, so be careful of permissions.
* setup/
Files used for installation.  install-mobiweb.sh and uninstall-mobiweb.sh are the only ones that work so far.

Directory names that start with "mobi" are server-side components.

## Installation Process
Clone this repository:
``git clone git@github.com:modolabs/modo-university.git``

For Red Hat systems, it is recommended to use the RPM script (forthcoming) because of package requirements and SELinux permissions.  Some specifics are documented in the working spec file ``setup/mitmobile-web-2.1.spec``

For other systems (tested on MAMP):

* Edit ``setup/install-mobiweb.sh``.  You MUST ensure that $PREFIX0 and $PREFIX1 point to legitimate directories on the system.  If MySQL is installed, you SHOULD edit the MYSQL.

``cd setup``
``vi install-mobiweb.sh``
``sudo ./install-mobiweb.sh``

* To uninstall:

``cd /path/to/modo-university/setup``
``sudo ./uninstall-mobiweb.sh``

* To make changes, you can edit any files in the directories mobi-* and run the install script to update them on your system.  After saving changes:

``cd /path/to/modo-university/setup``
``sudo ./install-mobiweb.sh --update``

The ``--update`` option will copy all the files, but skip MySQL setup.

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

## Further Documentation
We will be adding more technical [documentation](http://imobileu.webfactional.com) using Sphinx and doxygen.
The Sphinx documentation is containted in ``docs/`` directory and is intended for higher level information, the doxygen documentation is code comments embedded in the code.

## Notes
* php magic quotes MUST be disabled
* PHP short tags MUST be enabled
* error_reporting is set as follows:  
``error_reporting = E_ALL & ~E_NOTICE``


