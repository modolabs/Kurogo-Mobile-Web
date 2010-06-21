.. _section-mobiweb-Touch:

============
Touch Pages
============

-----------
Description
-----------

Phones which have touch screens but do not have Webkit based browsers, such as the Blackberry Storm or LG Dare.


-----------
Logic Files
-----------

.. class:: TouchPage (extends Page)


------------------
HTML Templates CSS
------------------

  
  * ``base.html`` -- Main template for Touch pages
  * ``core.css`` -- The global stylesheet for Touch pages
  * ``form.html`` -- Template used for pages that have search forms
  * ``help.html`` -- Template used for the help pages
  * ``images/`` -- Icons and other images globally useful for Touch pages
  

----------
Discussion
----------

The touch pages have a notion of being a "Home" page, which means it is the top of the module, and clicking on the module icon will return the user to the home page for the module.

