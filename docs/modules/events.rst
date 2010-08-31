.. _modules_events:

**********************
Events Calendar
**********************

The Events module provides an interface for the Harvard Gazette calendar. It provides the following high-level functionality:

* Display all events for a particular date
* Browse events in specific categories for particular dates
* Display Academic Calendar Events for a particular Academic Year
* Search for events in the next seven days
* Display detailed information for each event (including time, location, weblinks, descriptions, categories, contact information)


=======
iPhone
=======

-----------------
Event Categories
-----------------

The first server call made by the iPhone app is to retrieve all Event Categories at Harvard, as classified by the Gazette. The iPhone requests:
    http://m.harvard.edu/api/?module=calendar&command=categories

The server then retrieves the categories information from a **static** file cached on the server itself. This file is called the *event_cat* and is located in the */static* folder in */mobiweb/opt/mitmobile* folder. The contents of this static file contain the **category-title**, **category-id** and **category-url-query** for the Gazette iCal feed. The title, id and url are all separated by newlines in the static file.

The server then parses this data and returns it back to the iPhone in the following JSON string format:

.. code-block:: javascript

	[{"name":"Art\/Design","catid":"41129","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41129&filterfield1=15202"},{"name":"Athletic ","catid":"67204","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67204&filterfield1=15202"},{"name":"Award Ceremonies ","catid":"41149","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41149&filterfield1=15202"},{"name":"Business","catid":"41130","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41130&filterfield1=15202"},{"name":"Classes\/Workshops","catid":"41156","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41156&filterfield1=15202"},{"name":"Comedy","catid":"41140","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41140&filterfield1=15202"},{"name":"Concerts","catid":"41141","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41141&filterfield1=15202"},{"name":"Conferences","catid":"41131","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41131&filterfield1=15202"},{"name":"Dance","catid":"41142","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41142&filterfield1=15202"},{"name":"Education","catid":"78368","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=78368&filterfield1=15202"},{"name":"Environmental Sciences","catid":"41132","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41132&filterfield1=15202"},{"name":"Ethics","catid":"41133","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41133&filterfield1=15202"},{"name":"Exhibitions","catid":"41143","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41143&filterfield1=15202"},{"name":"Film","catid":"41144","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41144&filterfield1=15202"},{"name":"Health Sciences","catid":"41134","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41134&filterfield1=15202"},{"name":"Humanities","catid":"41135","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41135&filterfield1=15202"},{"name":"Information Technology","catid":"41136","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41136&filterfield1=15202"},{"name":"Law","catid":"41155","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41155&filterfield1=15202"},{"name":"Lecture","catid":"67253","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67253&filterfield1=15202"},{"name":"Music","catid":"67883","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67883&filterfield1=15202"},{"name":"Opera","catid":"41145","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41145&filterfield1=15202"},{"name":"Poetry\/Prose","catid":"41137","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41137&filterfield1=15202"},{"name":"Religion","catid":"41159","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41159&filterfield1=15202"},{"name":"Science","catid":"41138","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41138&filterfield1=15202"},{"name":"Social Sciences","catid":"41139","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41139&filterfield1=15202"},{"name":"Special Events","catid":"41150","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41150&filterfield1=15202"},{"name":"Support\/Social","catid":"41160","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41160&filterfield1=15202"},{"name":"Theater","catid":"41147","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41147&filterfield1=15202"},{"name":"Volunteer Opportunities","catid":"41157","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41157&filterfield1=15202"},{"name":"Wellness\/Work Life","catid":"41158","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41158&filterfield1=15202"},{"name":"Working@Harvard","catid":"64137","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=64137&filterfield1=15202"}]

These categories are then cached on the iPhone itself and future networks calls to populate a list of categories is avoided.


-------------------------
Today's Events
-------------------------

For any given day, the server call to retrieve that particular day's events is:
    http://m.harvard.edu/api/?type=Events&time=1283281315&module=calendar&command=day

The *time* parameter in the query is the current time in milliseconds since 1970. The *command* parameter specifies that only the events that belong to the particular day specified must be retrieved.

If the events for that **month** are not cached on the server, the following network call is made to the Gazette calendar feed:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100801&months=1&

The *startdate* parameter always specifies the first day of the month under consideration and the *months* parameter specifies how many months worth of events to retrieve based on the start-date.
This data is then cached on the server.

The iCal feed from the Gazette server is then parsed and the events for the particular date are singled out. Those events are then converted to JSON string format and returned to the iPhone:

.. code-block:: javascript

    [
     {
        "id":303962621,
        "title":"Farmers\u2019 Market at Harvard",
        "start":1283272200,
        "end":1283292000,
        "url":"http:\/\/www.dining.harvard.edu\/flp\/ag_market.html",
        "location":"Lawn between the Science Center and Memorial Hall",
        "description":"Fresh, locally grown produce, baked goods, cheese, maple syrup, chocolate, flowers, crafts, and more!",
        "custom":
            {
                "\"Event Type\"":"Harvard Event",
                "\"Gazette Classification\"":"Special Events",
                "\"Organization\/Sponsor\"":"Harvard University"
            }
      },
      {
        "id":1950789994,
        "title":"Cabaret",
        "start":1283297400,
        "end":1283297399,
        "url":"http:\/\/www.americanrepertorytheater.org\/events\/show\/cabaret",
        "location":"Oberon, 2 Arrow St., 02138",
        "description":"Take your seat at the Kit Kat Klub, the perfectly marvelous cabaret where singer Sally Bowles meets writer Cliff Bradshaw. As the two pursue a life of pleasure in Weimar Berlin, the world outside the Klub begins to splinter. Sally and Cliff are faced with a choice: abandon themselves to pleasures promised by the cabaret, or open their eyes and face the coming storm. Singer and songwriter Amanda Palmer of Dresden Doll fame stars as the Kit Kat Klub's magnetic Emcee, presiding over the debauched party where nothing is as it seems, with A.R.T. regulars Remo Airaldi, Thomas Derrah, and Jeremy Geidt.",
        "custom":
            {
                "\"Location\"":"2 Arrow St<\/StreetAddress1>Cambridge<\/City>MA<\/StateProvince>02138-5102<\/PostalCode>42.370927<\/Latitude>-71.114038<\/Longitude>US<\/MapPointRegionCode>Oberon\\n2 Arrow St\\nCambridge\\, MA 02138-5102<\/MapPointDisplay>3<\/Value><\/MapLinkType>AddressAndLabel<\/LocationType>2.15<\/MapHeight>2.86666666666667<\/MapWidth><\/RadarLocation>",
                "\"Event Type\"":"Harvard Event",
                "\"Gazette Classification\"":"Theater",
                "\"Ticket Web Link\"":"http:\/\/www.americanrepertorytheater.org",
                "\"Directed By\"":"Steven Bogart",
                "\"Ticket Info\"":"617.547.8300",
                "\"Organization\/Sponsor\"":"American Repertory Theater",
                "\"Cost\"":"$25\\; student rush $15\\; $10 off seniors"
            }
         }
     ]


-------------------
Academic Calendar
-------------------

For a given Academic-Year, the Academic Calendar events are requested using the following call to the server:
    http://m.harvard.edu/api/?month=8&module=calendar&year=2010&command=academic

If not cached, the server then retrieves the Academic Calendar events for the year using:
    http://www.trumba.com/calendars/harvard_academic_calendar.ics?startdate=20100901&enddate=20110831&

The *startdate* and *enddate* parameters span an entire Academic Year.

The retrieved events are then cached and processed to return as JSON strings:

.. code-block:: javascript

    [{"id":309289528,"title":"First day fall term classes","start":1283385600,"end":1283385599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1377144403,"title":"Holiday - Columbus Day","start":1286841600,"end":1286841599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1737372568,"title":"Holiday - Veterans Day","start":1289523600,"end":1289523599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":813349270,"title":"Thanksgiving recess","start":1290733200,"end":1290992399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":730945920,"title":"Winter recess","start":1293066000,"end":1294016399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1136903010,"title":"Optional Winter session","start":1294102800,"end":1295830799,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1006602584,"title":"Holiday - Martin Luther King Day","start":1295312400,"end":1295312399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":188234303,"title":"First day Spring term classes","start":1295917200,"end":1295917199,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":992486107,"title":"Holiday - Presidents Day","start":1298336400,"end":1298336399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1375120879,"title":"Spring recess","start":1299978000,"end":1300665600,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1618357601,"title":"Commencement","start":1306454400,"end":1306454399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1147143182,"title":"Holiday - Memorial Day","start":1306800000,"end":1306799999,"custom":{"\"Event Type\"":"Harvard Event"}}]


-------------------
Categories Search
-------------------

For a given category, events for a given day are requested as:
    http://m.harvard.edu/api/?command=category&id=41129&module=calendar&start=1283199940

The *id* parameter specifies the Category-Id, corresponding to the specific category. This is the same as the category-id retrieved earlier when the call to all Categories was made.
The *start* parameter specifies the time-stamp corresponding to the day under consideration.

The server then makes a call to the Gazette calendar (if the category data is not cached already) as:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100801&months=1&filter1=41147&filterfield1=15202

*filter1=41147&filterfield1=15202* are the specific query parameters for that category. They are obtained from the **event_cat** static file parsed and returned on the server call for all categories.

The events for the particular day are then returned as JSON strings:

.. code-block:: javascript

    [{"id":765804116,"title":"Opening of \"New Visiting Faculty 2010-11\" Exhibit","start":1283126400,"end":1283212799,"url":"http:\/\/www.ves.fas.harvard.edu\/vesNewFacultyExhibition.html","location":"Carpenter Center Main Gallery","description":"Exhibit on view through Sept. 26The Carpenter Center for the Visual Arts presents work by new visiting faculty in the Department of Visual and Environmental Studies. Artists: Katarina Burin, Marina Rosenfeld, Matt Saunders, Gregory Sholette, Mungo Thomson, Kerry Tribe, and Penelope Umbrico.Reception for the artists: Thursday, Sept. 9, 5:30-6:30 p.m.","custom":{"\"Event Type\"":"Harvard Event","\"Organization\/Sponsor\"":"Carpenter Center for the Visual Arts","\"Cost\"":"Free","\"Contact Info\"":{"email":[],"phone":["617.495.3251"],"url":[],"text":[],"full":"617.495.3251"},"\"Gazette Classification\"":"Art\/Design\\, Exhibitions\\, Special Events"}}]


----------------
Events Search
----------------

A search for events in the next 7 days is initiated as:
    http://m.harvard.edu/api/?q=exhibit&module=calendar&command=search

The query-term in this example is "exhibit".

The server then makes a call to the Gazette iCal feed:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100823&days=7&search=exhibit&

The returned events are then cached, parsed and then returned as JSON strings:

.. code-block:: javascript

    {"events":[{"id":765804116,"title":"Opening of \"New Visiting Faculty 2010-11\" Exhibit","start":1283126400,"end":1283212799,"url":"http:\/\/www.ves.fas.harvard.edu\/vesNewFacultyExhibition.html","location":"Carpenter Center Main Gallery","description":"Exhibit on view through Sept. 26The Carpenter Center for the Visual Arts presents work by new visiting faculty in the Department of Visual and Environmental Studies. Artists: Katarina Burin, Marina Rosenfeld, Matt Saunders, Gregory Sholette, Mungo Thomson, Kerry Tribe, and Penelope Umbrico.Reception for the artists: Thursday, Sept. 9, 5:30-6:30 p.m.","custom":{"\"Event Type\"":"Harvard Event","\"Organization\/Sponsor\"":"Carpenter Center for the Visual Arts","\"Cost\"":"Free","\"Contact Info\"":{"email":[],"phone":["617.495.3251"],"url":[],"text":[],"full":"617.495.3251"},"\"Gazette Classification\"":"Art\/Design\\, Exhibitions\\, Special Events"}}]}



===========
Mobile-Web
===========

-----------------
Event Categories
-----------------

When the "Browse by Category" link is clicked, the following php file is executed:
    http://m.harvard.edu/calendar/categorys.php

This file helps retrieve the categories information from a **static** file cached on the server itself. This file is called the *event_cat* and is located in the */static* folder in */mobiweb/opt/mitmobile* folder. The contents of this static file contain the **category-title**, **category-id** and **category-url-query** for the Gazette iCal feed. The title, id and url are all separated by newlines in the static file.

The server then parses this data and returns it back to the iPhone in the following JSON string format:

.. code-block:: javascript

	[{"name":"Art\/Design","catid":"41129","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41129&filterfield1=15202"},{"name":"Athletic ","catid":"67204","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67204&filterfield1=15202"},{"name":"Award Ceremonies ","catid":"41149","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41149&filterfield1=15202"},{"name":"Business","catid":"41130","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41130&filterfield1=15202"},{"name":"Classes\/Workshops","catid":"41156","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41156&filterfield1=15202"},{"name":"Comedy","catid":"41140","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41140&filterfield1=15202"},{"name":"Concerts","catid":"41141","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41141&filterfield1=15202"},{"name":"Conferences","catid":"41131","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41131&filterfield1=15202"},{"name":"Dance","catid":"41142","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41142&filterfield1=15202"},{"name":"Education","catid":"78368","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=78368&filterfield1=15202"},{"name":"Environmental Sciences","catid":"41132","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41132&filterfield1=15202"},{"name":"Ethics","catid":"41133","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41133&filterfield1=15202"},{"name":"Exhibitions","catid":"41143","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41143&filterfield1=15202"},{"name":"Film","catid":"41144","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41144&filterfield1=15202"},{"name":"Health Sciences","catid":"41134","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41134&filterfield1=15202"},{"name":"Humanities","catid":"41135","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41135&filterfield1=15202"},{"name":"Information Technology","catid":"41136","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41136&filterfield1=15202"},{"name":"Law","catid":"41155","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41155&filterfield1=15202"},{"name":"Lecture","catid":"67253","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67253&filterfield1=15202"},{"name":"Music","catid":"67883","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=67883&filterfield1=15202"},{"name":"Opera","catid":"41145","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41145&filterfield1=15202"},{"name":"Poetry\/Prose","catid":"41137","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41137&filterfield1=15202"},{"name":"Religion","catid":"41159","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41159&filterfield1=15202"},{"name":"Science","catid":"41138","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41138&filterfield1=15202"},{"name":"Social Sciences","catid":"41139","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41139&filterfield1=15202"},{"name":"Special Events","catid":"41150","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41150&filterfield1=15202"},{"name":"Support\/Social","catid":"41160","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41160&filterfield1=15202"},{"name":"Theater","catid":"41147","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41147&filterfield1=15202"},{"name":"Volunteer Opportunities","catid":"41157","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41157&filterfield1=15202"},{"name":"Wellness\/Work Life","catid":"41158","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=41158&filterfield1=15202"},{"name":"Working@Harvard","catid":"64137","url":"http:\/\/www.trumba.com\/calendars\/gazette.ics?filter1=64137&filterfield1=15202"}]

-------------------------
Today's Events
-------------------------

When the "Today's Events" link is clicked, or another day in the past or future is selected, the following call is made:
    http://m.harvard.edu/calendar/day.php?time=1283184000&type=events

The *time* parameter in the query is the current time in milliseconds since 1970. The *type* parameter specifies that only the events that belong to the particular day specified must be retrieved.

If the events for that **month** are not cached on the server, the following network call is made to the Gazette calendar feed:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100801&months=1&

The *startdate* parameter always specifies the first day of the month under consideration and the *months* parameter specifies how many months worth of events to retrieve based on the start-date.
This data is then cached on the server.

The iCal feed from the Gazette server is then parsed and the events for the particular date are singled out. Those events are then converted to JSON string format and returned as:

.. code-block:: javascript

    [
     {
        "id":303962621,
        "title":"Farmers\u2019 Market at Harvard",
        "start":1283272200,
        "end":1283292000,
        "url":"http:\/\/www.dining.harvard.edu\/flp\/ag_market.html",
        "location":"Lawn between the Science Center and Memorial Hall",
        "description":"Fresh, locally grown produce, baked goods, cheese, maple syrup, chocolate, flowers, crafts, and more!",
        "custom":
            {
                "\"Event Type\"":"Harvard Event",
                "\"Gazette Classification\"":"Special Events",
                "\"Organization\/Sponsor\"":"Harvard University"
            }
      },
      {
        "id":1950789994,
        "title":"Cabaret",
        "start":1283297400,
        "end":1283297399,
        "url":"http:\/\/www.americanrepertorytheater.org\/events\/show\/cabaret",
        "location":"Oberon, 2 Arrow St., 02138",
        "description":"Take your seat at the Kit Kat Klub, the perfectly marvelous cabaret where singer Sally Bowles meets writer Cliff Bradshaw. As the two pursue a life of pleasure in Weimar Berlin, the world outside the Klub begins to splinter. Sally and Cliff are faced with a choice: abandon themselves to pleasures promised by the cabaret, or open their eyes and face the coming storm. Singer and songwriter Amanda Palmer of Dresden Doll fame stars as the Kit Kat Klub's magnetic Emcee, presiding over the debauched party where nothing is as it seems, with A.R.T. regulars Remo Airaldi, Thomas Derrah, and Jeremy Geidt.",
        "custom":
            {
                "\"Location\"":"2 Arrow St<\/StreetAddress1>Cambridge<\/City>MA<\/StateProvince>02138-5102<\/PostalCode>42.370927<\/Latitude>-71.114038<\/Longitude>US<\/MapPointRegionCode>Oberon\\n2 Arrow St\\nCambridge\\, MA 02138-5102<\/MapPointDisplay>3<\/Value><\/MapLinkType>AddressAndLabel<\/LocationType>2.15<\/MapHeight>2.86666666666667<\/MapWidth><\/RadarLocation>",
                "\"Event Type\"":"Harvard Event",
                "\"Gazette Classification\"":"Theater",
                "\"Ticket Web Link\"":"http:\/\/www.americanrepertorytheater.org",
                "\"Directed By\"":"Steven Bogart",
                "\"Ticket Info\"":"617.547.8300",
                "\"Organization\/Sponsor\"":"American Repertory Theater",
                "\"Cost\"":"$25\\; student rush $15\\; $10 off seniors"
            }
         }
     ]


-------------------
Academic Calendar
-------------------

When the "Academic Calendar" link is clicked, or the past or future Academic Years are selected, the following call is made:
    http://m.harvard.edu/calendar/academic.php?year=2010

If not cached, the server then retrieves the Academic Calendar events for the year using:
    http://www.trumba.com/calendars/harvard_academic_calendar.ics?startdate=20100901&enddate=20110831&

The *startdate* and *enddate* parameters span an entire Academic Year.

The retrieved events are then cached and processed to return as JSON strings:

.. code-block:: javascript

    [{"id":309289528,"title":"First day fall term classes","start":1283385600,"end":1283385599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1377144403,"title":"Holiday - Columbus Day","start":1286841600,"end":1286841599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1737372568,"title":"Holiday - Veterans Day","start":1289523600,"end":1289523599,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":813349270,"title":"Thanksgiving recess","start":1290733200,"end":1290992399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":730945920,"title":"Winter recess","start":1293066000,"end":1294016399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1136903010,"title":"Optional Winter session","start":1294102800,"end":1295830799,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1006602584,"title":"Holiday - Martin Luther King Day","start":1295312400,"end":1295312399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":188234303,"title":"First day Spring term classes","start":1295917200,"end":1295917199,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":992486107,"title":"Holiday - Presidents Day","start":1298336400,"end":1298336399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1375120879,"title":"Spring recess","start":1299978000,"end":1300665600,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1618357601,"title":"Commencement","start":1306454400,"end":1306454399,"custom":{"\"Event Type\"":"Harvard Event"}},{"id":1147143182,"title":"Holiday - Memorial Day","start":1306800000,"end":1306799999,"custom":{"\"Event Type\"":"Harvard Event"}}]


-------------------
Categories Search
-------------------

For a given category, events for a given day are requested as:
    http://m.harvard.edu/calendar/category.php?id=41129&name=Art%2FDesign

The *id* parameter specifies the Category-Id, corresponding to the specific category. This is the same as the category-id retrieved earlier when the call to all Categories was made.n.

The server then makes a call to the Gazette calendar (if the category data is not cached already) as:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100801&months=1&filter1=41147&filterfield1=15202

*filter1=41147&filterfield1=15202* are the specific query parameters for that category. They are obtained from the **event_cat** static file parsed and returned on the server call for all categories.

The events for the particular day are then returned as JSON strings:

.. code-block:: javascript

    [{"id":765804116,"title":"Opening of \"New Visiting Faculty 2010-11\" Exhibit","start":1283126400,"end":1283212799,"url":"http:\/\/www.ves.fas.harvard.edu\/vesNewFacultyExhibition.html","location":"Carpenter Center Main Gallery","description":"Exhibit on view through Sept. 26The Carpenter Center for the Visual Arts presents work by new visiting faculty in the Department of Visual and Environmental Studies. Artists: Katarina Burin, Marina Rosenfeld, Matt Saunders, Gregory Sholette, Mungo Thomson, Kerry Tribe, and Penelope Umbrico.Reception for the artists: Thursday, Sept. 9, 5:30-6:30 p.m.","custom":{"\"Event Type\"":"Harvard Event","\"Organization\/Sponsor\"":"Carpenter Center for the Visual Arts","\"Cost\"":"Free","\"Contact Info\"":{"email":[],"phone":["617.495.3251"],"url":[],"text":[],"full":"617.495.3251"},"\"Gazette Classification\"":"Art\/Design\\, Exhibitions\\, Special Events"}}]


----------------
Events Search
----------------

A search for events in the next 7 days is initiated as:
    http://m.harvard.edu/calendar/search.php?filter=exhibit&sch_btn=Search&timeframe=0

The query-term in this example is "exhibit".

The server then makes a call to the Gazette iCal feed:
    http://www.trumba.com/calendars/gazette.ics?startdate=20100823&days=7&search=exhibit&

The returned events are then cached, parsed and then returned as JSON strings:

.. code-block:: javascript

    {"events":[{"id":765804116,"title":"Opening of \"New Visiting Faculty 2010-11\" Exhibit","start":1283126400,"end":1283212799,"url":"http:\/\/www.ves.fas.harvard.edu\/vesNewFacultyExhibition.html","location":"Carpenter Center Main Gallery","description":"Exhibit on view through Sept. 26The Carpenter Center for the Visual Arts presents work by new visiting faculty in the Department of Visual and Environmental Studies. Artists: Katarina Burin, Marina Rosenfeld, Matt Saunders, Gregory Sholette, Mungo Thomson, Kerry Tribe, and Penelope Umbrico.Reception for the artists: Thursday, Sept. 9, 5:30-6:30 p.m.","custom":{"\"Event Type\"":"Harvard Event","\"Organization\/Sponsor\"":"Carpenter Center for the Visual Arts","\"Cost\"":"Free","\"Contact Info\"":{"email":[],"phone":["617.495.3251"],"url":[],"text":[],"full":"617.495.3251"},"\"Gazette Classification\"":"Art\/Design\\, Exhibitions\\, Special Events"}}]}




==============
Services Used
==============

The following services are being used by the API:

* http://www.trumba.com/calendars/gazette.ics
    - This service is being used for all non-academic Gazette events
    - Parameters used are: *startdate* (yyyymmdd), *enddate* (yyyymmdd), *months* (number), *days* (number), *search* (strings, e.g. "Donkey+Show" for "Donkey Show")

*  http://www.trumba.com/calendars/harvard_academic_calendar.ics
    - This service is being used to all academic events
    - Parameters used are: *startdate* (yyyymmdd), *enddate* (yyyymmdd), *months* (number), *days* (number)
    - Currently, we are not searching in this calendar. But the search queries would look exactly the same as those for the Gazette events

* *events_cat* flat file for all Gazette Categories
