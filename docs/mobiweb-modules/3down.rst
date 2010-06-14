.. _section-mobiweb-3down:

=====
3DOWN
=====

3DOWN is MIT's dashboard page for the status of various services on campus
(email, network, library, etc.), based on http://3down.mit.edu

The mobile version of 3DOWN provides a drillable list overview of all
tracked services, and phone numbers to call to report a problem.

----------------------------
Data Sources / Configuration
----------------------------

The location of the RSS feed is
http://3down.mit.edu/3down/index.php?rss=1.  This is defined as the
value of the variable ``THREEDOWN_RSS_URL`` in
``mobi-config/mobi_lib_constants.php``.

A sample snippet of the 3DOWN RSS feed follows:

.. code-block:: xml

  <rss version="2.0"> 
    <channel> 
      <title>MIT 3DOWN</title> 
      <link>http://3down.mit.edu</link> 
      <description>Services Status: 3DOWN</description> 
      <language>en-us</language> 
      <pubDate>Sat, 25 Jul 2009 23:33:28 EDT</pubDate> 
 
      <ttl>5</ttl> 
 
      <item> 
        <title> 
          Academic Services 
        </title> 
        <link>http://3down.mit.edu/</link> 
        <description><![CDATA[ 
          <font size="1">All systems are operating normally. </font> 
          ]]> 
        </description> 
 
        <pubDate>Sun, 19 Jul 2009 09:28:58 EDT</pubDate> 
      </item> 
 
      ... 
 
    </channel> 
  </rss>

There are seven <item> tags within the feed, corresponding to the services:

* Academic Services
* Administrative Services
* Email Services
* General Services
* Library Services
* Network Services
* Telephone Services

^^^^^^^^^^^^^^^^^^^^^
mobi-lib dependencies
^^^^^^^^^^^^^^^^^^^^^

* :ref:`subsection-mobiweb-rss-services`

-----------
Logic Files
-----------


^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/3down/index.php
^^^^^^^^^^^^^^^^^^^^^^^^

Main page.  Provides convenience functions to extract needed parts
from each item of the processed RSS feed.

^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/3down/detail.php
^^^^^^^^^^^^^^^^^^^^^^^^^

Detail page.  Extracts the RSS <item> of interest and populates
strings from the item's <description> and <pubDate> to be inserted in
the template for the detail page.




--------------
Template Files
--------------

^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/3down/\*/index.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Displays the current status (contents of the RSS <description> tag)
for each of the services.  Text is truncated at a predefined maximum
length, currently defined as a constant in
``mobi-web/page_builder/page_tools.php``.  Truncated cells provide a
link to full text in the detail screen.

^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
mobi-web/3down/\*/detail.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Displays cleaned-up full text from the RSS item's description and
published date/time.




