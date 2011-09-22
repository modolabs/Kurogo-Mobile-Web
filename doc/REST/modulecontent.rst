#################
Content API
#################

=======
pages
=======

:kbd:`/rest/content/pages?v=1`

Sample *response* ::

    {
        "totalFeeds": 1, 
        "pages": [
            {
                "subtitle": "", 
                "key": "welcome", 
                "title": "Admissions",
                "showTitle": true
            }
        ]
    }

Contents:

* *totalFeeds*: number of pages of content
* *pages*: a list of the pages that can be further requested for contents.

  * *title* - title to display in a navigation list.
  * *subtitle* - subtitle to display in a navigation list.
  * *key* - API argument to pass to get the contents of the page in the
    *page* endpoint.
  * *showTitle* - whether or not the title should be shown (e.g. as a table
    header) in addition to the content.

==========
page
==========

The response of *page* is just the HTML content.

:kbd:`/rest/admissions/page?key=welcome&v=1`

Parameters:

* *key* - key of the page returned from the *feeds* API.

Sample *response* ::

    "...<h2>Welcome!<\/h2>\n<p>We're glad you're considering..."



