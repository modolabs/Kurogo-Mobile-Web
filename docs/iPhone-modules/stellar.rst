=========
Stellar
=========
---------
Overview
---------
The stellar module contains several ``UIViewController`` subclasses which each implement the UI
for the various screens.  The ``StellarModel`` which is a singleton class, acts as a proxy
to access stellar data from either the MIT Mobile server or in the iPhones local sqlite database.
Since many of the data access calls in ``StellarModel`` are asyncrohnous, it defines several protocols,
which are used to receive the data at a later time.  The ``StellarCache`` which is also a singleton
class is used for caching data in RAM, specifically stellar caches the the class list for each
department in RAM.  Stellar also caches every class the user visits in RAM, but only saves to disk
the classes that have been bookmarked.

----------------------
User Interface Classes
----------------------
.. class:: StellarMainTableController

   ViewController for the main screen of stellar, the view is grouped style table view with
   the first section being classes that have been bookmarked, this section does not appear
   if it is empty, the second section is groups of department.  There is a core data entity of
   ``StellarCourse`` which correponds to a course.  The courses are cached in core data
   for 30 days and then refreshed, the ``StellarCourseGroup`` class is used to break up all the
   courses into groups based on there course number.  This screen also contains a search bar 
   controlled by a ``UISearchDisplayController``, the search results for this are controlled by
   the class ``StellarSearch``.


.. class:: StellarCoursesTableController

   ViewController for the screen that displays when a user selects on a course group on the main 
   screen.  For example, if a user clickes the "Courses 1-10" row this screen will show up with
   a list of all the courses between 1 and 10 inclusive.  All this data is cached in core data so
   this screen does not require an external network calls.  This view controller needs to be sent
   a object of the class ``StellarCourseGroup`` when it is constructed, this object contains all
   the courses which need to be displayed


.. class:: StellarClassesTableController
   
   ViewController for the screen that displays when a user selects a course from the courses screen
   This screen displays all the classes for that course.  The classes need to be retreived from
   the server, or if the user has already visited this course, the classes are temporarily cached 
   in memory, either way the data is retreived with an asynchronous call to 
   ``+[StellarModel loadClassesForCourse:delegate:]``.  This class needs to be sent an object of
   class ``StellarCourse`` when it is constructed.

.. class:: StellarDetailViewController

   ViewController that corresponds to the screen that displays information for any specific class.
   This screen has three tabs: news, info, and staff.  This ViewController is a subclass of 
   ``UITableViewController``, which switch changes its tableView datasoure and tableView delegate
   when a different tab is selected.  When news is selected it uses ``NewsDataSource`` to populate
   the tableView, likewise the info tab and staff tab correspond to ``InfoDataSource`` and 
   ``StaffDataSource`` respectively.  This class needs to be sent an object of class ``StellarClass`` 
   when it is constructed, however it does not assume the data contained in the ``StellarClass``
   object is up to date.  Therefore when the screen is initial rendered it populates the screen 
   with the initial data from ``StellarClass`` but then calls the server to get the most up to date
   values and re renders the screen.  In fact, the news tab is assumed to be invalid at first,
   so instead of initially showing news items, it just shows the items as loading, and then after
   they load it populates the news tab.  If it fails to contact the server, it shows an an error 
   message, and the displays the old news items.
   