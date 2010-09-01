.. _modules_dining:

********************
Student Dining
********************

The Dining Module provides an interface to the residential dining options on the Harvard Campus. It has the following high-level functionality:

* Breakfast, Lunch and Dinner menus for each day of the year (excluding breaks)
* Dining Hall statuses, hours and restrictions

================
Requirements
================

The Dining Module requires the following static files:

* Menu *.csv* file (titled: "menu.csv")

* Dining Hall hours and restrictions file (titled: "DiningHours")

Both these files are to be located in:
    /Users/muhammadamjad/Documents/work/Harvard/Harvard-Mobile/opt/mitmobile/static

Details about the contents of these files are described in the following sub-sections.

---------
menu.csv
---------

The "menu.csv" contains the menus for each day and meal. Each item in the menu is listed on a separate line, and the attributes are comma-separated. As an example, the following three menu items would serve as three entries:

.. code-block:: javascript

    "08/23/2010","599058","Apple Streudel","LUN","DUNS-MATHER","17","1","PIECE",""
    "08/30/2010","599055","Apple Cranberry Bar","DIN","DUNS-MATHER","17","1","PIECE","VGT"
    "08/28/2010","599051","Lemon Shortbread","LUN","DUNS-MATHER","17","1","PIECE","VGT"

The order in which these attributes appear is also important. The following order must be maintained:

    1. Date
    2. Item/Recipe id
    3. Item Name
    4. Meal-Time (BRK, LUN, DIN)
    5. Location
    6. Food Type
    7. Serving Size
    8. Serving Unit
    9. Meal Category (VGN, VGT, LOC, (OG), (K))


-------------
DiningHours
-------------

The "DiningHours" file contains information about the dining halls (hours and restrictions). It follows the following format:

.. code-block:: php

    *Hall-Name*
    *Breakfast Time*
    *Lunch Time*
    *Dinner Time*
    *Brain-Break Time*
    *Sunday Brunch Time*
    *Lunch Restriction*
    *Dinner Restriction*
    *Sunday Brunch Restriction*

**Note: There are no restrictions for Breakfast and Brain=Break.**

The Restrictions take the following format:

.. code-block:: php

    "Days of Week (separated by "/"), Time applicable, Message (use '$' instead of ',' in messages)"

For any field that does not apply, "NA" is specified.

**Note: Currently, it is assumed that there is only one time that applies to all days specified. This means that only ONE restriction per location is supported. But this can be extended with ease in the future**


An example of the format for two Dining Halls is:

.. code-block:: javascript

    Adams
    7:30-10:00am
    Noon-2:15pm
    5:00-7:15pm
    starting 10:00pm
    11:30am-2:15pm
    Monday/Tuesday/Wednesday/Thursday,Noon-1:30pm,Upperclass: Monday-Thursday$ Noon-1:30pm. First-years: only as an invited guest at any time
    Sunday/Monday/Tuesday/Wednesday/Thursday,6:00-7:00pm,Upperclass: Sunday-Thursday$ 6:00-7:00pm. First-years: only as an invited guest$ at any time. No interhouse dinner on Wednesday for community dining.
    NA,NA,First-years only as an invited guest$ at any time

    Annenberg
    7:30-10:00am
    11:30am-2:15pm
    4:30-7:15pm
    9:15-10:45pm
    11:15am-2:00pm
    NA,NA,Exclusive to first-years only
    NA,NA,Exclusive to first-years only
    NA,NA,Exclusive to first-years only


==============
Daily Menus
==============

The iPhone makes a request for Breakfast, Lunch and Dinner menus for specific dates in the following manner:

    - **Breakfast**: http://m.harvard.edu/api/?module=dining&command=breakfast&date=2010-08-31
    - **Lunch**: http://m.harvard.edu/api/?module=dining&command=lunch&date=2010-08-31
    - **Dinner**: http://m.harvard.edu/api/?module=dining&command=dinner&date=2010-08-31

The calls made by mobile-web are similar:

    - **Breakfast** :http://m.harvard.edu/dining/index.php?time=1283270400&tab=breakfast
    - **Lunch** :http://m.harvard.edu/dining/index.php?time=1283270400&tab=lunch
    - **Dinner** :http://m.harvard.edu/dining/index.php?time=1283270400&tab=dinner

The server then consults the "menu.csv" file, (if not cached) parses it in to menus for each day (and caches the data), and then creates objects for each meal-item and returns the entire meal as a JSON string:

.. code-block:: javascript

        {"item":"Bacon","meal":"BRK","date":"2010-08-31","id":"089001","category":"Breakfast Meats","servingSize":"2","servingUnit":"EACH","type":""},
        {"item":"Chocolate Chip Pancakes","meal":"BRK","date":"2010-08-31","id":"036019","category":"Breakfast Entrees","servingSize":"2","servingUnit":"EACH","type":"VGT"},
        {"item":"Egg Beaters","meal":"BRK","date":"2010-08-31","id":"061041","category":"Breakfast Entrees","servingSize":"4","servingUnit":"OZ","type":"VGT"},
        {"item":"Egg Whites","meal":"BRK","date":"2010-08-31","id":"061042","category":"Breakfast Entrees","servingSize":"4","servingUnit":"OZ","type":"VGT"},
        {"item":"Hard Cooked Eggs","meal":"BRK","date":"2010-08-31","id":"161049","category":"Breakfast Entrees","servingSize":"1","servingUnit":"EACH","type":"VGT"},
        {"item":"Hummus","meal":"BRK","date":"2010-08-31","id":"142539","category":"Breakfast Entrees","servingSize":"2","servingUnit":"OZ","type":"VGN"},
        {"item":"Scrambled Eggs","meal":"BRK","date":"2010-08-31","id":"061003","category":"Breakfast Entrees","servingSize":"4","servingUnit":"OZ","type":"VGT"},
        {"item":"The Breakfast Sandwich","meal":"BRK","date":"2010-08-31","id":"061038","category":"Breakfast Entrees","servingSize":"1","servingUnit":"EACH","type":"VGT"},
        {"item":"Warmed Pancake Syrup","meal":"BRK","date":"2010-08-31","id":"191001","category":"Breakfast Misc","servingSize":"1","servingUnit":"OZL","type":"VGN"},
        {"item":"Shredded Hashbrowns","meal":"BRK","date":"2010-08-31","id":"161048","category":"Starch & Potatoes","servingSize":"4","servingUnit":"OZ","type":"VGN"},
        {"item":"Cream of Wheat","meal":"BRK","date":"2010-08-31","id":"031006","category":"Make or Build Your Own","servingSize":"6","servingUnit":"OZL","type":"VGN"}

The menu is then displayed to the user, organized/grouped by meal **category**.


=====================
Dining Hall Statuses
=====================

The "Locations" tab displays the current status of the Dining Halls and supports a drill-down to view the details.

The iPhone make a query to the server as:
    http://m.harvard.edu/api/?module=dining&command=hours

The Mobile-Web version makes a query as:
    http://m.harvard.edu/dining/index.php?time=1283270400&tab=locations

The server consults the *DiningHours* file and relays the information about all Dining Halls as JSON string:

.. code-block:: javascript

    {"name":"Adams","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 10:00pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday"],"time":"Noon-1:30pm","message":"Upperclass: Monday-Thursday, Noon-1:30pm. First-years: only as an invited guest at any time"}],"dinner_restrictions":[{"days":["Sunday","Monday","Tuesday","Wednesday","Thursday"],"time":"6:00-7:00pm","message":"Upperclass: Sunday-Thursday, 6:00-7:00pm. First-years: only as an invited guest, at any time. No interhouse dinner on Wednesday for community dining."}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"First-years only as an invited guest, at any time"}]},
    {"name":"Annenberg","breakfast_hours":"7:30-10:00am","lunch_hours":"11:30am-2:15pm","dinner_hours":"4:30-7:15pm","bb_hours":"9:15-10:45pm","brunch_hours":"11:15am-2:00pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"Exclusive to first-years only"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"Exclusive to first-years only"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"Exclusive to first-years only"}]},
    {"name":"Cabot","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:30pm","dinner_hours":"5:00-7:30pm","bb_hours":"starting 9:00pm","brunch_hours":"11:30am-2:30pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}]},
    {"name":"Currier","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:30pm","dinner_hours":"5:00-7:30pm","bb_hours":"starting 8:45pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}]},
    {"name":"Dunster","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 8:35pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}]},
    {"name":"Eliot","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 9:00pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday"],"time":"6:00-7:15pm","message":"Monday-Thursday, 6:00-7:15pm, 1 interhouse guest with resident only"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"1 interhouse guest with resident only"}]},
    {"name":"Hillel","breakfast_hours":"NA","lunch_hours":"NA","dinner_hours":"5:00-7:00pm","bb_hours":"NA","brunch_hours":"NA","lunch_restrictions":[{"days":["Saturday"],"time":"NA","message":"Shabbat (Sabbath) meal (Saturday Lunch): special community gatherings open to all undergraduates. Please check the Hillel website for Shabbat dinner times."}],"dinner_restrictions":[{"days":["Friday"],"time":"NA","message":"Shabbat (Sabbath) meal (Friday dinner): special community gatherings open to all undergraduates. Please check the Hillel website for Shabbat dinner times."}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"NA"}]},
    {"name":"Kirkland","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 9:00pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday"],"time":"6:00-7:15pm","message":"Monday-Wednesday, 6:00-7:15pm, 1 interhouse guest with resident only. No interhouse dinner on Thursday for community dining."}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"1 interhouse guest with resident only"}]},
    {"name":"Leverett","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 9:00pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["Sunday","Monday","Tuesday","Wednesday"],"time":"6:00-7:15pm","message":"Sunday-Wednesday, 5:30-7:00pm, 1 interhouse guest with resident only. No interhouse during Thursday community dining only from 5:30-7:00pm."}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"1 interhouse guest with resident only"}]},
    {"name":"Lowell","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 8:30pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],"time":"NA","message":"Monday through Saturday, 1 interhouse guest with resident only"}],"dinner_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],"time":"5:00-6:45pm","message":"Monday thru Saturday, 5:00-6:45pm, 1 interhouse guest with resident only"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"NA"}]},
    {"name":"Mather","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 8:30pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}]},
    {"name":"Pforzheimer","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:30pm","dinner_hours":"5:00-7:30pm","bb_hours":"starting 9:00pm","brunch_hours":"11:30am-2:30pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}]},
    {"name":"Quincy","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 8:30pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"None"}],"dinner_restrictions":[{"days":["Thursday"],"time":"NA","message":"No interhouse on Thursdays for community dinner"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"No First Years"}]},
    {"name":"Winthrop","breakfast_hours":"7:30-10:00am","lunch_hours":"Noon-2:15pm","dinner_hours":"5:00-7:15pm","bb_hours":"starting 8:30pm","brunch_hours":"11:30am-2:15pm","lunch_restrictions":[{"days":["Monday","Tuesday","Wednesday","Thursday","Friday"],"time":"NA","message":"Monday-Friday, 1 interhouse guest with resident only"}],"dinner_restrictions":[{"days":["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"],"time":"5:00-7:00pm","message":"Sunday-Friday 5-7pm, 1 interhouse guest with resident only"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"1 interhouse guest with resident only"}]},
    {"name":"Fly-By","breakfast_hours":"NA","lunch_hours":"11:30am-2:15pm","dinner_hours":"NA","bb_hours":"NA","brunch_hours":"NA","lunch_restrictions":[{"days":["NA"],"time":"NA","message":"NA"}],"dinner_restrictions":[{"days":["NA"],"time":"NA","message":"NA"}],"brunch_restrictions":[{"days":["NA"],"time":"NA","message":"NA"}]}]


All the processing about current status of the Dining Halls is done on the iPhone and Mobile-Web modules themselves and not the server.



==============
Services Used
==============

Currently no love-feeds are being used for the Dining Module. All data is contained in the flat files *menu.csv* and *DiningHours* (described in the **Requirements** section).
