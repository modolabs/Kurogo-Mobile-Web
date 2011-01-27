#############
Authorization
#############

Once a user's identity has been established, it is possible to authorize use of protected modules and
tasks based on their identity. Authorization is accomplished through *access control lists*. Developers
are likely familiar with this concept in other contexts (eg. file systems). 

====================
Access Control Lists
====================

An access control list is a series of rules that defines who is permitted to access the resource (ALLOW rules), and who is expressly
denied to access the resource (DENY rules). Rules can be defined for users, groups or entire authorities.
You can mix and match rules to tune the authorization to meet your site's needs.

Access control lists are defined in the module's configuration file *SITE_DIR/config/modules/MODULE.ini* Each 
entry is entered as a series of *acl[]* entries. The brackets indicates to PHP that the acl attribute is
an array of values.

If a module has an access control list entry it will be protected and only users matching the acl rules
will be granted access. A user will only be granted access if:

    * They match an ALLOW rule, AND
    * They do NOT match a DENY rule

If a user is part of a DENY rule, they will be immediately be denied access.

------------------------------
Syntax of Access Control Lists
------------------------------

Each access control list contains 3 parts, separated by a colon ":". The parts represent:

#. The action of the rule. This is either *A* (allow) or *D* (deny).
#. The rule type. Current types include *U* (user), *G* group or *A* authority
#. The rule value. If you have multiple authorities use "AUTHORITY|value". The default authority is
   the one defined first in the *SITE_DIR/config/authentication.ini* file
   
   * For users: use the userID or email address. 
   * For groups: use the short name or gid for the group. 
   * For authorities: use the *authority index* 
      
To better illustrate the syntax, consider the following examples:

* *A:U:admin* - Allow the user with the userID of *admin* from the default authority
* *A:G:staff* - Allow the group with the short name of *admin* from the default authority
* *A:A:ldap* - Allow all users from the authority with the index of *ldap*
* *D:G:ldap|students* - Deny users from the group *students* from the *ldap* authority
* *A:U:google|user@gmail.com* - Allow a user with the email user@gmail.com from the *google* authority 

A typical configuration file for the *admin* module might look like this:

.. code-block:: ini

    title = "Admin"
    disabled = 0
    search = 0
    secure = 0
    acl[] = "A:G:ldap|admin"
    acl[] = "A:G:ad|domainadmins"
    acl[] = "D:U:ad|Administrator"
    
This would allow members of the group *admin* of the ldap authority and members of the *domainadmins* group
in the ad authority to access this module, but specifically deny the *Administrator* user in the ad authority.

=========================================================
Using the Flat-file Authority to extend other authorities
=========================================================

The flat file authority *PasswdAuthentication* allows you to specify users and groups using a flat-file 
structure stored on the web server rather than on another system. this is useful in situations where
you do not want the burden of maintain an authority system because your user base is small.

Another use of this is to use a central system for user authentication, but use the flat files for
groups management. This technique is useful when you do not have direct control over the administration
of the authority and therefore cannot create groups (or the authority does not inherently support groups)

You can also use the *DatabaseAuthentication* authority to store just group information if you have 
a database server (or use a SQLite file) under your control. 

View the instructions for those authorities for more information on using them. If you do not wish
to use authorities for user logins, set the *USER_LOGIN* value to NONE. This will allow the authority
to be referenced for group information but will not attempt to authenticate users.