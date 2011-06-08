######################
Twitter Authentication
######################

The Twitter authority allows you to authenticate users by using their Twitter
account. This is useful if you have modules that contain personalization and you
don't want to maintain a separate account system. Because the Twitter system
includes users that are not part of your organization, it is not suitable for
restricting access. 

Twitter uses a form of *OAuth*. Instead of authenticating directly to Twitter, the user gets redirected
to the Twitter login page. Then they must authenticate and then authorize access to the application. 
Your application has no access to the user's login or password.

In order to successfully authenticate users using Twitter, you must first register your application.

* Go to http://dev.twitter.com/apps/new
* Enter an Application Name, description and URL. The Application type should be browser. The callback URL  
  should match the domain of your site
* Click to register the application
* In the OAuth 1.0a settings section, take note of the Consumer Key and Consumer secret

=============
Configuration
=============

There are a few parameters you need to configure:

* *USER_LOGIN* - Must be set to LINK, which will show a link to the Twitter login page.
* *OAUTH_CONSUMER_KEY* - Set this to the *Consumer Key* provided by Twitter
* *OAUTH_CONSUMER_SECRET* - Set this to the *Consumer Secret* provided by Twitter

You should keep your consumer key and secret protected since they represent your application's identity. 
Do not place these values in a public source code repository.


============
How it Works
============

`OAuth <http://oauth.net/>`_ systems work by redirecting the user to an authentication page hosted by the service. The 
application sends a series of values including a URL callback with the request. Once the request 
is complete, the service redirects back to the callback URL. 
