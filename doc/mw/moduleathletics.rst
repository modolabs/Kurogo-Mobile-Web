#################
Athletics Module
#################

The athletics module provides and interface to view news and event information for athletic
events. It utilizes a mixed feed system where data comes from multiple independent sources.
You can configure a list of sports for either gender and each sport has its own news and schedule/results
feed.

=====================
General Options
=====================

There are a few options in *SITE_DIR/config/photos/module.ini* that can configure basic operations of
the athletics module:

* *BOOKMARKS_ENABLED* - (optional) If set to true, a link to bookmarked entries will appear. Note that if
  you have not bookmarked any entries, this link will not appear until an entry is bookmarked. Defaults
  to true.
* *SHARING_ENABLED* -  Set to true to enable or false to disable sharing photos


===================
Sport Configuration
===================

The first step to configuring the athletics module is to specify the sports for each gender.
The list of sports will come from the *config/athletics/feeds.ini* file. Each sport will
have a section in this file. If a sport is competed by both genders, then it will have 
multiple sections with unique section keys ([m-basketball], [w-basketball]). The *feeds.ini*
file will also contain the configuration for that sport's news feed (if present). A corresponding 
entry in *schedule.ini* will contain the information regarding the schedule and results 
for that sport. Currently there is support for ICS or CSTV feeds for schedule and result
information.

----------------------------
Sport and News Configuration
----------------------------

For the sport and news configuration, each sport will have a section in *config/athletics/feeds.ini*.
The section name must be unique and will also be used by the schedule information. The feeds config
includes the following options:

* *TITLE* - The title of the sport as its shown in the list
* *GENDER* - The gender of the sport. Should be *men* or *women*. 
* *BASE_URL* - Optional - The URL of the news feed. By default it will assume RSS. If 
  this is in a different format you can use the same news feed options found in the :doc:`News Module <modulenews>`

For the schedule information, you will need to create a section in *config/athletics/schedule.ini* with 
the same name as you created in *feeds.ini*. This section will contain the schedule and results
information. Currently it supports ICS calendar feeds or CSTV XML feeds. It uses standard
DataRetriever configuration options. If you just include a *BASE_URL* then it will attempt
to parse the data using the ICS Data Parser.

------------------
CSTV Configuration
------------------

If your institution has a CSTV site (CBS Sports) then there is an easy way to retrieve event data.

* *RETRIEVER_CLASS* =  Set this to *CSTVDataRetriever*
* *BASE_URL* - Should be set to the event XML feed. It is typically in a format such as: http://goteam.com/data/ABBR-event-info-YEAR.xml where 
  ABBR is an abbreviation of your school and YEAR is a 4 digit academic year of the events to retrieve. This will typically be the same for all sports.
* *SPORT* - Set this to the value of the <sport> key in the XML file. This will filter the results for this sprot

-----------------
ICS Configuration
-----------------

If you are using an ICS feed for sporting events and results, then it is recommended that you create a distinct feed/calendar for each sport. 
Typically you would only need to configure the *BASE_URL* option to indicate the source of the event data. 

-----------------
Custom Data Types
-----------------

It is simple to create a custom parser for your own event information. The Athletics module expects event information to be returned as 
an array of AthleticEvent objects. 