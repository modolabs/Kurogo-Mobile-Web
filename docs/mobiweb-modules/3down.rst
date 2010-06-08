=====
3DOWN
=====

lib/trunk/rss_services provides the class ThreeDown, an extension of
the class RSS whose data source is
http://3down.mit.edu/3down/index.php?rss=1.

A sample snippet of the 3DOWN XML follows::

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

The index.php page displays the current status (the <description> tag)
for each of these services. If the text of the description is longer
than a predefined length (currently 80 characters), the text is
trunctaed and a link is shown for the full text. The full text is
displayed in detail.php.
