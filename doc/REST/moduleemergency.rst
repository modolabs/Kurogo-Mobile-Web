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
                "dialablePhone": "16175552893", 
                "subtitle": null, 
                "formattedPhone": "617.555.2893", 
                "title": "Police"
            }, 
            {
                "dialablePhone": "16175552893", 
                "subtitle": "Cambridge", 
                "formattedPhone": "617.555.2893", 
                "title": "Fire Deparment"
            }
            // ...
        ], 
        "secondary": [
            {
                "dialablePhone": "16175550838", 
                "subtitle": "Hazardous Material", 
                "formattedPhone": "617.555.0838", 
                "title": "Hazards"
            }
            // ...
        ]
    }


