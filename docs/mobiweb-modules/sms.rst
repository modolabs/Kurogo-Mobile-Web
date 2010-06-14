.. _section-mobiweb-sms:

===
SMS
===

Provides an overview of the interactive SMS service and a cheat sheet
for all general and module commands.

----------------------------
Data Sources / Configuration
----------------------------

The SMS server is at http://sms1.mit.edu

It does not currently expose an API for SMS commands, thus manual
hard-coding is done on the Mobile Web.

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^
mobi-web/sms/index.php
^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------

.. class:: Module

Wrapper for storing module name, description, and keywords (command to
enter in the text message), and examples.

.. class:: SMSInstructions

Wrapper around a list of Module objects



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/sms/Basic/module.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/sms/Webkit/images
^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/sms/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^

