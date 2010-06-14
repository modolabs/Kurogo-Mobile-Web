.. _section-mobiweb-techcash:

========
TechCASH
========

On successful connection, displays the user's full name, last 4 digits
of their MIT ID number, and a summary of all their existing TechCASH
accounts. If the user has no accounts, the message "No TechCASH
accounts found" is shown.

----------------------------
Data Sources / Configuration
----------------------------

Requires SSL and personal certificates.

Connects to Oracle databases on the TechCASH and MIT Data Warehouse
servers.  The latter is used as a backup when the former fails to find
the correct ID number.


^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-tech-cash`

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/techcash/detail.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/techcash/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/techcash/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/techcash/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

