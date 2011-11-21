#################
People API
#################

The People API provides an interface to search the people directory and 
retrieve static lists of contacts important to your organization.

The examples in this section assume the module id is *people*.  If your 
installation has the People module configured under a different URL location, 
substitute as necessary.

=======
search
=======


Returns a list of people matching a user-entered query string.  All fields are 
returned for each search result, so there is no separate "detail" endpoint.

:kbd:`/rest/people/search?q=<query>&v=1`

Parameters

* *q* - the query string entered by the user in a search box.

Sample *response* ::

    {
        "total": 8,
        "returned": 8,
        "displayField": "name",
        "results": [
            {
                "name": "John Smith", 
                "firstName": "John", 
                "lastName": "Smith", 
                "organizations": [
                    {
                        "type": "organization", 
                        "value": {
                            "jobTitle": "Our Favorite Person", 
                            "organization": "Department of Fake People"
                        }, 
                        "title": "work"
                    }
                ], 
                "contacts": [
                    {
                        "type": "email", 
                        "value": "john.smith@example.com", 
                        "title": "email"
                    }, 
                    {
                        "type": "phone", 
                        "value": "+1-617-555-1234", 
                        "title": "phone"
                    }, 
                    {
                        "type": "address", 
                        "value": {
                            "display": "5678 Massachusetts Ave, Cambridge, MA 02140"
                        }, 
                        "title": "home"
                    }
                ]
            }
            // ...
        ]
    }

The structure of each entry in *results* is intended to mimic fields in a 
typical smartphone address book.

* *total* is the total number of matching results.
* *returned* is the total number of results returned in this response.
* *results* is an array of search results, which contain:
  *name* - the person's display name
  *firstName* - the person's first name
  *lastName* - the person's last name
  *organizations* - an array of the person's organization affiliations.
  *contacts* - an array of methods to contact the person
* *displayField* is the name of the field

Entries in *organizations* and *contacts* each have the following fields:

* *value* - the information to be conveyed
* *title* - a hint to the user about what this value is about
* *type* - an indicator of how the field should be treated (e.g. "email" 
  means the user should be able to send an email to the address displayed,
  "phone" means the user should be able to call the number displayed,
  "address" means the user should be able to find the displayed address on a 
  map.)

.. _rest-people-contacts:

==========
contacts
==========

Returns a list of static contacts for displaying on the People module home 
screen.

:kbd:`/rest/people/contacts?v=1`

Sample *response* ::

    {
        "displayField": "title", 
        "total": 2, 
        "returned": 2, 
        "results": [
            {
                "subtitle": "(617-555-0001)",
                "url": "tel:6175550001",
                "title": "Static Entry 1",
                "type": "phone"
            }, 
            {
                "subtitle": "(617-555-0002)",
                "url": "tel:6175550002",
                "title": "Static Entry 2",
                "type": "phone"
            },
            {
                "title": "Contact Group 1",
                "group": "group1"
            }
            // ...
        ]
    }

Contents:

* *total* - total number of static contacts.
* *displayField* - number of results returned.
* *results* - an array of entries with the following fields:

  * *identifier* - unique identifier of the entry
  * *type* - an indicator of how the field should be treated. In the above
    example, both contacts are of type "phone", which means the user should 
    be able to place a phone call with the information provided.

  * *title* - short description of this contact
  * *url* - the contact's URL, if any.

  * *group* - indicates that this is not an individual contact but a group
    of contacts. The client must use the *group* endpoint below to fetch
    the individual contact objects in this group.

========
group
========

:kbd:`/rest/people/group?group=group1&v=1`

Sample *response* ::

    {
        "total": 3, 
        "results": {
            "contacts": [
                {
                    "url": "tel:6175550003", 
                    "subtitle": "(617-555-0003)", 
                    "class": "phone", 
                    "title": "Static Entry 4"
                }, 
                {
                    "url": "tel:6175550004", 
                    "subtitle": "(617-555-0004)", 
                    "class": "phone", 
                    "title": "Static Entry 5"
                },
                // ...
            ], 
            "description": "This is a group of contacts", 
            "title": "Group 1"
        }
    }

Each entry in "contacts" has the same structure as the entries in the *contacts*
API.


