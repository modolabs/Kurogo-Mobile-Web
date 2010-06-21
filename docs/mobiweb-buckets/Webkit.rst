.. _section-mobiweb-Webkit:

============
Webkit Pages
============

-----------
Description
-----------

iPhone, Android, and webOS devices.


-----------
Logic Files
-----------

.. class:: WebkitPage (extends Page)


------------------
HTML Templates CSS
------------------

  * ``base.html`` -- Main template for Webkit pages
  * ``core.css`` -- The global stylesheet for Webkit pages
  * ``form.html`` -- Template used for pages that have search forms
  * ``help.html`` -- Template used for the help pages
  * ``images/`` -- Icons and other images globally useful for Webkit pages
  * ``uiscripts.js`` -- Javascript useful for several of the modules
  

----------
Discussion
----------

The Webkit pages have "Breadcrumbs" at the top of the page, which act like a navigation stack that the user can drill down into.  These pages have a notion of being a "Home" page, which means it is the top of the breadcrumb navigation stack, and there is no page above it in the stack to go back to.

