#################
Content API
#################

=======
feeds
=======

:kbd:`/rest/content/feeds?v=1`

Sample *response* ::

    {
        "totalFeeds": 1, 
        "feedData": {
            "contentBody": "<h2>Welcome!</h2><p>We're glad you're considering...", 
            "showTitle": "0", 
            "title": "Admissions"
        }, 
        "pages": [
            {
                "url": "http://universitas.modolabs.com/admissions", 
                "subtitle": "", 
                "key": "welcome", 
                "title": "Admissions"
            }
        ]
    }

Contents:

* *totalFeeds*: number of pages of content
* *feedData*: only present if *totalFeeds* is 1.  See *getFeed* API for 
  contents.
* *pages*: a list of the pages that can be further requested for contents.

==========
getFeed
==========

:kbd:`/rest/admissions/getFeed?key=welcome&v=1`

Parameters:

* *key* - key of the page returned from the *feeds* API.

Sample *response* ::

    {
        "feedData": {
            "title": "Admissions",
            "showTitle":"0",
            "contentBody":"...<h2>Welcome!<\/h2>\n<p>We're glad you're considering..."
        }
    }






