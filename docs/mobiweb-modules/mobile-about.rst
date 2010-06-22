.. _section-mobiweb-mobile-about:

===============
About This Site
===============


Table view that drills down to the following screens:

* What’s New
* Background Information
* System Requirements
* Credits
* Send us feedback (just a mailto: link)

Additionally, a Statistics page is available, but not shown to the
user as a link.  The statistics page displays totals views (of the
website), messages (incoming/outgoing SMS), and API requests (from
native apps) over selected time periods, as well as corresponding
figures brokend down by platform/carrier and module.

The internal ID for this module is ``mobile-about`` because ``about``
is the name of the desktop about site.

----------------------------
Data Sources / Configuration
----------------------------

^^^^^^^^^^^^^^^^^^^^^^^^^^
"What's New" Announcements
^^^^^^^^^^^^^^^^^^^^^^^^^^

Published via Drupal at http://localhost/drupal/whats_new/rss.xml.
This is configured inside the :ref:`subsubsection-mobiweb-whatsnew`
class


^^^^^^^^^^^^^^^^^^
Website Statistics
^^^^^^^^^^^^^^^^^^

Page View (website) data come from the MySQL table PageViews.


^^^^^^^^^^^^^^
SMS Statistics
^^^^^^^^^^^^^^

SMS data are generated on the SMS server http://sms1.mit.edu and
fetched via a web call. The SMS server returns a JSON string similar
to the following:

.. code-block:: javascript

  {"days": [  
      {"date":"2009-07-25","count":"8"},  
      {"date":"2009-07-27","count":"14"}  
   ],  
   "sent": [  
      {"date":"2009-07-25","count":"11"},  
      {"date":"2009-07-27","count":"30"}  
   ],  
   "modules": [  
      {"module":"shuttleschedule","count":"15"},  
      {"module":"people","count":"3"},  
      {"module":"calendar","count":"1"},  
      {"module":"stop","count":"1"},  
      {"module":"info","count":"1"},  
      {"module":"emergency","count":"1"}  
   ],  
   "carriers": [  
      {"carrier_information":"31002","count":"18","carrier":"AT&T"},  
      {"carrier_information":"31004","count":"4","carrier":"Tmobile"}  
   ]}



^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-rss-services`

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

content for all sections of this module (requested via the ``page``
query parameter in the GET request), except What’s New.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/new.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/page_builder/counter.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. method:: PageViews::view_past($system, $interval_type, $duration)
   
   :param $system:  "web" or "api", web versus native app
   :param $interval_type: "day", "week", "month", or "quarter"
   :param $duration: length of interval in days

   Queries the MySQL table PageViews and returns an array similar to the following:
 

   .. code-block:: php

      Array(  
        [0] => Array(  
          "people" => 10,  
          "shuttleschedule" => 90,  
          "iphone" => 70,  
          "android" => 20,  
          "webos" => 10,
          "blackberry" => 5,
          "featurephone" => 10,
          "computer" => 13,  
          "date" => 1234567890,  
          "name" => "Fri",  
          "total" => 100,  
        ),  
      )

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/statistics.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. function:: summary_total($data, $field, $title)

   Generates the parameters for the total counts.

.. function:: bar_percentage()

   Generates parameters to create a horizontal bar graph for
   iPhone/Android pages, or a list of counts for other devices.

.. function:: trend($data, $field, $title, $interval_type)

   Generates parameters to create a vertical bar graph of usage per
   day/week/month for iPhone/Android pages, or a list of counts and
   percentages for other devices.

For web statistics:

.. function:: generate_popular_web_content($system, $data)

   Reads the module counts from the array (people, shuttleschedule etc.)
   and associates them with their display names (People Directory,
   Shuttle Schedule etc.).

.. function:: platform_data()

   Reads the platform counts and associates them with their display name
   (iPhone, Android etc.).

For SMS statistics:

.. function:: aggregate_days($days, $interval_type, $duration)

   This function is given either the days or sent array from the SMS JSON
   string to tally the data into day-, week-, and month-long intervals.

.. function:: generate_sms_content($data)

   This function is given the modules array from the the JSON string to
   generate a list of usage counts per module.

.. function:: carriers_data($data)

   This function is given the carriers array from the JSON string to
   produce carrier counts.

.. _subsubsection-mobiweb-whatsnew:

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/WhatsNew.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. class:: WhatsNew

   Populates a list of announcements that were input via Drupal.
   Extends of the class RSS from ``mobi-lib/rss_services.php``.

.. method:: get_items()

   Gets the contents of the RSS feed, but in reverse order so the most
   recent item is first.

.. method:: getLastTime()

   Reads the user’s whatsnewtime cookie to determine whether the user has
   unread items.

.. method:: getTopItemName()

   Determines whether the most recent item is more than 2 weeks old.

--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/Basic/statistics.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/Webkit/statistics.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/Webkit/stats.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/\*/background.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/\*/credits.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/\*/new.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/mobile-about/\*/requirements.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

