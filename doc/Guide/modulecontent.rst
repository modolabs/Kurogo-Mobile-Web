#################
Content Module
#################

The content module is a generic module designed to fetch and display freeform content from other sources. 
It can fetch and display content from another HTML site or display an item from an RSS feed. You can
also configure it to display static content configured using the configuration file or administration module.

By default, the content module displays its feeds in a list. Then the user selects a feed (shown using
its title) and the content of the feed is shown. If there is only 1 feed then that feed is shown instead
of the list.

=========================
Configuring Content Feeds
=========================

You can specify any number of pages to show in the *SITE_DIR/config/content/feeds.ini* file. Each
feed is represented by a section, the name of that section represents the "page" of the module. There
are several properties to configure:

* *TITLE* - The title of the feed. This is shown in the list and in the navigation bar
* *CONTENT_TYPE* - the type of content. Values include:

  * *html* - Static html text that is included in the *CONTENT_HTML* property
  * *html_url* - Fetch HTML content from the *BASE_URL* property. Set the *HTML_ID* property to indicate
    which HTML element to retrieve (good for extracting out content from navigation/headers/footers). If
    HTML_ID is not specified then the <body> tag will be returned. 
  * *rss* - Fetch RSS content from the *BASE_URL* property. Will retrieve the content from the first
    item in the feed. Good for CMS's that expose their content via RSS. Ensure that this feed contains
    the full content and not just a link
