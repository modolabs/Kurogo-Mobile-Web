.. _section-mobiweb-api-emergency:

==============
Emergency Info
==============

Overview:

* Get status of emertencies on campus.
* Get a list of contact phone numbers for various units on campus.


-------------
API Interface
-------------

All queries to Emergency Info use the base URL: http://m.mit.edu/api

All queries to Emergency Info include the following parameter:

* **module**: emergency

^^^^^^^^^^^^^^^^
Emergency Status
^^^^^^^^^^^^^^^^

Get current status of emergencies on campus.

Parameters:

* **module**: emergency

Sample Response:

.. code-block:: javascript

  [
    {
      "date":{
        "year":2010,
        "month":5,
        "day":13,
        "hour":12,
        "minute":21,
        "second":0,
        "fraction":0,
        "warning_count":0,
        "warnings":[],
        "error_count":0,
        "errors":[],
        "is_localtime":false,
        "relative":{
          "year":0,
          "month":0,
          "day":0,
          "hour":0,
          "minute":0,
          "second":0,
          "weekday":4
        }
      },
      "unixtime":1273767660,
      "text":"\n\n\tHello developers this is just a test!<\/p>\n\n \n ",
      "title":"Emergency Information",
      "version":"28"
    }
  ]

^^^^^^^^^^^^^^^^^
Emergency Contact
^^^^^^^^^^^^^^^^^

Get a list of useful contacts for emergency situations.

Parameters:

* **module**: emergency
* **command**: contacts

Sample Response:

.. code-block:: javascript

  [
    {
      "phone":"6172531212",
      "contact":"MIT Police"
    },
    {
      "phone":"6172531311",
      "contact":"MIT Medical",
      "description":"24-hour urgent care"
    },
    {
      "phone":"617253SNOW",
      "contact":"Emergency Status",
      "description":"recorded updates"
    },

    ...

  ]

---------
PHP Files
---------

mobi-lib/rss_services.php
mobi-web/api/index.php






