############
Login Module
############

The login module is used by sites that provide protected or personalized experience for their modules.
It provides a unified interface to log into the site. 

In order to use the login module you must first configure :doc:`authentication`.

=============
Configuration
=============

There are several configuration values that affect the display of the login module. They are all
in the *strings* section of *SITE_DIR/config/login/module.ini*

* *LOGIN_INDEX_MESSAGE* - A message shown at the top of the authority choose screen (index). Not shown if there is only 1 direct authority.
* *LOGIN_INDIRECT_MESSAGE*  - A message shown at the heading of the indirect authorities. Typically explains that the user will be redirected to another site and then returned.
* *LOGIN_DIRECT_MESSAGE* - A message shown at the top of the login form for direct authorities
* *LOGIN_LABEL* - The label used for the user name field of the login form. This only shows up for logins to direct authorities.
* *PASSWORD_LABEL* - The label used for the password field of the login form. This only shows up for logins to direct authorities.
* *FORGET_PASSWORD_URL* - If specified, a url that is included in the footer of the login form. This only shows up for logins to direct authorities.
* *FORGET_PASSWORD_TEXT* - If specified, text that is included in the footer of the login form. This only shows up for logins to direct authorities.


