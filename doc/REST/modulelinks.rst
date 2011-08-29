############
Links API
############

=========
index
=========

:kbd:`/rest/links/index?v=1`

Sample *response* ::

    {
        "displayType": "list", 
        "description": "Other mobile sites in higher education", 
        "links": [
            {
                "url": "http://m.ucf.edu/", 
                "icon": "", 
                "subtitle": "http://m.ucf.edu/", 
                "title": "UCF Mobile"
            }, 
            {
                "group": "kurogo", 
                "title": "Kurogo Links"
            }
            // ...
        ]
    }

========
group
========

:kbd:`/rest/links/group?group=kurogo&v=1`

Sample *response* ::

    {
        "displayType": "list", 
        "description": "This is a group of links for Kurogo Resources", 
        "links": [
            {
                "url": "http://groups.google.com/group/kurogo-dev", 
                "subtitle": "Developer's mailing list", 
                "title": "Kurogo-dev Google Group"
            }, 
            {
                "url": "http://modolabs.com/kurogo/guide", 
                "title": "Kurogo Developer's Guide"
            }
            // ...
        ]
    }







