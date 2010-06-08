=====================
Customize Home Screen
=====================

In this version, Customize Home Screen is not a separate module, but
resides under the home directory.

Customize Home Screen gives users the ability to control which modules
to show/hide from the homescreen, and in what order. These preferences
are set in two cookies.

customize_lib.php provides functions to read and write the contents fo
the activemodules and moduleorder cookies.

customize.php, the main file, creates an array of modules to populate
the HTML templates ip/customize.html, ad/customize.html, and
sp/customize.html. For the Android and Smartphone, the array includes
for each module whether it is turned off, a query URL that toggles it
on/off, and query URLs that will move switch its position with the
module above or below. These functions – toggle on/off, move up, move
down – on the iPhone are controlled via JavaScript.

iPhone customize screen

iPhone users are presented with a drag+drop list of modules with
checkboxes indicating whether they should be shown/hidden. Users
change the module order by dragging modules to the desired
location. The drag+drop feature takes advantage of the iPhone’s
multi-touch functionality which is currently unavailable on other
phones (The Touch class is only available on iPhone OS 2.0+).

Initially, the HTML template is rendered with a list of all modules in
the default order, with all checkboxes checked. The JavaScript
function init() (see below) is then called to reorder and add event
listeners to the module list.

Android customize screen

Android users are presented with a list of modules with checkboxes
indicating show/hide status and up/down arrows to move the module
up/down in order. Changes are saved via JavaScript.

Unlike the iPhone screen, the HTML template is initially rendered with
modules in the order and check/uncheck status determined by the
cookies. The contents of the activemodules and moduleorder cookies are
stored in JavaScript arrays. Each checkbox is given the onclick
property toggle(this). Up arrows are given the onclick property
moveUp(this), and down arrows the onclick property moveDown(this. The
function initializeHomeArray() is then called to fetch the list of
modules from the HTML.

