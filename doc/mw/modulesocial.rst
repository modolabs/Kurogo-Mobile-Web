#############
Social Module
#############

The social module shows a list of posts from a variety of social network service accounts.
Currently this includes public feeds from Facebook and Twitter. 

=======================
Configuring the Sources
=======================

The module allows you to show the recent posts from any number of social network accounts. 
Accounts are  configured in the *SITE_DIR/config/social/feeds.ini* file. Each account is 
contained in a section. The name of each section is generally not important, but must be unique. 
Each

Within each section you use the following options:

* *RETRIEVER_CLASS* - The :doc:`Data Retriever <dataretriever>` to use. Currently supported retrievers include:
  
  * *TwitterDataRetriever* - Retrieves tweets from a user's public timeline
  * *FacebookDataRetriever* - Retrieves posts from a user or page's public wall. 
  
* *TITLE* - A textual label for this account
* *ACCOUNT* - The user name / page name for the account to show

Other options are specific to those services.

--------------------
TwitterDataRetriever
--------------------

The Twitter retriever retrieves tweets from a user's public timeline. It is not required,
but it is recommended that you sign up for a Twitter developer account and create a Twitter
application. Once you have done so you should add the necessary oauth information:

* *OAUTH_CONSUMER_KEY* - The OAuth consumer key found in your application's configuration page
* *OAUTH_CONSUMER_SECRET* - The OAuth consumer secret found in your application's configuration page

These values must be present in the account's section in *feeds.ini*, however you can simply
put a value such as "anon". By not including your OAuth key, you will be limited to a certain
number of requests per hour, but due to Kurogo caching the data, you will likely not reach this
limit. It is not necessary to have different keys for each account if you are including 
multiple Twitter accounts. They can share the same values.

---------------------
FacebookDataRetriever
---------------------
The Facebook retriever retrieves posts on a user, group or page's *public* wall. The *ACCOUNT*
portion of the feed configuration should include either the user's name, group name or page name.
For pages that have not applied for a user name, you should include the number found in the URL
(i.e. facebook.com/###########). It is *required* that you register your application with Facebook.
The Facebook API requires an app key and secret. Go to https://developers.facebook.com/apps
to add a new application.

* *OAUTH_CONSUMER_KEY* - The App ID of your application
* *OAUTH_CONSUMER_SECRET* - The App secret of your application

It is not necessary to have different app ids for each account if you are including 
multiple Facebook accounts. They can share the same values.
