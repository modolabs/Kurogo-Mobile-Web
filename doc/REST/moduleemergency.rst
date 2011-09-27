#################
Emergency API
#################

========
notice
========

Returns the latest emergency status/message.

:kbd:`/rest/emergency/notice?v=1`

Sample *response* ::

    {
        "notice": {
            "date": "Thu, 03 Mar 2011 05:04:39 +0000", 
            "text": "There are no emergencies at Universitas at this time.\n", 
            "unixtime": 1299128679, 
            "title": "No Emergency"
        }
    }

========
contacts
========


:kbd:`/rest/emergency/contacts?v=1` 

Sample *response* ::

    {
        "primary": [
            {
                "url": "tel:16175552893", 
                "subtitle": "(617.555.2893)", 
                "title": "Police",
                "type": "phone",
            }, 
            {
                "url": "tel:16175552893", 
                "subtitle": "Cambridge", 
                "title": "Fire Deparment",
                "type": "phone",
            }
            // ...
        ], 
        "secondary": [
            {
                "url": "tel:16175550838", 
                "subtitle": "Hazardous Material", 
                "title": "Hazards",
                "type": "phone",
            }
            // ...
        ]
    }

The contacts are divided into a *primary* and *secondary* group.  Typically
*primary* tends to be a short list displayable on a screen with other 
information, and *secondary* a longer list displayed on its own.  Each contact
in the lists is similar in structure to contacts in the People module's
:ref:`rest-people-contacts` API.



