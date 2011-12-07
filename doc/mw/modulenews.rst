#################
News Module
#################

The news module shows a list of stories/articles from an RSS feed. If the feed provides full textual
content, the article is shown to the user in a mobile friendly format. If the feed does not contain
full text then the module will redirect the browser to the URL of the article.

===============
General Options
===============

There are a few options in *SITE_DIR/config/news/module.ini* that can configure basic operations of
the news module

* *MAX_RESULTS* (10) - The number of items to show in the news list
* *SHARING_ENABLED* (1) - Whether or not to enable the sharing link on news entries. Set to 0 to disable
  the sharing link

======================
Configuring News Feeds
======================

In order to use the news module, you must first setup the connection to your data. There are
2 required values that must be set and a few optional ones. You can set these values by either using
the :ref:`admin-module` or by editing the *SITE_DIR/config/news/feeds.ini* file directly.

The module supports multiple feeds. Each feed is indicated by a section in the configuration
file. The name of the section should be a 0-indexed number. (i.e. the first feed is 0, the second feed
is 1, etc). The following values are required:

* The TITLE value is a label used to name your feed. It will be used in the drop down list to select
  the current feed
* The BASE_URL is set to the url of your News feed. It can be either a static file or a web service. 

**Optional values**

* *RETRIEVER_CLASS* - allows you to set a define an alternate :doc:`data retriever <dataretriever>`
  to get the data. The default is URLDataRetriever.
* *PARSER_CLASS* set this to a subclass of *DataParser*. You would only need to change it if your data
  source returns data in a format other than RSS/Atom or RDF. The default is RSSDataParser.
* *ITEM_CLASS* allows you to set a different class name for each item in the feed. This would allow
  you to handle a feed that has custom fields
* *ENCLOSURE_CLASS* allows you to set a different class name for enclosures. This would allow you
  to handle custom behavior for enclosures, including images, video and audio.
* *SHOW_IMAGES* - Show the image thumbnails for this feed. If an image is not available, it will show
  a placeholder image. If the entire feed does not have images, you may wish to set SHOW_IMAGES=0 
* *SHOW_PUBDATE* - Show the publish date in the news list (the published date is always showed in the detail page)
* *SHOW_AUTHOR* - Show the author in the news list (the author is always showed in the detail page)
* *SHOW_LINK* - Show a link to the full article. This is useful for feeds that only contain an
  introductory paragraph.