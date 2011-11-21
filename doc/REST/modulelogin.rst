############
Login API
############

For Kurogo sites that use authentication, authentication on native apps is done
via a web view that loads the URL for the Mobile Web.  Once the user has gone
through the process of logging in via the web view, they will have session
cookies that enable them to use the rest of the APIs that require 
authentication.  Thus the REST commands ostensibly do not include *login*.

============
Logging in
============

To establish a login session, the client displays a web view and loads

:kbd:`/rest/login/?nativeApp=true`

The *nativeApp* parameter allows the server to display web pages differently
from how they are displayed on the mobile web.


=========
session
=========

Can be used to determine whether the user is currently logged in.

:kbd:`/rest/login/session&v=1`

Sample *response* ::

    {
        "session_id": <token>,
        "token": <token>,
        "user": {
            "name": "John M. Smith",
            "userID": "jmsmith",
            "authority": <authority>,
            "sessiondata": <sessiondata>
        }
    }

* *session_id*
* *token*
* *user*:

  * *name*: Full name of user, if available.
  * *userID*: ID of user, if available.
  * *authority*: a string describing the authority with which this user is
    authenticated.  If the user is not logged in, this value will be null.  A 
    logged-in user may have "Anonymous" as the authority.
  * *sessiondata*: implementation-dependent data for a session.


=======
logout
=======

:kbd:`/rest/login/logout&hard=<is-hard>&v=1`

Parameters:

* *hard* - boolean (0 or 1) value that tells the server whether or not to
  also delete any data stored on the server associated with the current
  session.


Response: not specified.  Any successful (error-free) response should be
interpreted by the client as a successful logout.  The client should then take
any actions to clear out session data locally (e.g. deleting cookies) to be in 
a consistent state with the server.

=============
getuserdata
=============

:kbd:`/rest/login/getuserdata&v=1`

Response is implementation-dependent.




