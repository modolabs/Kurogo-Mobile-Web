##################
Module Interaction
##################

In many cases it is useful to share data and create links between modules. In order to promote a 
consistent interface, a series of conventions has been established. These conventions deal primarily
with:

* The creation of links and formatting of values to one module based on data values from another 
  (i.e. a link to the map module from values in the people directory)
* The creation of links and formatting of values based on the model object of a module (i.e. the
  formatting and retrieval of calendar events)
* The retrieval of data from another module based on criteria (used in the federated search feature)

If you are writing new modules or want to format data from your various data sources than you should
read this section carefully.


