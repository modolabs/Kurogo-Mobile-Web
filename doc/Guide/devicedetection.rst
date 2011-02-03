#################
Device Detection
#################

One of the strong features of the Kurogo framework is the ability to detect various devices and 
format content based on that device's capabilities. To support the classification of devices, the 
framework uses a Device Detection Server that contains a database of devices and outputs a normalized
set of properties.

-----------------------
Device Detection Server
-----------------------

The Kruogo Framework queries the device detection server using the *user agent* of the user's browser.
The Device Detection server will then return a series of properties based on the device:

* *supports_certificates* - Boolean. Whether this devices supports certificate based authentication
* *pagetype* - String. One of the device *buckets* that determines which major source of HTML the device
  will received. Values include *BASIC*, *TOUCH* and *WEBKIT* (aka *COMPLIANT*)
* *platform* - The specific type of device. Values include *ANDROID*, *BBPLUS*, *BLACKBERRY*, *COMPUTER*, 
  *FEATUREPHONE*, *IPHONE*, *PALMOS*, *SPIDER*, *SYMBIAN*, *WEBOS*, *WINMO*, *WINPHONE7*
* *description* - a textual description of the device
