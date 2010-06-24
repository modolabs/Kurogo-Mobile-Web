-------------------------
Configuring Look and Feel
-------------------------

The constants which define colors and fonts are found
in ``Common/MITUIConstants.h``

^^^^^
Fonts
^^^^^

By default the app uses Helvetica for most text, to change this
edit the following constants:

.. code-block:: objective-c

   #define STANDARD_FONT @"Helvetica"
   #define BOLD_FONT @"Helvetica-Bold"


**************
Tableview Rows
**************

Tableviews cells have two types of text, with corresponding constants:

* CELL_STANDARD -- A large bold text for the row title

  * CELL_STANDARD_FONT_SIZE -- Font size.
  * CELL_STANDARD_FONT_COLOR -- Font color.
* CELL_DETAIL -- A small normal text for the main content of the row

  * CELL_DETAIL_FONT_SIZE -- Font size.
  * CELL_DETAIL_FONT_COLOR -- Font color.

*****************
Tableview Headers
*****************

The fonts in tableview section headers is customizable with 
the constants:

* TABLE_HEADER_FONT_SIZE -- Font size.
* UNGROUPED_SECTION_FONT_COLOR -- Font color.
* GROUPED_SECTION_FONT_COLOR -- Font color.

Examples of ungrouped tableviews are the various search result
lists.
Examples of a grouped tableviews can be seen on the initial
screen of the People Directory and Stellar modules.

****************
Standard Content
****************

There is also a STANTARD_CONTENT font used outside of table views
with the following constants:

* STANDARD_CONTENT_FONT_SIZE -- Font size.
* STANDARD_CONTENT_FONT_COLOR -- Font color.

^^^^^^
Colors
^^^^^^

**********
Search Bar
**********

The color of the search bar is controlled by 
the constant ``SEARCH_BAR_TINT_COLOR``.

*********
Tab Strip
*********

The color of tab strip (which can be seen at the 
top News and Events module)
is controlled by the following background images

* ``Resources/global/scrolltabs-background-opaque.png``
* ``Resources/global/scrolltabs-background-transparent.png``
* ``Resources/global/scrolltabs-leftarrow.png``
* ``Resources/global/scrolltabs-rightarrow.png``
* ``Resources/global/scrolltabs-selected.png``

****************
Tableview Colors
****************

Looking at the People Directory module, you can see an example 
of a group tableview with primary and secondary rows.
The cells with "Phone Directory" and "Emergency Contacts" are
secondary cells, the cells under "Recently Viewed" are primary cells.
The background color of these cells are controlled by the constants:

* ``PRIMARY_GROUP_BACKGROUND_COLOR``
* ``SECONDARY_GROUP_BACKGROUND_COLOR``
