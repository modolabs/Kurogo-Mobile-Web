=======
Stellar
=======

In this documentation, the full set of scheduled classroom offerings
by a single department will be called a "Course", while a single
offering will be called a "subject". Unfortunately the code itself
often uses "class" instead of "subject" as part of a variable or
function name.

The Stellar module provides an interface that reads XML feeds
published on http://stellar.mit.edu.

The entry page of Stellar (index.php) provides two options for the
user to find a subject:

#. Browsing a drill-down list
#. Searching for the subject

Key classes and functions are provided in stellar_lib.php in the same
directory, and lib/trunk/stellar.php

Stellar XML data and sources

Each Course has an associated XML file for each term. The URL of this
XML file for Course 6 in the Fall 2008 term, for example, is
http://stellar.mit.edu/courseuide/course/6/fa08/index.xml

This file lists each subject in the Course and information about
subjects such as title, instructors, time, location, and
description. The DTD for this file (as of May 2008) can be found
here. The XML Schema (also as of May 2008, never checked by creator)
can be found here.

Each subject has an RSS feed for public announcements. The URL for the
subject “2.705” for the Summer 2009 term, for example, is
http://stellar.mit.edu/SRSS/rss/course/2/su09/2.705/

We do not know of a source that provides a list of all Courses. A list
is currently hard-coded in lib/trunk/stellar.php.

StellarData class in lib/trunk/stellar.php

In addition to XML files from Stellar, we maintain two MySQL tables
ClassID and Class, which are populated each term by reading each
Course XML file.

These tables can be populated by running the script save_stellar.php,
which calls StellarData::populate_db(). However, the script assumes
the table is empty and does not currently include a TRUNCATE TABLE
statement; this must be done manually.

The ClassID table

This table has the fields main_course_id, main_subject_id,
this_course_id, this_class_id.

Some subjects are cross-listed across departments or within its own
department for different types of credit, and are thus assigned
multiple Courses and/or subject numbers. This table stores mappings
between the main Course/subject numbers and alternative Course/subject
number.

The Class table

This table has the fields course_id, class_id, title, name.

course_id and class_id correspond to the main Course number and
subject numbers. name is defined in the Stellar XML file and is
generally a concatenation of Course number, a period, and subject
number (e.g. “6.001”). For cross-listed subjects, the name is a
slash-separated list of all Course.subject numbers (e.g. “1.021 /
10.333 / 22.00 / 3.021”).

Key methods in StellarData

* StellarData::populate_db(): The two tables above are populated by
  calling this method.
* StellarData::get_classes($course) reads the Course XML file for
  relevant information and returns an array of subjects along with
  subject ID, title, description, instructors etc.
* StellarData::get_class_id($id) finds a subject’s main ID number from
  the MySQL tables, given any alternative ID number.  Some stellar
  sites are not really classroom subjects and do not have a standard
  ID number (examples are 9.PDP and the Faculty Environmental Network
  under SP); in these cases name (from the table Class) is returned.
* StellarData::get_class_info($id) fetches info about a subject from
  the relevant node in StellarData::get_classes($course), and
  announcements from the subject’s RSS feed.
* StellarData::search_classes($terms) returns information of a subject
  if $terms looks like a subject ID, otherwise it searches for
  subjects whose titles contain $terms.

Browsing for subjects

courses.php provides the top-level drill-down list. The list items –
Courses 1-10, Courses 11-20, Courses 21-24, and Other Courses – are
hard-coded in the HTML templates ip/index.html and sp/index.html. Each
item links to a second-level drill-down list in course.php.

stellar_lib.php provides functions to generate the URLs that the list items above are linked to.

Searching for subjects

When the user searches for terms from the search box search.php calls
StellarData::search_classes() to get a list of search results. If
multiple results are returned, a list os matches is shown; if one
result is returned, the detail screen is shown for the one matching
subject.

Detail screen

The detail screen shows three tabs: Announcements, Info, and
Staff. The HTML for the tabs is created using the class Tabs from
page_builder/page_tools.php

Announcements

If the text of an announcement is longer than a specified length
(currently defined as 80 characters), it is truncated (see String
formatting functions) and users are shown a link to display the full
text. On iPhone pages the text is expanded via JavaScript. On
smartphone and featurephone pages the link directs the user to a
separate page (announcement.php) containing the full text. A PHP
session variable is used to keep track of which subject the user is
viewing when switching between full announcement view and subject
detail view.

Info

This includes lecture times, location, and
description. mapURL($location) creates a link to the Campus Map for
the classroom location.

Staff

This includes professors and TAs. personURL($name) creates a link to
the People Directory for the staff member’s contact information.
