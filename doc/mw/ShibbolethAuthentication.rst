#########################
Shibboleth Authentication
#########################

The Shibboleth authority allows you to authenticate users using the `Shibboleth <http://shibboleth.net>`_
federated identity system. In a properly configured environment it can provide a single sign
on experience for users without exposing credentials to the web server. 

============
Requirements
============

The Shibboleth authority requires a functioning Shibboleth Service Provider (SP) on the Kurogo
server. This typically includes the Shibboleth daemon and an apache module. Kurogo does not include
nor attempt to simulate a Shibboleth SP. You should ensure your SP has been setup and is
properly recognized by your IDP before attempting to configure Kurogo. 

============
Installation
============

It is recommended that Shibboleth be configured to either protect the entire site (by adding
the appropriate apache directives in the configuration) or by protecting the */login*
page by including a *<Location /login>* directive in the Apache configuration. A typical
Apache configuration might look like this::

  <Directory /var/www/> #this should be set to the root of your Kurogo Installation
  	AuthType shibboleth
	require shibboleth
  </Directory>

  <Location /login>
    AuthType shibboleth
    ShibRequestSetting requireSession 1
    require valid-user
  </Location>

The *<Directory>* section ensures that Shibboleth will override anything in Kurogo (this is necessary
to ensure that Shibboleth handles the /SSO and other endpoints it handles instead of Kurogo). The *<Location>*
section ensures that a user is logged in to view the */login* page. If a user is not logged in
they will be redirected to the IDP login page. 

=============
Configuration
=============

Enabling the Shibboleth SP and adding the ShibbolethAuthentication authority is sufficient
to authenticate users to Kurogo. There are several configuration options that will link
shibboleth attributes to user data. All are optional:

* *SHIB_EMAIL_FIELD* - The field containing the user's email address
* *SHIB_FIRSTNAME_FIELD* - The field containing the user's first name
* *SHIB_LASTNAME_FIELD* - The field containing the user's last name
* *SHIB_FUlLNAME_FIELD* - The field containing the user's full name
* *SHIB_ATTRIBUTES[]* - An array of values that will be added as attributes to the user
  object. They can be retrieved using $user->getAttribute($key)
  
============
How it Works
============

The Shibboleth SP will redirect the user to the IDP login page. This process is handled
by Shibboleth completely independent of Kurogo. The Kurogo server never receives any 
credentials. The IDP will redirect back to the Kurogo server and return to the login page.
The ShibbolethAuthentication authority will inspect the PHP *$_SERVER* variable for the *REMOTE_USER*
property and set the user's ID accordingly. It will also populate the user object with
any additional attributes defined by the configuration.