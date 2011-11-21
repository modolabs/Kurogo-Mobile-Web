###############
Calendar Module
###############

The calendar module provides an mobile interface to a series of events. You can browse events 
by day, category or list, and then view any details of the event. The built in module supports parsing
and viewing events in iCalendar (ICS) format. 

=============================
Configuring the Calendar Feed
=============================

In order to use the calendar module, you must first setup the connection to your data. There are
2 required values that must be set and a few optional ones. You can set these values by either using
the :ref:`admin-module` or by editing the *SITE_DIR/config/calendar/feeds.ini* file 
directly.

The module supports multiple calendars. Each calendar is indicated by a section in the configuration
file. The name of the section becomes the *type*, used in URLs to indicate which calendar to use. When
the type parameter is not indicated in a url, the first calendar is used. 

* The *TITLE* value is a label used to name your calendar feed. It will be used in the heading when 
  browsing and viewing events. 
* The *BASE_URL* is set to the url of your ICS feed. It can be either a static file or a web service. 

**Optional values**

* *CONTROLLER_CLASS* - allows you to set a different class name for the controller. The default is 
  CalendarDataController. You could write your own subclass to adjust the URL if your source is a 
  web service. The framework also includes an implementation suitable for users who host their calendar
  data on the Trumba event service. 
* *PARSER_CLASS* (default ICSDataParser) set this to a subclass of *DataParser*. You would only need to change it if your data
  source returns data in a format other than iCalendar (ICS). 
* *EVENT_CLASS* (default ICalEvent) allows you to set a different class name for the returned event objects when searching. 
  This allows you to write custom behavior to handle custom fields in your feed.

=============================
Configuring the Detail Fields
=============================

Once you have configured the feed settings, you need to configure how the detail view displays and 
what values to use. Each field is configured in a section, the section name maps to an event field.
The order of the sections controls its order in the detail view. Within each section there are several 
possible values to influence how a field is displayed. All are optional.

* *label* - A text label for the field. 
* *type* - Optional value to format the value and/or create a link. Possible values are:

  * datetime - Formats the value as a date/time
  * email - creates a mailto link using the value as the email address
  * phone - creates a telephone link using the value as the phone number
  * url - creates a link, The value is used as the url
  
* *class* - CSS class added to the field. multiple classes can be added using spaces
* *module* - Creates a link to a another module and uses that module's linkForValue method to format the result.
  See the section on :doc:`moduleinteraction` for more details.

==============================
Configuring the Initial Screen
==============================

The index page can be configured to show a list of links to show views of the calendars you have configured.
You can update the contents of this list by editing the *SITE_DIR/config/calendar/page-index.ini*. Each entry
is a section. Each section has values that map to the the values used by the *listItem* template. 

* *title* - The Name of the entry as it's shown to the user
* *subtitle* - The subtitle, typically shown below the title
* *url* - The link it should point to. Although you can link to any url, you would typically link to
  one of the pages within the module. The calendar view pages require you to pass the *type* parameter
  to indicate which calendar to show:
  
  * *day* - Shows events for a given day. 
  * *year* - Shows all events for a given 12 month period. You can indicate the starting month by passing
    the month parameter
  * *list* - Shows the next events beginning with the present day. Default limit is 20 events.
  * *categories* - Shows a list of categories. Currently this requires special support to get a list of
    categories.
  
* *class* - The CSS class of the item, such as *phone*, *email*

========================================
Configuring User Calendars and Resources
========================================

There is support for viewing user calendars and resources (such as rooms/equipment). Currently the 
only supported calendar system is Google Apps for Business or Education. Support for Microsoft Exchange
calendars is available through Modo Labs contact `sales@modolabs.com` for information.

To enable User Calendars:

* Setup the :doc:`authority <GoogleAppsAuthentication>` for your Google Apps Domain. 
* Ensure that you have entered the required OAuth consumer key and secret
* Ensure that the "http://www.google.com/calendar/feeds" scope is available in your authority.
* Edit *config/calendar/module.ini* and add a *user_calendars* section.
* Set CONTROLLER_CLASS to GoogleAppsCalendarListController
* Set AUTHORITY to the section name of your Google Apps Authority

This is an example section from the config/calendar/module.ini file::

  [user_calendars]
  CONTROLLER_CLASS="GoogleAppsCalendarListController"
  AUTHORITY="googleapps"

To enable Resources: 

* Setup the :doc:`authority <GoogleAppsAuthentication>` for your Google Apps Domain. 
* Ensure that you have entered the required OAuth consumer key and secret
* Ensure that the "https://apps-apis.google.com/a/feeds/calendar/resource/" scope is available in your authority.
* Edit *config/calendar/module.ini* and add a *resources* section.
* Set CONTROLLER_CLASS to GoogleAppsCalendarListController
* Set AUTHORITY to the section name of your Google Apps Authority

This is an example section from the config/calendar/module.ini file::

  [resources]
  CONTROLLER_CLASS="GoogleAppsCalendarListController"
  AUTHORITY="googleapps"
