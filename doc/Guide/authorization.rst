################################
Access Control and Authorization
################################

Once a user's identity has been established, it is possible to authorize use of protected modules and
tasks based on their identity. Authorization is accomplished through *access control lists*. Developers
are likely familiar with this concept in other contexts (eg. file systems). 

====================
Access Control Lists
====================

An access control list is a series of rules that defines who is permitted to access the resource (ALLOW rules), and who is expressly
denied to access the resource (DENY rules). Rules can be defined for users and groups.
You can mix and match rules to tune the authorization to meet your site's needs.

Access control lists are defined in either *SITE_DIR/config/acls.ini* (for site authorization) or *SITE_DIR/config/MODULE/acls.ini* 
(for module authorization). Each entry is entered as a numerically indexed section.

If the *site* has an access control list entry (in SITE_DIR/config/acls.ini), then it will be used for ALL modules. This
would protect the entire site. If a *module* has an access control list entry (in SITE_DIR/config/MODULE/acls.ini) it will be protected and only users matching the acl rules
will be granted access. 

For A user will only be granted access if:

    * They match an ALLOW rule, AND
    * They do NOT match a DENY rule

If a user is part of a DENY rule, they will be immediately be denied access.

Access control lists can also be edited in the Administration Console.

------------------------------
Syntax of Access Control Lists
------------------------------

Each ACL is a section in an acls.ini file. The first ACL will be section 0, the second will be section 1 and so on. Each section has a number of keys:

* *type*: Either U (for user access, i.e. who can use the module), or A (for admin access, who can administer the module). 
* *action*: Either A (allow) or D (deny). Deny rules always take precedence. 
* *scope*: Either U (user), G (group) or E (everyone, i.e. ALL users, including anonymous users),
* *authority*: The index of the authority. For user scope this can be blank and would match a user from any authority.
* *value*: The particular user/group to match. For user scope this can be "*" which would match all authenticated users.

To better illustrate the syntax, consider the following examples:

A typical configuration file for the *admin* module might look like this:

.. code-block:: ini

    [0]
    type = "U"
    action = "A"
    scope = "G"
    authority = "ldap"
    value = "admin"
    
    [1]
    type = "U"
    action = "A"
    scope = "G"
    authority = "ad"
    value = "domainadmins"

    [2]
    type = "U"
    action = "D"
    scope = "U"
    authority = "ad"
    value = "Administrator"
    
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