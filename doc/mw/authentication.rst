##############
Authentication
##############


While many services are suitable for a public audience, there are instances where you want to restrict
access to certain modules or data to authenticated users. You may also want to provide personalized 
content or allow users to participate in feedback.

The Kurogo framework provides a robust system for authenticating users and authorizing access to content.
You can provide the ability to authenticate against private or public services and authorize access or
administration based on the user's identity or membership in a particular group. 

Kurogo is designed to integrate with existing identity systems such as Active Directory, MySQL databases,
Twitter, Facebook and Google. You can supplement this information by creating groups managed by the
framework independent of the user's original identity.

*************************
Setting up Authentication
*************************

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
* Group information and membership - Some authorities will also contain information on groups which allow
  you to logically organize users. Some authorities are designed to only contain users from their
  own domains, while others have the ability to utilize users from other authorities in their membership
  
It is not necessary for an authority to provide all services. It is possible to have one authority
provide user authentication and information, and another provide group information. If you do not
utilize groups in your authorization schemes, you may not need any group information at all.

=======================
Enabling Authentication
=======================

In order to support authenticating users you must set the *AUTHENTICATION_ENABLED* setting in 
*SITE_DIR/config/site.ini* to 1. This setting is disabled by default because if all your modules
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
one has its own unique instructions for setup and use. Please read these documents carefully and be 
aware of important requirements for development and deployment.

.. toctree::
   :maxdepth: 1

   PasswdAuthentication
   DatabaseAuthentication
   LDAPAuthentication
   ActiveDirectoryAuthentication
   CASAuthentication
   FacebookAuthentication
   TwitterAuthentication
   GoogleAuthentication
   GoogleAppsAuthentication
