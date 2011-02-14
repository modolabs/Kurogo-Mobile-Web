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
the :ref:`admin-module` or by editing the `config/feeds/calendar.ini` file 
directly.

The module supports multiple calendars. Each calendar is indicated by a section in the configuration
file. The name of the section becomes the *type*, used in URLs to indicate which calendar to use. When
the type parameter is not indicated in a url, the first calendar is used. 

* The TITLE value is a label used to name your calendar feed. It will be used in the heading when 
  browsing and viewing events. 
* The BASE_URL is set to the url of your ICS feed. It can be either a static file or a web service. 

**Optional values**

* CONTROLLER_CLASS - allows you to set a different class name for the controller. The default is 
  CalendarDataController. You could write your own subclass to adjust the URL if your source is a 
  web service. The framework also includes an implementation suitable for users who host their calendar
  data on the Trumba event service. 
* PARSER_CLASS set this to a subclass of *DataParser*. You would only need to change it if your data
  source returns data in a format other than iCalendar (ICS). 
* EVENT_CLASS allows you to set a different class name for the returned event objects when searching. 
  This allows you to write custom behavior to handle custom fields in your feed.

=============================
Configuring the Detail Fields
=============================

Once you have configured the feed settings, you need to configure how the detail view displays and 
what values to use. Each field is configured in a section, the section name maps to an event field.
The order of the sections controls its order in the detail view. Within each section there are several 
possible values to influence how a field is displayed. All are optional.

* *label* - A text label for the field. 
* *type* - One of "datetime, "email", "phone", "category", "url".  Used to format and generate links.
* *class* - CSS class added to the field

==============================
Configuring the Initial Screen
==============================

The index page can be configured to show a list of links to show views of the calendars you have configured.
You can update the contents of this list by editing the *config/web/calendar-index.ini*. Each entry
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
  
* *class* - The CSS class of the item, such as *phone*, *map*, *email*
