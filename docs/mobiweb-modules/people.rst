================
People Directory
================

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

LDAP Queries

The file lib/trunk/mit_ldap contains all the functions for
communicating with the MIT LDAP server.

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

The first of the above rules is accomplished by building the query
with the function email_query($search). The remaining are accomplished
by building the query with the function standard_query($search).
