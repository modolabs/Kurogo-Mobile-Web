.. _section-mobiweb-api-news:

====
News
====

Overview:

* Get 10 articles at a time in a specified news category.

-------------
API Interface
-------------

Production queries to News use the base URL:
http://m.mit.edu/api/newsoffice

Development queries to News use the base URL:
http://m.mit.edu/api/newsoffice-dev

All parameters are optional.

^^^^^^^^^^^^^^^^
Load 10 Articles
^^^^^^^^^^^^^^^^

Parameters:

* [ **channel**: *channelId* ]
* [ **story_id**: *storyId* ]

The optional *channelId* parameter specifies the category ID of news
articles to return.  There is not yet an API that returns the full
list of category IDs available.  The categories are:

* 1 - Engineering
* 2 - Business
* 3 - Science
* 4 - Architecture and Planning
* 5 - Humanities, Arts, and Social Sciences
* 99 - Campus

If *channelId* is not supplied or 0, All News is assumed.

The optional *storyId* parameter specifies a story ID that the
returned results must not exceed.  Articles in the MIT News feed are
sorted by decreasing storyId.

Sample Response:

.. code-block:: xml

  <?xml version="1.0" encoding="utf-8"?>
  <rss version="2.0">
    <channel>
      <title></title>
      <link>http://web.mit.edu/newsoffice</link>
      <description/>
      <item>
        <title><![CDATA[Haiti’s plight]]></title>
        <author><![CDATA[Peter Dizikes, MIT News Office]]></author>
        <category>6</category>
        <link>http://web.mit.edu/newsoffice/2010/haiti-women-06182010.html</link>
        <story_id>15448</story_id>
        <featured>1</featured>
        <description><![CDATA[MIT anthropologist Erica James examines the psychological damage inflicted on the island nation’s inhabitants.]]></description>
        <postDate>Fri, 18 Jun 2010 04:00:00 EDT </postDate>
        <image>
          <thumbURL>http://web.mit.edu/newsoffice/images/article_images/w76/20100616110001-1.png</thumbURL>
          <smallURL width="140" height="140">http://web.mit.edu/newsoffice/images/article_images/w140/20100616110001-1.jpg</smallURL>
          <fullURL width="368" height="368">http://web.mit.edu/newsoffice/images/article_images/20100616110001-1.jpg</fullURL>
          <imageCredits><![CDATA[Image courtesy of UC Press]]></imageCredits>
          <imageCaption><![CDATA[The cover of Erica James's book, “Democratic Insecurities”]]></imageCaption>
        </image>
        <otherImages>
          <image>
            <fullURL width="200" height="200">http://web.mit.edu/newsoffice/images/article_images/20100616110321-2.jpg</fullURL>
            <imageCaption><![CDATA[Erica James]]></imageCaption>
          </image>
        </otherImages>
        <body><![CDATA[
          The destructive earthquake that hit Haiti in January was only the most recent of the Caribbean nation’s troubles.
          ...
          “In some ways the world I described in the book no longer exists, but in other ways the issue surrounding aid practices are as important as ever,” James says. <br /><br /><br />]]></body>
      </item>

      ...

    </channel>
  </rss>

