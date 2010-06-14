.. _section-mobiweb-people:

================
People Directory
================


Presents a search bar that returns a list of people found.  Search
terms can match all or part of a person's surname and given names,
exact match for username in email address, or phone number.


The search is designed with the following requirements:

#. If the email username of a directory entry matches the search term
exactly, that directory entry should be listed first.

#. Allow users to search phone numbers. If the search term contains at
least 5 numeric digits, then LDAP is searched for entries whose phone
number attributes contain the search term as a substring.

#. Return results where the search terms are substrings in any part of
the person’s names.  (This is accomplished by wrapping all search
tokens with the wildcard character "*".)

#. Wildcards such as "wil*" and "*fred" are supposed to be substrings,
so will be treated the same way as "wil" and "fred". (This is
accomplished by stripping extra "*" wildcards.)

#. Allow users to search by the person’s initials. If a token contains
only one letter, one of the tokens in the search result (words in the
person’s full name or email) must start with that letter.

#. If the user enters multiple tokens (strings separated by spaces),
each of the tokens has to obey all but the first of the above rules.

----------------------------
Data Sources / Configuration
----------------------------

An LDAP server.
Enter configuration in ``mobi-config/ldap_config.php``.

The MIT LDAP server returns a limit of up to 100 results per query.
There is no way to know how many matches exist above 100.


^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-mit-ldap`

-----------
Logic Files
-----------



^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/people/index.php
^^^^^^^^^^^^^^^^^^^^^^^^^

--------------
Template Files
--------------

The People Directory home screen displays a search box. When the user
enters a query, the following things can happen:

* Multiple search results are returned. The search box is shown again
  with the list of results below. Clicking any of the results brings
  up the detail screen for that person. LDAP returns a maximum of 100
  results, but if the number of results exceeds 50, the user is shown
  a message to refine their search (this behavior is defined in search
  form templates).

* A single search result is returned. If there is only one match, the
  detail screen for the found person is shown.

* No results are returned. The search box is shown again with a
  message saying no results were found.

The detail screen shows each of the following info from the the
person’s directory entry if they are available: name, title,
department, phone numbers, address, email, and office location.

Phone numbers and email addresses are converted into tel: and mailto:
links. For office locations, a link to the Campus Map is returned with
the building selected.



^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/people/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/people/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/people/\*/items.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/people/\*/results.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

