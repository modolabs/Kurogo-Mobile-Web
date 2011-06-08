#################
Content Module
#################

The content module is a generic module designed to fetch and display freeform content from other sources. 
It can fetch and display content from another HTML site or display an item from an RSS feed. You can
also configure it to display static content configured using the configuration file or administration module.

By default, the content module displays its feeds in a list. Then the user selects a feed (shown using
its title) and the content of the feed is shown. If there is only 1 feed then that feed is shown instead
of the list.

--------
Abstract
--------
The content module cannot be instantiated directly. It is an *abstract* module. In order to create
a module that utilizes its features you should :ref:`copy the module <copy-module>`.

=========================
Configuring Content Feeds
=========================

You can specify any number of pages to show in the *SITE_DIR/config/content/feeds.ini* file. Each
feed is represented by a section, the name of that section represents the "page" of the module. There
are several properties to configure:

* *TITLE* - The title of the feed. This is shown in the list and in the navigation bar
* *CONTENT_TYPE* - the type of content. Values include:

  * *html* - Static html text that is included in the *CONTENT_HTML* property
  * *html_url* - Fetch HTML content from the *BASE_URL* property.  
  * *rss* - Fetch RSS content from the *BASE_URL* property. Will retrieve the content from the first
    item in the feed. Good for CMS's that expose their content via RSS. Ensure that this feed contains
    the full content and not just a link

------------------------
Options for HTML Content
------------------------

There are a few options to handle the extraction of data from an HTML document. In most cases you only
want to include a fragment of the document and strip away things like HTML and HEAD tags and remove 
headers and footers. There are two ways to indicate which content to include:

* *HTML_ID* - Use this option to include only a single element (and its child elements) based on its
  HTML id attribute. This is the simplest, and most recommended option if it is available. The value
  for this option is case sensitive.
* *HTML_TAG* - Use this to include all elements of a certain tag. For instance set it to "table" to 
  include all table elements or "p" to include all paragraph elements. Do **not** include the surrounding
  brackets (<, >)
  
If you do not include either of these options then the entire contents of the body tag will be extracted.