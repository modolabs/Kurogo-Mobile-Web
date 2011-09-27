############
About API
############

========
index
========

Returns a list of links/commands.  When a *command* is returned, it can be used
to retrieve a page of contents.

:kbd:`/rest/about/index?v=1`

Sample *response* ::

    [
        {
            "command": "about_site", 
            "title": "About this website"
        }, 
        {
            "command": "about", 
            "title": "About Universitas"
        }, 
        {
            "type": "email", 
            "email": "kurogo@modolabs.com", 
            "title": "Send us feedback!"
        }, 
        {
            "command": "credits", 
            "title": "Credits"
        }
    ]

If there is a *command* parameter associated the list item, the client can 
expect the full body of this item from the <command> endpoint as described 
below.  Otherwise the list item should be treated following Kurogo
:ref:`rest-api-conventions`.


==========
<command>
==========

The response for each <command> returned from the <index> command is raw HTML.

:kbd:`/rest/about/<command>?v=1`

Sample *response* ::

    "<p>The Kurogo Framework is the living heart of our Mobile Solutions and 
    was developed as part of iMobileU, a community of education institutions 
    promoting open source mobile solutions. Alongside institutions such as MIT 
    and Harvard, we're proud to be a part of this dynamic and forward-thinking 
    collective.</p>"

