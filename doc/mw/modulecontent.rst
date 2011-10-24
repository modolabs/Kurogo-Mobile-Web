#################
Content Module
#################

The content module is a generic module designed to fetch and display freeform content from other sources. 
It can fetch and display content from another HTML site or display an item from an RSS feed. You can
also configure it to display static content configured using the configuration file or administration module.

By default, the content module displays its feeds in a list. Then the user selects a feed (shown using
its title) and the content of the feed is shown. If there is only 1 feed then that feed is shown instead
of the list. Optional grouping may be configured.

--------
Abstract
--------
The content module cannot be instantiated directly. It is an *abstract* module. In order to create
a module that utilizes its features you should :ref:`copy the module <copy-module>`.

=========================
Configuring Content Feeds
=========================

You can specify any number of pages to show in the *SITE_DIR/config/MODULE/feeds.ini* file. Each
feed is represented by a section, the name of that section represents the "page" of the module. There
are several properties to configure:

* *TITLE* - The title of the feed. This is shown in the list and in the navigation bar
* *CONTENT_TYPE* - the type of content. Values include:

  * *html* - Static html text that is included in the *CONTENT_HTML* property
  * *html_url* - Fetch HTML content from the *BASE_URL* property.  
  * *rss* - Fetch RSS content from the *BASE_URL* property. Will retrieve the content from the first
    item in the feed. Good for CMS's that expose their content via RSS. Ensure that this feed contains
    the full content and not just a link

==========================
Creating Groups of Content
==========================

* NOTE - Creation of content groups is not supported in the admin console at this time.

You can create groups of content in order to organize similar content into categories. Creating content
groups involves the following steps:

#. If it does not exist, create a file named *SITE_DIR/config/MODULE/feedgroups.ini*
#. Add a section to feedgroups.ini with a short name of your group. This should be a lowercase
   alpha numeric value without spaces or special characters.
#. This section should contain a *TITLE* option that represents the title of the group. Optionally
   you can include a *DESCRIPTION* value that will show at the top of the content list for the group.
#. Create a file named *SITE_DIR/config/MODULE/feeds-groupname.ini* where *groupname* is the short name
   of the group you created in *feedgroups.ini*. This file should be formatted like feeds.ini with
   each entry being a uniquely indexed section.
#. To use this group, assign it to a entry in *feeds.ini*. Add a value *GROUP* with a value of the
   short name of the group. You can optionally add a *TITLE* that will be used instead of the group title
   indicated in *feedgroups.ini*

The *feeds.ini* file may contain both groups and content entries. They will be displayed in the order the
sections appear in the *feeds.ini* file. If only one group is added, that group will be displayed. If only
one content entry exists in either a group or in *feeds.ini* it will be displayed.

This is an example *SITE_DIR/config/MODULE/feedgroups.ini*. Each group is a section that contains title
(and optional description). You can have any number of groups:

.. code-block:: ini

  [applying]
  TITLE = "Applying to Universitas"
  DESCRIPTION = ""

  [visiting]
  TITLE = "Visiting"
  DESCRIPTION = ""

*SITE_DIR/config/MODULE/feeds-applying.ini*. This is an example file for the *applying* group. It is
formatted like the *feeds.ini* file:

.. code-block:: ini

  [admissions]
  TITLE = "Admissions"
  SUBTITLE = ""
  SHOW_TITLE = 0
  CONTENT_TYPE = "html_url"
  BASE_URL = "http://universitas.modolabs.com/admissions"
  HTML_ID = "node-2"

*SITE_DIR/config/MODULE/feeds.ini*. Include a *group* value to show a group:

.. code-block:: ini

  [applying]
  TITLE = "Applying to Universitas"
  GROUP = "applying"

  [visiting]
  TITLE = "Visiting"
  GROUP = "visiting"

  [othercontent]
  TITLE = "Other Content"
  SUBTITLE = ""
  SHOW_TITLE = 0
  CONTENT_TYPE = "html_url"
  BASE_URL = "http://www.example.com/othercontent"
  HTML_ID = "html-id"

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