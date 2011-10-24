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

* *CONTROLLER_CLASS* - The DataController to use. Currently supported controllers include:
  
  * *YouTubeVideoControler*
  * *VimeoVideoController*
  * *BrightcoveVideoController*
  
* *TITLE* - The textual label used when showing the section list
* *AUTHOR* - optional, used to limit the results by author
* *TAG* - optional, used to limit the results by tag

----------------------
YouTubeVideoController
----------------------

There are additional options for YouTube feeds:

* *PLAYLIST* - optional, used to limit the results by a particular playlist

----------------------
VimeoVideoController
----------------------

There are additional options for Vimeo feeds:

* *CHANNEL* - optional, used to limit the results by a particular channel

-------------------------
BrightcoveVideoController
-------------------------

In order to to use the Brightcove service, you must also include several other parameters. These 
values are available from Brightcove`

* token
* playerKey
* playerId 
