#################
Photos Module
#################

The photos module enables sites to provide mobile access to photos hosted by 3rd party websites
such as flickr and Picassa

=====================
General Options
=====================

There are a few options in *SITE_DIR/config/photos/module.ini* that can configure basic operations of
the photos module:

[module]

* *MAX_RESULTS* - The number of photos to show per page.
* *BOOKMARKS_ENABLED* - Set to 1 to enable or 2 to disable bookmarking photos
* *SHARING_ENABLED* -  Set to 1 to enable or 2 to disable sharing photos

[strings]

* *description* - This text will show at the top of the index page

=======================
Configuring the Sources
=======================

The module allows you to organize your photos by album using a distinct feed for each album. Each
section contains information on the service provider and can retrieve photos from a particular user's
account. Feeds are  configured in the *SITE_DIR/config/photos/feeds.ini* file. Each feed is contained in a section. 
The name of each section is generally not important, but must be unique. 

Within each feed you use the following options:

* *RETRIEVER_CLASS* - The :doc:`Data Retriever <dataretriever>` to use. Currently supported retrievers include:
  
  * *FlickrFeedRetriever* - Retrieves photos using the flickr Feed service
  * *FlickrApIRetriever* - Retrieves photos using the flickr API service. This requires an API key from flickr
  * *PicasaRetriever* - Retrieves photos from the Picassa service.
  
* *TITLE* - The textual label used when showing the album list

----------------------
FlickrFeedRetriever
----------------------

The flickr Feed retriever can retrieve public user photosets and group photos. This feed, however, is limited
to viewing 20 photos (This is a flickr limit). If you would like access to all your photos, then you will need to use 
the  FlickrAPIRetriever with an API key. Use the following options to configure the FlickrFeedRetriever:

* *USER* - The flickr User ID. Use a tool like http://idgettr.com/ to find your user id
* *PHOTOSET* - The photoset id of the album to show. This id can typically be found in the URL address bar when viewing the photoset.
* *GROUP* - The group ID of the group photos to retrieve. This id can typically be found in the URL address bar when viewing the group.

----------------------
FlickrApIRetriever
----------------------

The flickr API retriever can retrieve public user photosets and group photos. It has the same options as the FlickrFeedRetriever,
but requires an API key from flickr. Unlike the FlickrFeedRetriever, it does not limit the number of photos returned.  In addition
to the options found in the FlickrFeedRetriever, it has the follow option:

* *API_KEY* - The API key you received from flickr. See http://www.flickr.com/services/api/misc.api_keys.html for information
  on getting a key.

-------------------------
PicasaRetriever
-------------------------

The Picasa retriever views photos from a public Picassa album. It has the following required options:

* *USER* - The user account associated with the album. This is typically the email address of the owner (user@gmail.com)
* *ALBUM* - The album ID of the album.