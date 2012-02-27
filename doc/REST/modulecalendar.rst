###############
Calendar API
###############

The Calendar API allows the client to fetch groups of calendars, a list of
events from a specific calendar, a list of events matching a search phrase,
and details about a specific event.

=======
groups
=======

:kbd:`/rest/calendar/groups&v=1`

Returns a list of available calendars

Sample *response* ::

    {
        [
            {
                "title":"Events",
                "id":"events",
                "calendars": [
                    {
                        "id":"events",
                        "type":"static",
                        "title":"Events"
                    }
                    // ...
                ]
            }
            // ...
        ]
    }

=========
events
=========

:kbd:`/rest/calendar/events?type=<type>&time=<start-time>&calendar=<calendar-id>&v=1` 

Returns a list of events for a particular calendar

Sample *response* ::

    {
        "displayField": "title", 
        "total": 2, 
        "returned": 2, 
        "results": [
            {
                "start": "1314630000", 
                "end": "1314637200", 
                "id": "http://modolabs.com/kurogo/events/201108291", 
                "title": "Faculty Meeting"
            }
            // ...
        ]
    }

Each entry in the *results* list has the same structure as the response of the
*detail* API.

=========
detail
=========

:kbd:`/rest/calendar/detail?type=<type>&calendar=<calendar-id>&id=<event-id>&start=<start-time>&v=1`

Returns a detail for an event

Sample *response* ::

    {
        "start": "1314626400", 
        "end": "1314631800", 
        "id": "http://modolabs.com/kurogo/events/201108290", 
        "title": "Concert",
        "description": "Boston Pops concert",
        "location": "Kresge Oval"
    }

The fields *id*, *start*, and *title* are mandatory.

======
search
======

:kbd:`/rest/calendar/search?type=<type>&calendar=<calendar-id>&end=<end-time>&start=<start-time>&q=<search-terms>&v=1` 

Search for an event within a particular calendar.

Sample *response* ::

    {
        "total": 1,
        "returned": 1,
        "displayField": "title",
        "results": [
            {
                "title": "Student Group Meeting",
                "start": "1314633600",
                "end": "1314635400",
                "id":"http:\/\/modolabs.com\/kurogo\/events\/201108290"
            },
            // ...
        ]
    }

Each entry in the *results* list has the same structure as the response of the
*detail* API.
