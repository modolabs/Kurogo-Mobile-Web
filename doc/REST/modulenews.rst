#################
News API
#################


=============
categories
=============


:kbd:`/rest/news/categories?v=1`

Sample *response* ::

    [
        {
            "id": "0", 
            "title": "Technology Review"
        }, 
        {
            "id": "1", 
            "title": "Harvard Gazette"
        }
    ]

=========
stories
=========


:kbd:`/rest/news/stories?categoryID=<category-id>&mode=full&limit=<limit>&start=<start>&v=1`

Sample *response* ::

    {
        "moreStories": 24, 
        "stories": [
            {
                "body": "<p>RNA molecules have long been known ...</p>\n", 
                "hasBody": true, 
                "description": "A new study reveals the influence of large RNA molecules in controlling stem cells.", 
                "pubDate": 1314590400, 
                "title": "An RNA Switch for Stem Cells", 
                "image": {
                    "src": "http://www.technologyreview.com/files/69375/RNA_x116.jpg", 
                    "height": null, 
                    "width": null
                }, 
                "author": "By Courtney Humphries", 
                "link": "http://www.technologyreview.com/read_article.aspx?id=38448&a=f", 
                "GUID": "http://www.technologyreview.com/read_article.aspx?id=38448&a=f"
            }
            // ...
        ]
    }

======
search
======

:kbd:`/rest/news/search?q=<search-terms>&categoryID=<category-id>&v=1`

Sample *response* ::

    [
        {
            "body": "<p><a href=\"http://www.harvard.edu/\">Harvard University </a>is commemorating its...</p>\n", 
            "hasBody": true, 
            "description": "Harvard University is commemorating its 375th anniversary...", 
            "pubDate": 1314245433, 
            "title": "Harvard\u2019s Mobile Yard Tour app", 
            "image": {
                "src": "http://media.news.harvard.edu/2011/08/phones-for-tour-140.jpg", 
                "height": null, 
                "width": null
            }, 
            "author": "", 
            "link": "http://news.harvard.edu/gazette/story/2011/08/harvards-mobile-yard-tour-app/", 
            "GUID": "http://news.harvard.edu/gazette/?p=88094"
        }
    ]


