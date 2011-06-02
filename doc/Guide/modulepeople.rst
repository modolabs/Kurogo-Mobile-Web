#################
People Module
#################

The people module enables sites to provide mobile access to their directory. With a few short configuration
parameters you enable searching and detailed information to users on their mobile device. The built-in
module supports connecting to either LDAP based directories (including Active Directory), and database
(MySQL/SQLite) backed directories. 

=================================
Configuring the Server Connection
=================================

The configuration for this module is accomplished by using the :ref:`admin-module` or by editing 
the *SITE_DIR/config/people/feeds.ini* file. There are a variety of values to set in order to connect
to your directory system.

* *CONTROLLER_CLASS* allows you to set a different class name for the controller. Current options include

  * LDAPPeopleController - uses a standard LDAP server. You can configure the various fields if your values
    differ from defaults
  * DatabasePeopleController - connects to an external database server (MySQL/SQLite). This controller assumes
    that people are mapped to a single row and that the various fields are stored in single (definable) columns 
  
* *PERSON_CLASS* allows you to set a different class name for the returned user objects when searching. 
  This allows you to write custom behavior to handle the data in your directory service. The default 
  value is *Person*

--------------------------------
Options for LDAPPeopleController
--------------------------------

* *HOST* - should match the address of your server. Keep in mind that this server must
  be accessible from the web server the framework is hosted on. Managing network and firewall 
  settings is the responsibility of your network administrator.
* *SEARCH_BASE* - should manage the LDAP search base of your directory. You can get this 
  value from the administrator of your LDAP directory. Examples would include "dc=example,dc=com"
* *PORT* - Optional (Default 389) The port to connect. Use 636 for SSL connections (recommended if available)
* *ADMIN_DN* - Some servers do not permit anonymous queries. If necessary you will need to provide a full 
  distinguished name for an account that has access to the directory. For security this account should
  only have read access and be limited to the search bases to which it needs to access.
* *ADMIN_PASSWORD* - The password for the *ADMIN_DN* account.


The following values inform the controller which attributes to use when searching. These values would only
need to be altered if the values differ from the defaults in parentheses.

* *LDAP_USERID_FIELD* (uid)- Stores the user name for this user. Do not choose an attribute that is sensitiveas
  they are easily viewed by users.
* *LDAP_EMAIL_FIELD* (mail) - The attribute of the user's email address
* *LDAP_FIRSTNAME_FIELD* (givenname) - The attribute of the user's first name
* *LDAP_LASTNAME_FIELD* (sn) - The attribute of the user's last name
* *LDAP_PHONE_FIELD* (telephonenumber) - The attribute of the user's phone number

------------------------------------
Options for DatabasePeopleController
------------------------------------

The *DatabasePeopleController* has a number of possible configuration values, all of which
are optional. The following values affect the connectivity to the database system:

* DB_TYPE - The database system currently supports 2 types of connections *mysql* or *sqlite* through PDO
* DB_HOST - used by db systems that are hosted on a server
* DB_USER - used by db systems that require a user to authenticate
* DB_PASS - used by db systems that require a password
* DB_DBNAME - used by db systems that require a database
* DB_FILE - used by db systems the use a file (i.e. sqlite).

If you omit any of the above values, it will default to the settings in :ref:`database_config`
In addition to the connectivity settings, there are several options that tell the controller how to 
query the database. 

The following value inform the controller which table the data is located:

* *DB_USER_TABLE* - (users) The name of the table that stores the user records. This table should at 
  least have fields for userID, name and email. Each row should contain a single user entry. 

The following values inform the controller which fields to use for critical fields. These values would only
need to be altered if the values differ from the defaults in parentheses.

* *DB_USERID_FIELD* (userID)- stores the userID in the user table. You can use any unique column for the userID
  field. Do not use sensitive values as they are easily viewed by users.
* *DB_EMAIL_FIELD* (email) - stores the email in the user table
* *DB_FIRSTNAME_FIELD* (firstname) - stores the first name of user.
* *DB_LASTNAME_FIELD* (firstname) - stores the last name of user.
* *DB_PHONE_FIELD* (no default) - stores the user's phone number. If empty then the search will not use the phone number

The other fields are shown by configuring the detail fields below.

=============================
Configuring the Detail Fields
=============================

Once you have configured the server settings, you need to configure the field mappings between your
server and the detail view. If your LDAP directory uses standard fields, then most fields should
map automatically, however, you may still want to customize how it displays or the order of the fields.

The fields are configured in the *SITE_DIR/config/people/page-detail.ini* file. Each field is 
configured in a section (the section name should be unique, but it otherwise irrelevant).
The order of the sections controls its order in the detail view. Within each section there are several 
possible values to influence how a field is displayed:

* *label* - (required) A text label for the field.  Can include HTML tags.
* *attributes* - (required) Array of fields to put in the contents (should map the the field names in your backend system)
* *format* - (optional) A string for vsprintf to format the attributes. Only needed if more than one attribute is provided.
* *type* - (optional) One of "email", "phone", or "map".  Used to format and generate links.
* *section* - (optional) If this field belongs to a section, the name of that section
* *parse* - (optional) A function which will be run on the LDAP results before display. Generated with 
  *create_function*. Gets the argument "$value" and returns the formatted output.

=============================
Configuring the Fixed Entries
=============================

This module supports the ability to show a list of directory entries on the module index page. You
can update the contents of this list by editing the *SITE_DIR/config/people/page-index.ini*. Each entry
is a numerically 0-indexed list of sections. Each section has 4 values that map to the the values used
by the *listItem* template. Note that because it's displaying a list with URLs, the entries do not
have to be phone numbers, but could be any URL.

* *title* - The Name of the entry as it's shown to the user
* *subtitle* - The subtitle, typically the phone number for phone entries.
* *url* - The link it should point to, use *tel:XXXXXXXX* links for phone numbers
* *class* - The CSS class of the item, such as *phone*, *map*, *email*
