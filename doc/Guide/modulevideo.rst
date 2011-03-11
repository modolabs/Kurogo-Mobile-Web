#################
Video Module
#################

The video module enables sites to provide mobile access to their video content on 3rd party websites
such as Brightcove and YouTube. 

=================================
Configuring the Sources
=================================

In order to use the video module, you must first select your video source. Currently, either 
Brightcove or YouTube are the possible choices. Brightcove also requires a read `token` and a player `key`
for searching and playing videos.  You can set these values by either using the :ref:`admin-module` or 
by editing the `config/module/video.ini` file directly.

* video_source - (required) Set to 0 for Brightcove or 1 for YouTube.

**Brightcove** These required fields for Brightcove are accessible from the admin console on their website:

* brightcoveToken - (required)  Read token to search Brightcove website.  
* playerKey - (required) Key to desired video player.   

**YouTube** Currently we only access public feeds so no token/key required.


=============================
Configuring Feeds/Categories
=============================

One can further limit returned videos by creating search categories and setting these in `config/feeds/video.ini`.
Each category requires this pair of information:

* *TAG* - This is what is displayed in the categories drop-down list.
* *TAG_CODE* - This is what is actually used in the search query.
