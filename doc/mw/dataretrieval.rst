##############
Data Retrieval
##############

Kurogo's foundation is built on the concept of accessing remote data. Nearly every module connects
to remote servers, using a variety of protocols, and retrieves this data, and normalizes it 
in a format that the module can use. In Kurogo versions 1.3 and earlier, this was typically
accomplished using the DataController Class. In version 1.4, the system
has been refactored and improved to introduce more flexibility for developers and more easily
permit additional protocols beyond simple HTTP to access the remote data. This includes SOAP and Database
access in addition to more flexible HTTP access. It also permits more complicated data modeling
scenarios to deal with situations where the necessary data cannot be retrieved in a single
request.

.. toctree::
   :maxdepth: 1

   datamodel
   dataretriever
   dataparser   

