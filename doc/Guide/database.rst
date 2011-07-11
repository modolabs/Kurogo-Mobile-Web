###############
Database Access
###############

There are several situations where utilizing a database may be necessary. Kurogo has created a standard
database access abstraction system that utilizes the PDO php library. This section outlines both the
configuration of database connections as well as instructions on how to utilize database calls when
writing modules or libraries.

===========================
Supported Database Backends
===========================

Kurogo includes support for the following database backends. Keep in mind that utilizing these systems
requires the appropriate PHP extension. Installing and configuring the database server and the PHP extensions
is beyond the scope of this document.

* MySQL
* SQLite
* PostgreSQL
* Microsoft SQLServer. Note that support for SQL Server is limited to servers running Microsoft Windows and requires
  the Microsoft Library found at: http://msdn.microsoft.com/en-us/sqlserver/ff657782.aspx

--------------------------------------
An important note about SQL statements
--------------------------------------

The Kurogo database library is merely a connection abstraction library. It is not a SQL abstraction
library. Therefore it is important to make sure that different backend systems do not support the
same SQL language and dialects and you must write your statements accordingly. See :ref:`database_dev` for 
information on targeting back ends if the SQL statements must be different.

=============================================
Kurogo Features that use Database connections
=============================================

* The internal device detection system uses an included SQLite database to store data on browsers
* The :doc:`Statistics Modules <modulestats>`  uses a database to index the access logs and prepare the reports.
  If you are in a load balanced environment, you would want to use a centralized database.
* You can optionally store session data in a database rather than on the server file system by using the SessionDB class.
* The DatabasePeopleController uses a database to get directory information (rather than an LDAP server)
* The DatabaseAuthentication authority uses a database for authentication

Only the features that you require and configure would require a database. It is possible to run Kurogo
without using a database.

.. _database_config:

================================
Configuring Database Connections
================================

There is a primary set of database connection settings in the *database* section of the *site.ini* file.
All database connections (with the exception of the internal device detection database) will use that 
series of settings by default. You can also override those settings by providing the appropriate values
in each particular service's configuration. Regardless of where the settings are set, the keys and
values are similar.

* *DB_DEBUG* - When on, queries are logged and errors are shown on the browser. You should turn this
  off for production sites or you risk exposing SQL queries when there is a database error.
* *DB_TYPE* - The type of database system. Current values include:

  * mysql
  * sqlite
  * pgsql
  * mssql

The following values are valid for host based systems (mysql, pgsql and mssql) 

* *DB_HOST* - The hostname/ip address of the database server. 
* *DB_USER* - The username needed to connect to the server
* *DB_PASS* - The password needed to connect to the server
* *DB_DBNAME* - The database where the tables are located

The following values are valid for file based systems (sqlite)

* *DB_FILE* - The location of the database file. Use the DATA_DIR constant to save the file in the site
  data dir. This folder is well suited for these files. 

.. _database_dev:


=================================
Using the database access library
=================================

If you are writing a module that requires database access, you can utilize the database classes to 
simplify your code and use the same database connections easily. 

* Include the db package: *Kurogo::includePackage('db');*
* Instantiate a db object with arguments, the arguments should be an associative array that contains 
  the appropriate configuration parameters. If the argument is blank then it will use the default
  settings found in the *database* section of site.ini
* Use the *query($sql, $arguments)* method to execute a query. The arguments array is sent as prepared 
  statement bound parameters. In order to prevent SQL injection attacks you should utilize 
  bound parameters rather than including values in the SQL statement itself
* The query method will return a `PDOStatement <http://php.net/manual/en/class.pdostatement.php>`_ object. 
  You can use the *fetch* method to return row data.
* The *lastInsertID* method of the db object will return the ID of the last inserted row.
  
.. code-block:: php

    <?php

    Kurogo::includePackage('db');

    class MyClass
    {
        function myMethod() {
        
            $db = new db();
            
            $sql = "SELECT * FROM sometable where somefield=? and someotherfield=?";
            $result = $db->query($sql, array('value1','value2'));
            while ($row = $result->fetch()) {
                // do something
            }
        }
    }
    