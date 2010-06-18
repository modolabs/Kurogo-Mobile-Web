.. _section-mobiweb-api-people:

=========
Directory
=========

Overview:

* Get a list of people who match given search terms.

In the current MIT implementation, a "match" is a person in the LDAP
database whose name or phone number partially matches given search
terms, or whose email username exactly matches given search term

-------------
API Interface
-------------

All queries to Directory use the base url: http://m.mit.edu/api

All queries to Directory use the following parameter:

* **module**: people

^^^^^^^^^^^^^^^^
Directory Search
^^^^^^^^^^^^^^^^

Get a list of people matching the given search terms:

Parameters:

* **module**: people
* **q**: *searchTerms*

Sample Response:

.. code-block:: javascript

  [
    {
      "surname":["Huang"],
      "givenname":["Sonya Y"],
      "name":["Sonya Y Huang"],
      "dept":["URBAN STUDIES AND PLANNING"],
      "id":"sonya",
      "email":["sonya@mit.edu"],
      "office":["9-536"]
    },
    ...
  ]

---------
PHP Files
---------

mobi-lib/mit_ldap.php
mobi-web/api/index.php
