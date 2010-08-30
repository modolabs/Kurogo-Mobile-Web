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



=====================
Dining Hall Statuses
=====================

