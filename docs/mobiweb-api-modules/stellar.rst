.. _section-mobiweb-api-stellar:

=======
Stellar
=======

Overview:

* Get a list of "Courses" (i.e. departments offering classes)
* Get a list of class subjects offered within a "course"
* Get detail information about a class subject, including instructors,
  times and locations, and announcements
* Search for class subjects by title, subject number, or Course
  (i.e. department) number

-------------
API Interface
-------------

All queries to Stellar use the base URL: http://m.mit.edu/api

All queries to Stellar include the following parameter:

* **module**: stellar
* **command**: *command*

^^^^^^^^^^^^^^^^^^^
Stellar Course List
^^^^^^^^^^^^^^^^^^^

Get a list of all Courses.

Parameters:

* **module**: stellar
* **command**: courses

Sample Response:

.. code-block:: javascript

  [
    {
      "short":"1",
      "name":"Civil and Environmental Engineering",
      "is_course":1
    },
    {
      "short":"2",
      "name":"Mechanical Engineering",
      "is_course":1
    },

    ...

    {
      "short":"SP",
      "name":"Special Programs",
      "is_course":0
    },
    {
      "short":"STS",
      "name":"Science, Technology, and Society",
      "is_course":1
    }
  ]

In the above JSON, "short" is the course ID or common name of the
department (this is what MIT students call their departments).  "name"
is the English name of the department.  "is_course" indicates whether
this is a real department, versus just a collection of classes.

^^^^^^^^^^^^^^^^^^^^
Stellar Subject List
^^^^^^^^^^^^^^^^^^^^

Get a list of subjects within the Course provided

Parameters:

* **module**: stellar
* **command**: subjectList
* **id**: *courseId*

*courseId* is the course number that describes the department at MIT,
for example 6 is EECS.

Sample Response:

.. code-block:: javascript

  [

    ...

    {
      "masterId":"18.06",
      "name":"18.06",
      "title":"Linear Algebra",
      "description":"Basic subject on matrix theory and linear algebra, emphasizing topics useful in other disciplines, including systems of equations, vector spaces, determinants, eigenvalues, singular value decomposition, and positive definite matrices. Applications to least-squares approximations, stability of differential equations, networks, Fourier transforms, and Markov processes. Uses MATLAB. Compared with 18.700, more emphasis on matrix algorithms and many applications.",
      "stellarUrl":"\/course\/18\/su10\/18.06",
      "times":[
        {
          "title":"Lecture",
          "time":"MWF 11-12:30 p.m.",
          "location":"2-131"
        }
      ],
      "staff":{
        "instructors":["Alejandro Henry Morales","Emanuel Stoica"],
        "tas":[]
      },
      "term":"su10"
    },

    ...

    {
      "masterId":"18.UR",
      "name":"18.UR",
      "title":"Undergraduate Research",
      "description":"Undergraduate research opportunities in mathematics. Permission required in advance to register for this subject. For further information, consult the departmental coordinator.",
      "term":"su10"
    }
  ]

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Stellar Subject List Checksum
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Get the checksum of the subject list within a Course (to see whether
or not the list on the device is up-to-date)

Parameters:

* **module**: stellar
* **command**: subjectList
* **id**: *courseId*
* **checksum**: true

*courseId* is the course number that describes the department at MIT,
for example 6 is EECS.

*checksum* needs to be "true" to grab the checksum.

Sample Response:

.. code-block:: javascript

  {"checksum":"2326622a7143ef934161970c211dbc4f"}

^^^^^^^^^^^^^^^^^^^^^^^
Stellar Subject Details
^^^^^^^^^^^^^^^^^^^^^^^

Get detailed information about a subject.

Parameters:

* **module**: stellar
* **command**: subjectInfo
* **id**: *subjectId*

*subjectId* is the master subject ID (see
:ref:`section-mobiweb-stellar` in Mobile Web).

Sample Response:

.. code-block:: javascript

  {
    "masterId":"18.06",
    "name":"18.06","title":
    "Linear Algebra",
    "description":"Basic subject on matrix theory and linear algebra, emphasizing topics useful in other disciplines, including systems of equations, vector spaces, determinants, eigenvalues, singular value decomposition, and positive definite matrices. Applications to least-squares approximations, stability of differential equations, networks, Fourier transforms, and Markov processes. Uses MATLAB. Compared with 18.700, more emphasis on matrix algorithms and many applications.","stellarUrl":"\/course\/18\/su10\/18.06",
    "times":[
      {
        "title":"Lecture",
        "time":"MWF 11-12:30 p.m.",
        "location":"2-131"
      }
    ],
    "staff": {
      "instructors":["Alejandro Henry Morales","Emanuel Stoica"],
      "tas":[]
    },
    "announcements":[
      {
        "date":{
          "year":2010,
          "month":6,
          "day":15,
          "hour":21,
          "minute":16,
          "second":0,
          "fraction":0,
          "warning_count":0,
          "warnings":[],
          "error_count":0,
          "errors":[],
          "is_localtime":true,
          "zone_type":1,
          "zone":240,
          "is_dst":false,
          "relative":{
            "year":0,
            "month":0,
            "day":0,
            "hour":0,
            "minute":0,
            "second":0,
            "weekday":2
          }
        },
        "unixtime":1276650960,
        "title":"comments pset 2",
        "text":"In question 4(d) first I want you to make an observation about the rows of matrices formed by multiplying a column and row vector. Then show the identity about a different way of taking the product of two 3x3 matrices. In question 6(a) you are showing that the inverse is unique. You can first show that B=C and then show that they have to be the inverse of A. If you are using A^(-1) to cancel matrices in your proof that B=C then you are not doing it correctly. For parts (b)-(d) you can use A^(-..."
      },

      ...

    ],
    "term":"su10"
  }

^^^^^^^^^^^^^^
Search Stellar
^^^^^^^^^^^^^^

Search for subjects by Course, subject number, or title.

Parameters:

* **module**: stellar
* **command**: search
* **query**: *searchTerms*

*searchTerms* is whatever the user entered into the search bar, with
strings escaped with necessary means.

Sample Response (for the query "physics"):

.. code-block:: javascript

  [
    {
      "masterId":"8.391",
      "name":"8.391",
      "title":"Special Problems in Graduate Physics",
      "description":"Advanced problems in any area of experimental or theoretical physics, with assigned reading and consultations.",
      "term":"su10"
    },

    ...

    {
      "masterId":"HST.204",
      "name":"HST.204",
      "title":"Industrial Experience in Medical Engineering and Medical Physics","description":"An individually arranged full-time eight week (or longer) internship in an industrial environment in the field of medical engineering\/medical physics. Students participate in a clinically related research and\/or development project. Students required to attend a series of industry-related seminars during the term before the internship. A term paper and final presentation are required. May not be repeated for credit.",
      "term":"su10"
    }
  ]

^^^^^^^^^^
My Stellar
^^^^^^^^^^

See push notifications.

---------
PHP Files
---------

mobi-lib/StellarData.php
mobi-web/api/index.php
mobi-web/api/stellar.php

