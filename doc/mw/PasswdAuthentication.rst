########################
Flat File Authentication
########################

The *PasswdAuthentication* class provides authentication and user/group information in a locally 
hosted flat file structure. It represents the simplest form of authentication an group management
and can be run without any external dependancies or services.

=============
Configuration
=============

The *PasswdAuthentication* authority has only 2 additional values beyond the standard:

* *PASSWD_USER_FILE* - a path to the user file. This file can be placed anywhere, but it is recommended to place it in the DATA_DIR
  folder which is mapped to *SITE_DIR/data*. i.e. If you use the DATA_DIR constant it should not be in quotes: *DATA_DIR"/users"*
* *PASSWD_GROUP_FILE* -  a path to the group file. This file can be placed anywhere, but it is recommended to place it in the DATA_DIR
  folder which is mapped to *SITE_DIR/data*. i.e. If you use the DATA_DIR constant it should not be in quotes: *DATA_DIR"/groups"*

=======================
Format of the user file
=======================

The user file is formatted very similar to a typical unix passwd file, with a few modifications. 

Each line represents a single user. Blank lines, or lines that begin with a pound (#) symbol will be ignored.
For each line there are a series of fields separated by colons (:) The field order is as follow:

#. *userID* - a short name for the user
#. *password* - an md5 hash of the user's password (unix users can use md5 -s "password" to generate a hash)
#. *email* - the email address of the user
#. *full name* - the full name of the user


========================
Format of the group file
========================

The group file is formatted very similar to a typical unix groups file

Each line represents a single group. Blank lines, or lines that begin with a pound (#) symbol will be ignored.
For each line there are a series of fields separated by colons (:) The field order is as follow:

#. *group* - a short name for the group
#. *gid* - a numerical id for the group
#. *members* - a comma separated list of users. Each user is represented by their username/email. If the user 
   is from another authority you should enter it as authority|userID
   
=====
Usage
=====

Using this authority is useful when you want to only setup a few accounts that are not connected to
an existing directory system. For instance, if you want to protect a few modules with a simple username
and password.

This is also a good system to use when you want to manage groups of users from an authority that does
not natively support groups. You can create groups made up of users from a variety of authorities to
make it easier to manage authorization. 