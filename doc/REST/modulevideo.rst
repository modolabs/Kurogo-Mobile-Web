#################
Video API
#################

==========
sections
==========

:kbd:`/rest/video/sections?v=1`

Sample *response* ::

    [
        {
            "value": "ted", 
            "title": "TED Talks"
        }, 
        {
            "value": "vimeo", 
            "title": "Vimeo Staff Picks"
        }
    ]

========
videos
========

:kbd:`/rest/video/videos?section=<section-id>&v=1`

Sample *response* ::

    [
        {
            "description": "Using three iPods...", 
            "author": "TEDtalksDirector", 
            "url": "http://www.youtube.com/watch?v=fumsXEuiLyk&feature=youtube_gdata_player", 
            "image": "http://i.ytimg.com/vi/fumsXEuiLyk/default.jpg", 
            "title": "Marco Tempest: The magic of truth and lies (and iPods)", 
            "height": null, 
            "width": null, 
            "tags": [
                "TED", 
                "tedtalks", 
                "tedglobal", 
                "Marco Tempest", 
                "truth", 
                "lies", 
                "ipods", 
                "Art", 
                "Arts", 
                "Design", 
                "Entertainment", 
                "Illusion", 
                "Magic", 
                "Music", 
                "Technology", 
                "politics", 
                "emotion", 
                "deception", 
                "magicians", 
                "clever"
            ], 
            "mobileURL": "rtsp://v7.cache8.c.youtube.com/CiILENy73wIaGQkpL6JLXKzpfhMYDSANFEgGUgZ2aWRlb3MM/0/0/0/video.3gp", 
            "published": {
                "date": "2011-08-12 19:40:06", 
                "timezone_type": 2, 
                "timezone": "Z"
            }, 
            "date": "Aug 8, 2011", 
            "streamingURL": "rtsp://v3.cache8.c.youtube.com/CiILENy73wIaGQkpL6JLXKzpfhMYESARFEgGUgZ2aWRlb3MM/0/0/0/video.3gp", 
            "id": "fumsXEuiLyk", 
            "duration": 307, 
            "stillFrameImage": "http://i.ytimg.com/vi/fumsXEuiLyk/hqdefault.jpg"
        }
        // ...
    ]

======
detail
======

:kbd:`/rest/video/detail?section=<section-id>&videoid=<video-id>&v=1`

Sample *response* ::

    {
        "description": "http://www.ted.com Here's a crazy idea: Persuade the world to try living in peace for just one day...", 
        "author": "TEDtalksDirector", 
        "url": "http://www.youtube.com/watch?v=04SEzifEsGg&feature=youtube_gdata_player", 
        "image": "http://i.ytimg.com/vi/04SEzifEsGg/default.jpg", 
        "title": "Jeremy Gilley: One day of peace", 
        "height": null, 
        "width": null, 
        "tags": [
            "TED", 
            "TEDTalks", 
            "TEDGlobal", 
            "Jeremy Gilley", 
            "Peace One Day", 
            "Activism", 
            "Collaboration", 
            "Global", 
            "Issues", 
            "Peace", 
            "War", 
            "September 21"
        ], 
        "mobileURL": "rtsp://v5.cache8.c.youtube.com/CiILENy73wIaGQlosMQnzoSE0xMYDSANFEgGUgZ2aWRlb3MM/0/0/0/video.3gp", 
        "published": {
            "date": "2011-08-10 15:29:01", 
            "timezone_type": 2, 
            "timezone": "Z"
        }, 
        "date": "Aug 8, 2011", 
        "streamingURL": "rtsp://v6.cache8.c.youtube.com/CiILENy73wIaGQlosMQnzoSE0xMYESARFEgGUgZ2aWRlb3MM/0/0/0/video.3gp", 
        "id": "04SEzifEsGg", 
        "duration": 1062, 
        "stillFrameImage": "http://i.ytimg.com/vi/04SEzifEsGg/hqdefault.jpg"
    }

