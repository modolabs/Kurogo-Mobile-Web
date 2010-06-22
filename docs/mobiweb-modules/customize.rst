.. _section-mobiweb-customize:

=====================
Customize Home Screen
=====================

Customize Home Screen gives users the ability to control which modules
to show/hide from the homescreen, and in what order. These preferences
are set in the two cookies
:ref:`subsubsection-mobiweb-cookies-activemodules` and
:ref:`subsubsection-mobiweb-cookies-moduleorder`.

-----------
Logic Files
-----------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/customize_lib.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Provides functions to read and write the contents from the
activemodules and moduleorder cookies.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Creates the array of modules to populate the HTML templates.

For non-Webkit platforms, the array includes for each module whether
it is turned off, a query URL that toggles it on/off, and query URLs
that will move its position with the module above or
below.

For the Webkit phones, functions that toggle on/off and move up/down are in
JavaScript.  The iPhone uses JavaScript that allows the user to drag
a module up and down, where as the other Webkit phones use up/down arrows.



--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Webkit/index-iphone.\*
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Files for iPhone customize screen.

HTML template is rendered with all modules in the default order (in
case users have JavaScript disabled).  The onload function,
``init()``, reorders and adds event listeners to the page.

JavaScript file contains functions to populate drag+drop list of
modules.  The JavaScript Touch class is available on iPhone OS 2.0 and
above.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Webkit/index.\*
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Files for Android and webOS customize screen.

Presents the list of modules with checkboxes indicating show/hide
status and up/down arrows to move the module up/down in order.
Changes are saved via JavaScript.

HTML template is initially rendered with modules in the order and
check/uncheck status determined by the cookies.  Contents of the
activemodules and moduleorder cookies are stored in JavaScript
arrays. Each checkbox is given the onclick property
``toggle(this)``. Up arrows listen for ``moveUp(this)``, and down
arrows the listen for ``moveDown(this)``.  ``initializeHomeArray()``
is then called at the end to fetch the list of modules from the HTML.


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Basic/images
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Webkit/images
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Touch/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/customize/Basic/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

