#################
Video Module
#################

The video module enables sites to provide mobile access to their video content on 3rd party providers 
such as Brightcove and YouTube. 

=================================
Configuring the Sources
=================================

In order to use the video module, you must first select your source to pull videos. Currently, either 
Brightcove or YouTube are the possible choices. Brightcove also requires a read token and a player key
for searching and playing videos.  You can set these values by either using the :ref:`admin-module` or 
by editing the `config/module/video.ini` file directly.

* video_source - Set to 0 for Brightcove or 1 for YouTube.
* xml_or_json - Select XML (RSS) or JSON feeds. Currently only supports json (value=0)

** Brightcove ** Both of the following are accessible from the account console on the Brightcove website:

* brightcoveToken - (required)  Read token to search Brightcove website.  
* playerKey - (required) Key to desired video player.   

** YouTube ** Currently only accesses public feeds so no token/key required.

**Optional values**

=============================
Configuring Feeds
=============================

One can further limit returned videos by creating search categories and setting these in `config/feeds/video.ini`.
The values set there will appear in the search drop-down list:

* *TAG* - This is what is displayed in the categories drop-down list.
* *TAG_CODE* - This is what is actually used in the search query.
