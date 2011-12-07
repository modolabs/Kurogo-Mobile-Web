##########
Data Model
##########


The Data Model class is the interface the module uses to access the data. Each module is typically
paired with a Data Model Class (NewsDataModel, PeopleDataModel). This permits the module to 
be written more simply by using easy to understand methods and a common interface regardless of the
mechanism to retrieve the data.

Many modules use a common subclass, the :ref:`ItemListDataModel <itemlistdatamodel>`. This subclass has the ability 
to grab a list of items, manage things like paging and limiting options,
search, and single item retrieval. It is designed to be used with data that presents a collection
of items and allows the user to view a detail of an individual item. 

Developers should need to write any subclass of a Data Model for included modules. If you want
to connect to one of your services, then you will either set the appropriate configuration, or
write a DataRetriever to connect to this service. 

.. _itemlistdatamodel:

=================
ItemListDataModel
=================

This subclass of Data Model has methods to manage a collection of items and retrieve individual
items. It also permits searching within the collection.

--------
Overview
--------

This model will retrieve the values from the retriever and expects to receie an array of
items. The *getItem* method will allow you to retrieve a specific item from the collection.
For retrievers that have specific methods for retrieving a single item they should implements 
the *ItemDataRetriever* interface. Otherwise the model will return the item that matches
the id. If a module has a method for searching collections, then it should implement the
*SearchDataRetriever* interface. If it does, the model will call the search method on the
retriever. Otherwise it will iterate through the collection and return items that match
a *filterEvent*  method. 

-----------
Subclassing
-----------

If you are writing a module that processes a collection of similar items, then creating
a subclass is appropriate. It is not not necessary to create a subclass if you are simply
adding another service to an existing module. You should instead create a new data retriever. 
Refer to the documentation for the module to see details on the values and options sent
to each retriever.


--------------
Public Methods
--------------

These methods can be called by the module to set options and retrieve the data.

* *setStart($start)* - Sets the starting item to retrieve in the collection. This is used by modules to implement paging
  of the collection. The first item in the collection is 0. The 'start' option will be set so it
  can be used by the retriever if the service supports limiting records.
* *setLimit($limit)* - Sets the maximum number of items to return. This is used by modules to implement paging of
  the collection. If this value is not set then all the items in the collection will be returned. The first item 
  in the collection is 0. The 'limit' option will be set so it can be used by the retriever if the service supports limiting records.
* *items()* - Return the collection of items based on the current options
* *search($keyword)* - Performs a search and returns the items that match the search.
* *getItem($id)* - Returns a single item based on its id
* *getTotalItems()* - Returns the total number of items in the collection. Used to implement paging.

================================
Relationship with the Retriever
================================

As part of the initialization process, the Data Model will instantiate a :doc:`Data Retriever <dataretriever>`. 
It will pass the initialization parameters to the retriever. 

-------
Options
-------

The Data model uses a dictionary of options to configure the retriever. The specific options set
are indicated in each module's documentation. Examples include:

* *startDate* - Set by the calendar data model to indicate the start date of events to retrieve
* *author*- Set by the video data model to indicate the author to retrieve
* *limit* - Set by the ItemListDataModel to limit the number of items to return
* *action* - used by several data models to set the mode of retrieval. (i.e. individual item vs. collection vs search)

Refer to the documentation for each module for information on writing retrievers for that particular module.

