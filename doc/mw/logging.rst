=================
Logging in Kurogo
=================

Kurogo includes a facility for logging normal and exceptional events. The purpose of the
logging system is to inform system administrators of important conditions that may need to 
be fixed, as well as providing a way for developers to debug and trace Kurogo code paths. 

By default Kurogo events are logged in *SITE_DIR/logs/kurogo.log*. Each entry has several
parts:

:kbd:`[date/time] area:priority method URI message`

The area is a string that represents the Kurogo component responsible for this message.
Kurogo defines several standard areas, but developers can use any string to isolate their
messages into categories. The priority is one of several values that represents severity 
of the message. These priorities map to the priorities of the `syslog function <http://php.net/manual/en/function.syslog.php>`_.
The function/method helps you determine where this message originated. The URI is the address
the user used to view the page.

------------
Log Settings
------------

There are several settings that affect the behavior of the Kurogo log. All are located in
*SITE_DIR/config/site.ini*

* *KUROGO_LOG_FILE* (default LOG_DIR/kurogo.log)  - This is the location of Kurogo log
  file. It must be writable by the webserver. This is where all log messages will be saved.
* *DEFAULT_LOGGING_LEVEL* - Sets the logging level. Only messages at this level or higher 
  will be logged. All other messages will be discarded. 
* *LOGGING_LEVEL[area]* - Sets the logging level for a particular area. This is useful
  for developers that only want to see informational or debugging messages for certain area.
  This level will override the DEFAULT_LOGGING_LEVEL for this area. You can override any number
  of areas.


---------------
Priority Levels
---------------

These are the priority level constants used by Kurogo. Most systems should set this to LOG_WARNING.
If you set it to LOG_INFO or LOG_DEBUG you will get a large number of messages. Generally you would only
set it lower than warning for debugging purposes, and then only in certain areas. 

* LOG_EMERG - system is unusable
* LOG_ALERT - action must be taken immediately
* LOG_CRIT -critical conditions
* LOG_ERR - error conditions
* LOG_WARNING - warning conditions
* LOG_NOTICE -normal, but significant, condition
* LOG_INFO -informational message
* LOG_DEBUG	- debug-level message

------------------------
Standard areas in Kurogo
------------------------

The following are areas used by internal Kurogo areas to separate logging messages. Developers
are free to use any area they wish to log their own messages:

* admin - Admin console
* auth - Authentication and authorization
* config - Configuration
* data - Used by libraries that retrieve external data.
* db - Database 
* deviceDetection - Device Detection
* exception - Exceptions
* kurogo - Core functions including initialization
* module - General module events
* session - User session management
* template - HTML templates

---------------------------------
Logging in your module or library
---------------------------------

The *Kurogo::log($priority, $message, $area)* method is used to send a message to the log. The 
$priority parameter should be on of the priority level constants, the message should be a
string and the area should be a string of the area you wish to log. You could use LOGGING_LEVEL[area]
to set the logging for your area as necessary.