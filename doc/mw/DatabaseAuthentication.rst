#######################
Database Authentication
#######################

The *DatabaseAuthentication* class provides authentication and user/group information in a relational
database. It can either use the default database setup by the main configuration file or use a different
system with its own credentials and settings. You can customize the tables used to lookup authentication
data, and the fields to use in those tables. You can also specify the algorithm used to hash the 
password. The authority also presents a sensible set of default settings to allow easy setup of a 
new series of tables with minimal configuration. 

Note that this authority does not currently support the ability to actually create and manage users
or groups. It is assumed that you are connecting this to an existing system or you are managing
the users and groups directly or through another utility. This is a read only system.

=============
Configuration
=============

The *DatabaseAuthentication* authority has a number of possible configuration values, all of which
are optional. The following values affect the connectivity to the database system:

* DB_TYPE - The database system currently supports 2 types of connections *mysql* or *sqlite* through PDO
* DB_HOST - used by db systems that are hosted on a server
* DB_USER - used by db systems that require a user to authenticate
* DB_PASS - used by db systems that require a password
* DB_DBNAME - used by db systems that require a database
* DB_FILE - used by db systems the use a file (i.e. sqlite).

If you omit any of the above values, it will default to the settings in *SITE_DIR/config/site.ini*
In addition to the connectivity settings, there are several options that tell the authority how to 
query the database. It is not necessary to include both user and group information if you only need
one.

The following values inform the authority which database tables the data is located:

* *DB_USER_TABLE* - (users) The name of the table that stores the user records. This table should at 
  least have fields for userID, password and email. It can also have fields for first/last name or full name.
  Each row should contain a single user entry
* *DB_GROUP_TABLE* (groups) The name of the table that stores group information. It should have fields
  for shortname and group id. Each row should contain a single group entry.
* *DB_GROUPMEMBERS_TABLE* - (groupmembers) The name of the table that stores the members of each group,
  it should have a field for the group name/id and the userID of the user.  Each row should contain
  an entry that contains the group name and userID. The system will search for members that match
  the group name.
  
The following values inform the authority which fields to use:

* *DB_USER_USERID_FIELD* (userID)- stores the userID in the user table. For systems that use the email
  address as the key, you should include the email field
* *DB_USER_PASSWORD_FIELD* (password) -stores a hashed value of the user's password. See DB_USER_PASSWORD_HASH
  for possible hashing algorithms (Default is md5)
* *DB_USER_EMAIL_FIELD* (email) - stores the email in the user table
* *DB_USER_FIRSTNAME_FIELD* (empty) - stores the first name of user. Won't be used unless it is specified
* *DB_USER_LASTNAME_FIELD* (empty) - stores the last name of user. Won't be used unless it is specified
* *DB_USER_FULLNAME_FIELD* (empty) - stores the full name of user. Won't be used unless it is specified
* *DB_GROUP_GROUPNAME_FIELD* (group) - stores the short name of the group in the group table
* *DB_GROUP_GID_FIELD* - (gid) - stores the group id of the group in the group table. Should be numerical
* *DB_GROUPMEMBER_GROUP_FIELD* (gid) - which field to use when looking up groups in the group member table. 
  This is typically the same value as the group name or gid field
* *DB_GROUPMEMBER_USER_FIELD* (userID) - which field to use when looking up user in the group member table. 
  This is typically either the userID or the email
* *DB_GROUPMEMBER_AUTHORITY_FIELD* - If present you can store the authority index in this field. This allows
  the system to map group members to other authorities.

Other values affect how the group membership is keyed

* *DB_GROUP_GROUPMEMBER_PROPERTY* - (gid) - This is not stored in the database, but refers to which field
  will be used to look up group information in the group member table. Valid values are *gid* or *group* (i.e. the shortname)
  gid is the default.


There are other values that affect the method of password hashing

* *DB_USER_PASSWORD_HASH* (md5) - This is a string that represents a valid hashing function. It indicates what
   hashing algorithm is used to store the password. See `hash_algos() <http://www.php.net/manual/en/function.hash-algos.php>`_
   for a list of valid hashing algorithms. Keep in mind that available algorithms may differ by PHP
   version and platform. You can also use the *hmac_* prefix to use the hmac signing method (i.e. hmac_sha1). This requires setting
   the *DB_USER_PASSWORD_KEY* value.
* *DB_USER_PASSWORD_KEY* (empty) - Necessary if you use the more secure *hmac* variant hashing algorithms. This uses a
  shared key to sign the value using *hash_hmac*
* *DB_USER_PASSWORD_SALT_BEFORE* (empty) - If present this string will be *prepended* to any string as a salt
  value before hashing. This is useful if you are using fixed salts.
* *DB_USER_PASSWORD_SALT_AFTER* (empty) - If present this string will be *appended* to any string as a salt
  value before hashing. This is useful if you are using fixed salts.
* *DB_USER_PASSWORD_SALT_FIELD_BEFORE* (empty) - If present the value of this field for the user will be *prepended*
  to any string as a salt value before hashing. This is useful if you are using variable salts.
* *DB_USER_PASSWORD_SALT_FIELD_AFTER* (empty) - If present the value of this field for the user will be *appended*
  to any string as a salt value before hashing. This is useful if you are using variable salts.
   
============
How it Works
============

-------------------
User Authentication
-------------------

If you support user authentication (by setting the USER_LOGIN option to FORM) the authority will look
in the *USER_TABLE* and look for a record with the *DB_USER_USERID_FIELD* or *DB_USER_EMAIL_FIELD* matching the login typed in.
If that record is found it will see if the value in the *DB_USER_PASSWORD_FIELD* matches the hashed
value of the password typed in (using the *DB_USER_PASSWORD_HASH* algorithm). The hash methods
used in the database and in the configuration must match.

-----------
User Lookup
-----------

Users are looked up in the *DB_USER_TABLE* and look for a record with the *DB_USER_USERID_FIELD* or *DB_USER_EMAIL_FIELD*
matching the value requested. If found, a user object is populated with *DB_USER_USERID_FIELD*, *DB_USER_EMAIL_FIELD*
and *DB_USER_FIRSTNAME_FIELD*,*DB_USER_LASTNAME_FIELD* and *DB_USER_FULLNAME_FIELD* if present.

------------
Group Lookup
------------

Groups are looked up in the *DB_GROUP_TABLE* and look for a record with the *DB_GROUP_GROUPNAME_FIELD* or *DB_GROUP_GID_FIELD*
matching the value requested. If found, a group object is populated with the *DB_GROUP_GROUPNAME_FIELD* and *DB_GROUP_GID_FIELD*
values.

-----------------------
Group Membership Lookup
-----------------------

Group membership is queried in the *DB_GROUPMEMBERS_TABLE*. The getMembers() method will construct
an array of user objects using the *DB_GROUPMEMBER_USER_FIELD*. All users that match the *DB_GROUPMEMBER_GROUP_FIELD*
will be returned (using the value of the groups *DB_GROUP_GROUPMEMBER_PROPERTY*, i.e. gid or short name) The user
objects are created from the authority referenced by the *DB_GROUPMEMBER_AUTHORITY_FIELD*. If there is no authority field it will use the same
authority as the group (i.e. it will use the *DB_USER_TABLE*). Because of the ability to include the
authority field. You can reference users from other authorities in this table (i.e. ldap users, google users, etc)

====================
Using Default Values
====================

If you simply wish to include your own reference database, you can use all the default values with tables 
defined as such:

.. code-block:: sql

  CREATE TABLE users (userID varchar(64), password varchar(32), email varchar(64), firstname varchar(50), lastname varchar(50));
  CREATE TABLE groups (`group` varchar(16), gid int);
  CREATE TABLE groupmembers (gid int, authority varchar(32), userID varchar(64));

This will give you a table structure compatible with the default values.