#################
Video Module
#################

The video module enables sites to provide mobile access to their video content on 3rd party websites
such as YouTube, Vimeo and Brightcove

=======================
Configuring the Sources
=======================

The module allows you to organize your videos by section using a distinct feed for each section. Each
section contains information on the service provider and can either filter by tag or author, in addition
to full textual searches. Depending on the source there are other options to configure. Feeds are 
configured in the *SITE_DIR/config/video/feeds.ini* file. Each feed is contained in a section. 
The name of each section is generally not important, but must be unique. 

Within each feed you use the following options:

* *RETRIEVER_CLASS* - The :doc:`Data Retriever <dataretriever>` to use. Currently supported retrievers include:
  
  * *YouTubeRetriever*
  * *VimeoRetriever*
  * *BrightcoveRetriever*
  
* *TITLE* - The textual label used when showing the section list
* *AUTHOR* - optional, used to limit the results by author
* *TAG* - optional, used to limit the results by tag

----------------------
YouTubeRetriever
----------------------

There are additional options for YouTube feeds:

* *PLAYLIST* - optional, used to limit the results by a particular playlist

----------------------
VimeoRetriever
----------------------

There are additional options for Vimeo feeds:

* *CHANNEL* - optional, used to limit the results by a particular channel

-------------------------
BrightcoveRetriever
-------------------------

In order to to use the Brightcove service, you must also include several other parameters. These 
values are available from Brightcove`

* token
* playerKey
* playerId 

===========================
Configuring Display Options
===========================

* *MAX_PANE_RESULTS* - (optional) Defines the maximum number of videos to display when viewing the site's
  home page on a tablet device. Defaults to 5.
* *MAX_RESULTS* - (optional) Defines the maximum number of results when viewing the index page of the video
  module. Defaults to 10.
* *BOOKMARKS_ENABLED* - (optional) If set to true, a link to bookmarked entries will appear. Note that if
  you have not bookmarked any entries, this link will not appear until an entry is bookmarked. Defaults
  to true.
* *SHARING_ENABLED* - (optional) If set to true a link to share the current video will be display on the
  detail page. Defaults to true.