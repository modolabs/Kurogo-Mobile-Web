================
Shuttle Scheudle
================

The Shuttle Schedule home page shows the user a list of all shuttle
routes that are available during the current term, separated into
daytime and nighttime shuttles. Icons are used to differentiate
between shuttles that are and are not currently running.

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

Detail screen

The detail screen times.php shows a list of all the stops along the
selected shuttle route. If the shuttle is currently running, the
expected next stop is highlighted in the list. Whether or not a
shuttle is running is determined by first quering NextBus to see if
there are any upcoming predictions. If NextBus is not available, the
published schedule to see if the shuttle is supposed to be running. If
not, the scheduled times for the next day’s first scheduled round of
stops is shown.

For most routes (except Boston/Cambridge All), a route map is shown below the listed times where the predicted next position of the shuttle is highlighted.

Key functions in times.php:

* timeSTR($stop) returns a string displaying the next time the shuttle
  will arrive at the given $stop. If the shuttle is done running, the
  string “Finished” is returned.

* imageURL($phone, $encodedName, $stops): For the route identified by
  $encodedName and image size identified by $phone, this function
  looks for the route map image that has the upcoming stop(s)
  highlighted.

* getNextStops($stops) returns a list of the stop(s) that are coming
  up next. The array $stops is created by
  ShuttleSchedule()::getRoute($routeName)::getCurrentStops($day,
  $hour, $minute) from lib/trunk/ShuttleSchedule.php.

Published shuttle schedule

The MIT Office of Parking and Transportation publishes updated
schedules of all shuttle routes online. The file
lib/trunk/shuttle_schedule.php constructs a ShuttleSchedule object and
populates it with schedule data that was manually copied from the
published schedules. Each route and stop also has a “short name”
assigned by NextBus, see the following section for details.

The Route class defined in ShuttleSchedule.php represents a single
shuttle route; the stop names and arrival times (as minute offsets
after the start of the hour) from the published schedule are entered
via the Route::stope() function. Route implements an iterator that
increments to the next stop in the list if it exists, or to the first
stop running the next available hour.

The PHP representation of route schedules is extrapolated into all
scheduled times on all days of the week in the MySQL table Schedule so
that they can be searched more easily. Key functions that interact the
database (defined in ShuttleSchedule.php):

* Route::populate_db() calls the day class for a list of all days of
  the week, then iterates through the whole list of stops (using the
  iterator to do this for every hour), inserting this information in
  the Schedule table.

* Route::getNextPrevStop($day, $hour, $minute, $next_flag) gets the
  next (previous) stop by searching the Schedule table for the
  earliest (latest) stop after (before) the specified time. If no stop
  is found, the search procedure is repeated for the following day.

* Route::isRunningFromDB($day, $hour, $minute) figures out whether the
  shuttle route is scheduled to run on the specified day/time, first
  by checking whether it is a holiday and second by searching the
  database using Route::getNextPrevStop.

* Route::getCurrentStopsFromDB($day, $hour, $minute) searches the
  database for the the earliest upcoming time the shuttle will stop at
  each stop.

Whenever there is a schedule change, the file shuttle_schedule.php
needs to be edited and the MySQL table needs to be re-populated. The
PHP script lib/trunk/save_schedule was written for this purpose; it
calls Route::populate_db() for each route constructed in
lib/trunk/shuttle_schedule.php. However, the script assumes the table
is empty and does not currently include a TRUNCATE TABLE statement;
this must be done manually.

Stop predictions from NextBus

* Route::GPSisActive() determines whether NextBus GPS is active by
  calling Route::getNextBusTimes(); if there are current predictions
  then we assume GPS is active.

* Route::isRunning($day, $hour, $minute) first calls
  Route::GPSisActive() to see if the shuttle is both running and
  tracked. If not, it calls Route::isRunningFromDB() to check if the
  shuttle is supposed to be running.

* Route::queryNextBus($stop_tags) sends a query to the NextBus server
  for a list of predictions in XML format, extracting the predicted
  times for each stop.

* Route::getNextBusTimes() returns the cached NextBus predictions if
  they are cached, otherwise a new set of predictions is fetched via
  Route::queryNextBus().

* Route::getCurrentStops($day, $hour, $minute) gets the list of
  prediction times via Route::getNextBusTimes() and returns
  information about each stop in the order they appear as part of the
  route.

Short names for routes and stops

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

The above query sends back an XML result that looks like::

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
