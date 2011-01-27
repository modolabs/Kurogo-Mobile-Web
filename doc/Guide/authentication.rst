################################
Authentication and Authorization
################################

While many services are suitable for a public audience, there are instances where you want to restrict
access to certain modules or data to authenticated users. You may also want to provide personalized 
content or allow users to participate in feedback.

The Kurogo framework provides a robust system for authenticating users and authorizing access to content.
You can provide the ability to authenticate against private or public services and authorize access or
administration based on the user's identity or membership in a particular group. 

Kurogo is designed to integrate with existing identity systems such as Active Directory, MySQL databases,
Twitter, Facebook and Google. You can supplement this information by creating groups managed by the
framework independent of the user's original identity.

**************
Authentication
**************

Authentication is the process that establishes the users' identity. Typically this occurs when the
user provides a username and password. The framework then tests those credentials against a central
authority. If the authority validates the credentials, the user is logged in and can now consume
authorized services or personalized content.

Authentication by the Kurogo framework is provided through one or more *authentication authorities*. Each
authority is configured to connect to an existing authentication system. Depending on your site's needs
you can utilize a private self-hosted authentication system (like an LDAP/Active Directory or database system)
or a public system (Twitter, Facebook, Google). There are also hybrid approaches that utilize external
services that expose standard authentication services (Google Apps). For simple deployments, you can
also utilize a flat-file based system that requires no external service.

Each authority can provide various services including:

* User authentication - either through a direct login/password form or through an external system
  based on OpenID or OAuth
* User attributes - At minimum authorities should supply id, name and email information. Some authorities
  can provide this information to any user in their system, however others can only provide this information
  on the logged in user
* Group information and membership - Some authorities will also contain information on groups which all
  you to logically organize users. Some authorities are design to only contain users from their
  own domains, while others have the ability to utilize users from other authorities in their membership
  
It is not necessary for an authority to provide all services. It is possible to have one authority
provide user authentication and information, and another provide group information. If you do not
utilize groups in your authorization schemes, you may not need any group information at all.

=======================
Enabling Authentication
=======================

In order to support authenticating users you must set the *AUTHENTICATION_ENABLED* setting in 
*SITE_DIR/config/config.ini* to 1. This setting is disabled by default because if all your modules
are public there is no need to involve the overhead of PHP session management to determine if a user
is logged in or not.

=======================
Configuring Authorities
=======================

Authorities are defined in the *authentication.ini* file in the *SITE_DIR/config* folder. Each authority
is represented by its own section. The section name is referred to as the *authority index*, programmatic
value used by the framework. This value can be whatever value you wish, but you should take care to
not rename this section after you have deployed your site, otherwise it may cause problems if you
refer to it in any module authorization settings. 

Each authority has a number of required attributes and depending on the type of authority it will
have several others. See :doc:`configuration` for more information on configuration files.

The following values are *required* for all authorities:

* *TITLE* - This value is used when referencing the framework to users. If there is more than one
  authority available to users to choose the title will direct them to the correct one.
* *CONTROLLER_CLASS* - This value should map to a valid subclass of *AuthenticationAuthority*. This
  defines the core behavior of the authority. 
* *USER_LOGIN* - There are 3 possible values: 

  * FORM - Use the login form
  * LINK - Use a login link. The authority should handle this using their login method
  * NONE - This authority does not provide authentication services (i.e. just group services)
  
--------------------
Included Authorities
--------------------

To allow the Kurogo framework to operate in a wide variety of settings, the project has included 
several classes that can connect to various types of authentication and authorization services. Each
one has its own unique instructions for setup and use:

* :doc:`PasswdAuthentication` - An authority based on a simple passwd style flat file. Used for small deployments
  where the list of users is small and fixed. Useful for protecting a few modules with a simple user
  name and password that is not connected to any other system. Also has support for a groups file 
  with group information (including users from mixed authorities). 
* :doc:`DatabaseAuthentication` - Can query a database (MySQL, SQLite) for a user name and hashed password. 
  Configuration options allow the setting of the table and field names as well as hashing
  methods. Can also be configured to retrive group information
* :doc:`LDAPAuthentication` - Authenticate a user against a standard LDAP server. Configuration options
  include server information, search base and attributes. Can also be configured to search for
  group information
* :doc:`ActiveDirectoryAuthentication` - a subclass of LDAP Authentication that has a streamlined configuration
  due to common attribute and container information found in Active Directory deployments
* :doc:`FacebookAuthentication` - Allows users to login using their FaceBook account. Requires a api key
  from facebook's developer site. 
* :doc:`TwitterAuthentication` - Allows users to login using their Twitter account. Requires a api key
  from twitters's developer site. 
* :doc:`GoogleAuthentication` - Allows users to login using their Google Account. 
* :doc:`GoogleAppsAuthentication` - Allows users to login to a specific google apps account.

Please read these documents carefully and be aware of important requirements for development and deployment.

*************
Authorization
*************

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