############
Links API
############

=========
index
=========

:kbd:`/rest/links/index?v=1`

Returns a list of links. Items will be returned as links or as groups that can be retrieved
using the *group* command.

Sample *response* ::

    {
        "displayType": "list", 
        "description": "Other mobile sites in higher education", 
        "description_footer": "",
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
    
---------------
Version History
---------------

* Version 2 - added description_footer property that should appear below the list

========
group
========

:kbd:`/rest/links/group?group=kurogo&v=1`

Retrieve links for a particular

Sample *response* ::

    {
        "displayType": "list", 
        "description": "This is a group of links for Kurogo Resources", 
        "description_footer": "",
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

---------------
Version History
---------------

* Version 2 - added description_footer property that should appear below the list
