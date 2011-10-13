#################
Device Detection
#################

One of the powerful features of the Kurogo framework is the ability to detect various devices and 
format content based on that device's capabilities. To support the classification of devices, the 
framework uses a Device Detection Server that contains a database of devices and outputs a normalized
set of properties.

=================================
Types of Device Detection Servers
=================================

Kurogo includes an internal device detection server that parses the user agent of the user's device
and returns an appropriate series of values. It contains a json dataset, located at lib/deviceData.json, 
that contains a series of patterns and will return the values that match that pattern. In addition, you
can define a custom dataset file which contains extensions and/or overrides to the cannonical dataset.
This custom file is searched before the canonical dataset, allowing you to control the entire process
of detecting devices. 

There is also an external device detection service available. The advantage of this service is that it
will contain a more up-to-date database of new devices. There are 2 urls available. One is suitable for
development and one for production. 

See :ref:`Device Detection Configuration <devicedetection_config>` for specific configuration values.

===========
Data Format
===========

The Kruogo Framework queries the device detection service using the *user agent* of the user's browser.
The service will then return a series of properties based on the device:

* *pagetype* - String. One of the device *buckets* that determines which major source of HTML the device
  will received. Values include *basic*, *touch*, *compliant* and *tablet*
* *platform* - The specific type of device. Values include *ANDROID*, *BBPLUS*, *BLACKBERRY*, *COMPUTER*, 
  *FEATUREPHONE*, *IPHONE*, *IPAD*, *PALMOS*, *SPIDER*, *SYMBIAN*, *WEBOS*, *WINMO*, *WINPHONE7*
* *supports_certificates* - Boolean. Whether this devices supports certificate based authentication
* *description* - a textual description of the device

The *pagetype* and *platform* properties are assigned to the :doc:`module object <modules>` as properties. 

=============
Configuration
=============

There are several configuration values that affect the behavior of the device detection service. They 
are located in *SITE_DIR/config/site.ini*:

* *MOBI_SERVICE_VERSION* - Includes the version of device detection to use. Provided for compatibility.
* *MOBI_SERVICE_USE_EXTERNAL* - Boolean. If 0, Kurogo will use the internal device detection server. If 1 it will use an external server
* *MOBI_SERVICE_SITE_FILE* - Location of site-specific device detection data if using internal detection. (typically located in *DATA_DIR/deviceData.json*)
* *MOBI_SERVICE_URL* - URL of device detection server if using external detection

  * (Development) https://modolabs-device-test.appspot.com/api/
  * (Production) https://modolabs-device.appspot.com/api/

* *MOBI_SERVICE_CACHE_LIFETIME* - Time (in seconds) to keep cached results from the external device detection service

-----------------
Debugging Options
-----------------

* *DEVICE_DETECTION_DEBUG* - When you turn this value on, you will see the device detection information
  on the bottom of the home screen. This is useful if you wish to see how a particular device is classified.
  If you feel a device is improperly classified, please send a note to kurogo-dev@googlegroups.com with 
  the user agent of the device/browser. 
* *DEVICE_DEBUG* - When turned on, this permits you to change the device pagetype and platform used for a
  given request. This is useful to test behavior and style for other devices that you do not have in your
  possession using your desktop browser. Simply prepend /device/pagetype-platform/ to your request:
  
  * http://server/device/basic/home
  * http://server/device/tablet-ipad/news
  
=================================
Customizing your Device Detection
=================================

Kurogo 1.3 changed how it accesses device detection files, making it much easier to modify and/or override specific device detections, without sacrificing speed.

Kurogo uses a JSON format to control it's device detection process.  This allows for both fast detection and
easy modification/extension of the specified devices.  There is a schema defined for this file, located at
*LIB_DIR/deviceDataSchema.json*.  For more information about JSON Schemas, visit http://json-schema.org/.
An example custom file can be found at *DATA_DIR/sampleDeviceData.json*.

The sample deviceData.json file is reproduced below with comments added to help describe the format.  Note that comments are not allowed in the actual file, in order to conform to json specifications::

    {
        // All deviceData files consist of an object containing a single property, "devices".  devices is an array of device blocks.
        "devices" : [
            {
                // A device block contains several properties:
                
                // "groupName" : a machine readable identification of the device or device group.
                "groupName" : "exampleDevice",
                
                // "description" : a human readable identification of the device or device group.
                "description" : "Both revisions of the Example Device produced by Example Company, Inc.",
                
                // "classification" : a versioned set of identifications to be passed to kurogo once a device is matched
                "classification" : {
                    // By convention, the highest version number is placed first in the file.
                    "2" : {
                        // The full list of supported pagetypes and platforms are defined
                        // in the associated schema.
                        // Note: The schema defines a specified set of pagetypes and
                        //     platforms, in an attempt to catch misspelling and other user
                        //     input errors.  Kurogo, however, has no such restrictive list, and
                        //     you can actually specify any pagetype/platform assuming you also
                        //     have all the necessary template/configuration files defined.
                        "pagetype"     : "compliant",
                        "platform"     : "android",
                        "supports_certificate" : true
                    },
                    
                    // In the event that a classification cannot be found for a given version
                    // number, it attempts to search for the next lowest version.
                    // So if you are requesting version 3, it first attempts to find version 3.
                    // If it cannot find it, it attempts to find version 2.  If it cannot find
                    // that either, it attempts to find version 1, etc.
                    
                    // Because of this, version numbers are a way to specify when this
                    // definition last changed.  This also means that upgrading to a new
                    // version of the detection engine does not require you to edit every single
                    // entry just to continue to use your custom detection file.
                    "1" : {
                        "pagetype"     : "webkit",
                        "platform"     : "android",
                        "supports_certificate" : true
                    }
                },
                
                // "match" : an array of regex and/or string match blocks which should be matched against the user agent.
                "match" : [
                    // You can specify any number of matcher blocks.
                    {
                        // There are 4 types of matches.
                        // "prefix" attempts to match the specified string at the beginning of the user-agent.
                        "prefix" : "Mozilla/5.0 (exampleDevice-v1"
                    },
                    {
                        // "suffix" attempts to match the specified string at the end of the user-agent.
                        "suffix" : "exampleDevice-v2)"
                    },
                    {
                        // "partial" attempts to perform a literal string match somewhere in the user-agent string.
                        "partial" : "exampleDevice"
                    },
                    {
                        // "regex" allows you full preg_match syntax for matching any part of the user-agent string.
                        // Note: Do not include delimiters in your regex string or escape any potential delimiters.
                        // This is done automatically by Kurogo.
                        "regex" : "exampleDevice-v(1|2)",
                        
                        // Each of the 4 different types of matches can also include an optional "options" object.
                        // This object can contain various modifiers for the match functions.  While useable on the
                        // basic string matching operations, it's most useful in regex, where you can specify case insensitivity and/or newline matching for the dot token.
                        "options" : {
                            "DOT_ALL" : true,
                            "CASE_INSENSITIVE" : true
                        }
                    }
                ]
            }
        ]
    }
