#####################
Google Authentication
#####################

The Google authority allows you to authenticate users by using their Google
account. This is useful if you have modules that contain personalization and you
don't want to maintain a separate account system. Because the Google system
includes users that are not part of your organization, it is not suitable for
restricting access. 

Google uses a form of *OpenID*. Instead of authenticating directly to Google, the user gets redirected
to the Google login page. Then they must authenticate and then authorize access to the application. 
Your application has no access to the user's login or password.

This Authority is suitable for authenticating people with *any* google account. If you wish to limit
logins to people from your Google Apps domain, then please use the :doc:`GoogleAppsAuthentication` 
authority.

=============
Configuration
=============

There is very little to configure for this authority. You simply include a *USER_LOGIN = LINK* value
along with the title and *GoogleAuthentication* controller class.

.. code-block:: ini

    [google]
    CONTROLLER_CLASS        = "GoogleAuthentication" 
    TITLE                   = "Google"
    USER_LOGIN              = "LINK"

------------
How it Works
------------

The Google Authentication system uses OpenID  by redirecting the user to an authentication page
provided by Google. The application sends a series of values including a URL callback with the request. 
Once the request  is complete, the service redirects back to the callback URL and the user is logged in. 
Google requires that the user authorize the ability for the application to view the user's email address. 

=====================
Accessing Google Data
=====================

If you have the need to access Google Data on behalf of the user, then you will need to provide a
consumer key, secret, and scope. These are values that identify the application to Google (and the
user as a result) and identify the types of data you wish to access. If you have no need to access
data, then you do not need to enter these values.

The following values must be included if you wish to access Google Data (

* *OAUTH_CONSUMER_KEY* - Consumer key provided by google. You can provide "anonymous" 
* *OAUTH_CONSUMER_SECRET* - Consumer secret provided by google
* *GOOGLE_SCOPE[]* - A repeatable list of scope URLs that indicate which services you wish to access.
  These are defined by Google and shown at http://code.google.com/apis/gdata/faq.html#AuthScopes. 
  Common examples used by Kurogo include:
  
  * http://www.google.com/calendar/feeds - User calendar data
  * https://apps-apis.google.com/a/feeds/calendar/resource/ - Calendar resources (Google Apps for Business/Edu only)
  * http://www.google.com/m8/feeds - Contacts
  * http://docs.google.com/feeds, http://spreadsheets.google.com/feeds, http://docs.googleusercontent.com - Google docs

.. code-block:: ini

    [google]
    CONTROLLER_CLASS        = "GoogleAuthentication" 
    TITLE                   = "Google"
    USER_LOGIN              = "LINK"
    OAUTH_CONSUMER_KEY      = "anonymous"
    OAUTH_CONSUMER_SECRET   = "anonymous"
    GOOGLE_SCOPE[]          = "http://www.google.com/calendar/feeds"
    GOOGLE_SCOPE[]          = "https://apps-apis.google.com/a/feeds/calendar/resource/"
    GOOGLE_SCOPE[]          = "http://www.google.com/m8/feeds"
    GOOGLE_SCOPE[]          = "http://docs.google.com/feeds"
    GOOGLE_SCOPE[]          = "http://spreadsheets.google.com/feeds"
    GOOGLE_SCOPE[]          = "http://docs.googleusercontent.com"

You should only include scopes for data that you plan on accessing. If you have no need to access
data, then you do not need to enter these values.

