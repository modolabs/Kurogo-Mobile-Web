.. _section-mobiweb-shuttleschedule:

================
Shuttle Schedule
================

Displays a list of all available shuttles, indicating which ones are
running; predicted (or scheduled) stop times for the upcoming route
loop.

----------------------------
Data Sources / Configuration
----------------------------

^^^^^^^^^^^^^^^^
Printed Schedule
^^^^^^^^^^^^^^^^

The MIT Office of Parking and Transportation publishes updated
schedules of all shuttle routes online. Each route and stop also has a
"short name" assigned by NextBus.

The shuttle routes are

* Tech Shuttle (daytime, year-round)
* Northwest Shuttle (daytime, year-round)
* Boston Daytime shuttle (daytime, regular term)
* Saferide Boston East (nighttime, regular term)
* Saferide Boston West (nighttime, regular term)
* Saferide Cambridge East (nighttime, regular term)
* Saferide Cambridge West (nighttime, regular term)
* Saferide Boston All (nighttime, holidays/summer)
* Saferide Cambridge ALl (nighttime, holidays/summer)

^^^^^^^
NextBus
^^^^^^^

The list of which routes NextBus is tracking and their short names can
be obtained at
http://www.nextbus.com/s/xmlFeed?command=routeList&a=mit.

For each route, the list of stops and their short names can be found
using the routeConfig command in the query, for example:
http://www.nextbus.com/s/xmlFeed?command=routeConfig&a=mit&r=tech

Predictions XML

Predictions are obtained by sending a query similar to the following::

  http://www.nextbus.com/s/xmlFeed  
    ?command=predictionsForMultiStops  
    &a=mit  
    &stops=tech|wcamp|kendsq_d  
    &stops=tech|wcamp|amhewads  
    &stops=tech|wcamp|medilb  
 
    ...

The above query sends back an XML result that looks like:

.. code-block:: xml

  <body copyright="All data copyright Massachusetts Institute of Technology 2009.">
    <predictions agencyTitle="Massachusetts Institute of Technology"
                 routeTitle="Tech Shuttle"
                 routeTag="tech"
                 stopTitle="Kendall Square">
      <direction title="West Campus">
        <prediction seconds="292"
                    minutes="4"
                    epochTime="1248896544115"
                    isDeparture="true"
                    dirTag="wcamp"
                    block="11"/>
        <prediction seconds="1388" ... />
        <prediction seconds="2477" ... />
        <prediction seconds="3565" ... />
        <prediction seconds="4654" ... />
      </direction>
      <message text="Contact Telephone Numbers:  Saferide Manager  617-253-2997, Parking and Transportation Office 617-258-6510."/>
    </predictions>
    ...
    <predictions agencyTitle="Massachusetts Institute of Technology"
                 routeTitle="Tech Shuttle"
                 routeTag="tech"
                 stopTitle="Media Lab">
      <direction title="West Campus">
        <prediction seconds="416" ... />

        ...

        <prediction seconds="4781" ... />
      </direction>
      <message text="Contact Telephone Numbers:  Saferide Manager  617-253-2997, Parking and Transportation Office 617-258-6510."/>
    </predictions>
  </body> 



^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-ShuttleSchedule`
* :ref:`subsection-mobiweb-NextBusReader`

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/shuttle_lib.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/times.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shows a list of all shuttle routes that are running today or tomorrow,
separated into daytime and nighttime shuttles.  Shows a normal bus
icon if the shuttle is running, and a grayed-out, X'ed out bus icon if
it is not running.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/\*/times.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shows a list of all the stops along the selected shuttle route. If the
shuttle is currently running, the upcoming stop is highlighted in the
list.  Displays a the shuttle's description text (e.g. "Runs 7am-7pm
weekdays"), and current GPS status (online or offline).  A second tab,
when tapped, displays the diagrammatic map view with the shuttle
location.  Some longer routes (Boston and Cambridge All) do not have maps.


^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-lib/ShuttleSchedule.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-lib/NextBusReader.php
^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/times.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. function:: timeSTR($stop) 
  
  returns a string displaying the next time the shuttle
  will arrive at the given ``$stop``. If the shuttle is done running, the
  string “Finished” is returned.

.. function:: imageURL($phone, $encodedName, $stops)

  For the route identified by
  $encodedName and image size identified by $phone, this function
  looks for the route map image that has the upcoming stop(s)
  highlighted.

.. function:: getNextStops($stops) 
  
  returns a list of the stop(s) that are coming
  up next. The array ``$stops`` is created by
  ``ShuttleSchedule()::getRoute($routeName)::getCurrentStops($day,$hour,$minute)``
  from lib/trunk/ShuttleSchedule.php.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Basic/images
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Basic/shuttletrack-fp.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Basic/shuttletrack.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Webkit/route_na.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Webkit/shuttletrack-tablet.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/shuttleschedule/Webkit/shuttletrack.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

