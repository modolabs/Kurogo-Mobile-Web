.. _section-mobiweb-emergency:

==============
Emergency Info
==============


Displays text describing the status of any emergency that is happening
on campus, and contact phone numbers for Campus Police, MIT Medical,
and Emergency Status hotline.

----------------------------
Data Sources / Configuration
----------------------------

The location of the RSS feed is
http://emergency.mit.net/emergency/mobirss.  This is defined as the
value of the variable ``EMERGENCY_RSS_URL`` in
``mobi-config/mobi_lib_constants.php``.

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-rss-services`
* :ref:`subsection-mobiweb-EmergencyContacts`

-----------
Logic Files
-----------

^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-lib/rss_services.php
^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/emergency/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/emergency/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/emergency/\*/contacts.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/emergency/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

