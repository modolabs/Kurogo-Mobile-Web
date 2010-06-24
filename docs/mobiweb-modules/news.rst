.. _section-mobiweb-news:

================
News Office
================

Displays the most current articles from the MIT News Office.
User can select articles by category (Top News, Campus, Engineering, 
Science, Management, Architecture, and Humanities), or users
can search for articles with search terms.  

----------------------------
Data Sources / Configuration
----------------------------

^^^^^^^^^^^^^^^^^^^^
News Office XML feed
^^^^^^^^^^^^^^^^^^^^

The MIT News Office publishes an XML feed, that allows
retrieval of news articles by category type or search terms.

News article by category are obtained by sending a query similar to
the following::

  http://web.mit.edu/newsoffice/feeds/iphone.php
    ?category=3
    &story_id=15451

The above query sends back XML for up to 200 stories that looks like:

.. code-block:: xml

   <items lastModified='1277305204'>
      <item> 
         <title><![CDATA[Bill Porter in conversation with Howard Anderson]]></title> 
         <author><![CDATA[]]></author> 
         <category>3</category> 
         <link>http://web.mit.edu/newsoffice/2010/mitworld-porter-anderson.html</link> 
         <story_id>15425</story_id> 
         <featured>0</featured> 
         <description><![CDATA[Presented by MIT Sloan School of Management Dean's Innovative Leader Series]]></description> 
         <postDate>Wed, 09 Jun 2010 12:31:31 EDT </postDate> 
         <body><![CDATA[><blockquote>“It’s a case where the entrepreneur wants to 
                build a business, and is  madly in love with a great concept, and 
                the VC wants to make a quick  buck. It’s a partnership, but the 
                guy with control is the guy with  bucks. That’s not a good recipe. ... 
                You have a dichotomy of interests.”<br /> — Bill Porter</blockquote> 
         </body>
       </item>
       <item> 
          <title><![CDATA[MIT Portugal venture competition aims to connect innovators with investors]]></title> 
          <author><![CDATA[]]></author> 
          <category>3</category> 
          <link>http://web.mit.edu/newsoffice/2010/iei-venture-competition.html</link> 
          <story_id>15419</story_id> 
          <featured>0</featured> 
          <description><![CDATA[]]></description> 
          <postDate>Fri, 04 Jun 2010 19:54:51 EDT </postDate> 
          <image> 
             <thumbURL>http://web.mit.edu/newsoffice/images/article_images/w76/20100604160617-1.png</thumbURL> 
             <smallURL width='140' height='140'>http://web.mit.edu/newsoffice/images/article_images/w140/20100604160617-1.jpg</smallURL> 
             <fullURL width='368' height='368'>http://web.mit.edu/newsoffice/images/article_images/20100604160617-1.jpg</fullURL> 
          </image> 
          <body><![CDATA[The ISCTE-IUL MIT Portugal Competition — launched by ISCTE-IUL 
                 in partnership with MIT School of Engineering, the Deshpande Center, 
                 ...
          </body>
       <item> 
       ...
   </items>

If you do not specify ``category`` stories from all categories are returned.  
Only stories older than ``story_id`` are returned, if you
do not include this parameter, the most recent stories are
returned.

Search results are obtained with::

   http://web.mit.edu/newsoffice/index.php
       ?option=com_search
       &view=isearch
       &searchword=quarks
       &ordering=newest
       &start=0
       &limit=3

The above query sends back an XML result that looks like:

.. code-block:: xml

   <items lastModified='1277307134' totalResults='38'> 
      <item> 
         <title><![CDATA[Explained: Quark gluon plasma]]></title> 
         <author><![CDATA[Anne Trafton, MIT News Office]]></author> 
         <link><![CDATA[http://web.mit.edu/newsoffice/2010/exp-quark-gluon-0609.html]]></link> 
         <story_id>15427</story_id> 
         <description><![CDATA[By colliding particles, physicists hope to recreate the earliest moments of our universe, on a much smaller scale.]]></description> 
         <postDate>Wed, 09 Jun 2010 04:00:00 EDT </postDate> 
         <image> 
            <thumbURL>http://web.mit.edu/newsoffice/images/article_images/w76/20100608165022-1.png</thumbURL> 
            <smallURL width='140' height='112'>http://web.mit.edu/newsoffice/images/article_images/w140/20100608165022-1.jpg</smallURL> 
            <fullURL width='368' height='296'>http://web.mit.edu/newsoffice/images/article_images/20100608165022-1.jpg</fullURL> 
         </image> 
         <body><![CDATA[For a few millionths of a second after the Big Bang, 
            ...
         </body>
      </item>
      ...
   </items>

In this XML one can see there are a total of 38 stories matching the query,
but the XML itself will only contain 3 stories because of ``limit`` parameter,
and it starts from the most recent story, because ``start`` is 0.

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-NewsOffice`

-----------
Logic Files
-----------

^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/index.php
^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/channels.php
^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/detail.php
^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/photo.php
^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/story_request_lib.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Functions to construct URLs for news article categories,
search results, or specific story.  The URLs are constructed
from the data in the HTTP Request.

.. function:: newsHomeQuery()

   Returns the query string needed to get back to the most
   recent state of news "home" page.

.. function:: searchQuery()

   Returns the query string needed to get back to the most
   recent state of the news search results page.

The state of the news module is kept in the parameters of the URL,
the state can contain 2 parts, It always contains a "home" state,
which determines how the news "home page" is rendered. It contains
up the following 3 three variables:

* ``channel_id`` -- an id indicating the category to retreive stories from
* ``seek_story_id`` -- by default retreive only stories older than this story id
  , if this variable is NULL retreive the most recent stories.  If
  ``next`` variable is defined and set to 0 only retreive stories newer
  than this story_id
* ``next`` -- whether to retrieve stories older or newer than the ``seek_story_id``

Optionally, the search state can also be set, which contains the following variables:

* ``query`` -- search terms
* ``seek_search_id`` -- The number of stories to seek forward 
  into the search results, if this variable is undefined display the
  first 10 search results

   
--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shows a list stories by category or search results.
The Webkit version uses Javascript to load more
stories in place

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Shows the detail version of the story, and includes
links to see the full size version of the story
photos


^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/\*/photo.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Display the photos from a specific story


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/\*/news.css
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/Webkit/items.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The inner HTML content to display a list of stories.
Javascript dynamically adds this HTML to the page.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/Webkit/news.js
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/Webkit/load_next_ten.js
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

AJAX used to load more stories dynamically.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/Touch/channels.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The Touch phones have a seperate to change
from one category to another.


^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/news/Basic/channel_nav_links.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

All the Basic pages contain navigation links at
the bottom of the page to change from category to
another.
