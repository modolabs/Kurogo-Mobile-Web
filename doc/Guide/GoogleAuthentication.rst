#####################
Google Authentication
#####################

The Google authority allows you to authenticate users by using their Google
account. This is useful if you have modules that contain personalization and you
don't want to maintain a separate account system. Because the Google system
includes users that are not part of your organization, it is not suitable for
restricting access. 

Google uses a form of *OAuth*. Instead of authenticating directly to Google, the user gets redirected
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

If you have registered your application with google, you should also provide your consumer key and secret

* *OAUTH_CONSUMER_KEY* - Consumer key provided by google
* *OAUTH_CONSUMER_SECRET* - Consumer secret provided by google


============
How it Works
============

OAuth systems work by redirecting the user to an authentication page hosted by the service. The 
application sends a series of values including a URL callback with the request. Once the request 
is complete, the service redirects back to the callback URL and the user is logged in. Google requires
that the user authorize the ability for the application to view the user's email address. 

