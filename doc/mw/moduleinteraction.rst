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

=====================================
Formatting Data from Existing Modules 
=====================================

There are many examples where you have data that exists in one data source (an LDAP directory,
a calendaring system) that references other data (map locations, people). Unfortunately there may
not be a strong link between those 2 systems and linking them together requires a bridge. 

There are 2 modules in Kurogo--the People and Calendar modules--that have built in support for 
showing and linking to data in other modules. The *module=xxx* parameter of the detail configuration
creates a link to another module. The default implementation simply uses the same value in the directory
and links to the *search* page of the target module using the value as a *filter* parameter. So a
link to the map module would look like this:

* *map/search?filter=value*

This works in many cases, but sometimes you can provide a more specific link that includes a better
formatted text and a more specific link. The overall steps are as follows:

* Create a subclass of the target module (i.e. map)

    * Create a file named *SiteMapWebModule.php* in *SITE_DIR/app/modules/map*. You may 
      have to create the enclosing folders
    * The file should be a subclass of the MapWebModule. You do not need to include any additional
      properties since this is merely an :ref:`extension <extend-module>` of the map module

* Implement a *linkForValue($value, Module $callingModule, KurogoObject $otherValue=null)* method
  in your subclass. This method will receive:
  
    * a value from the other system
    * a reference to the calling module (so you can determine which module is making the call)
    * optionally the underlying object provided by the module so you can consider all the values when
      generating the response
      
This method should return an array suitable for a :ref:`list item <listitem>`. At very least you
must include a *title* value. Normally you would also include a *url* value unless you do not wish
to include a link.

.. code-block:: php

    <?php
    
    class SiteMapWebModule extends MapWebModule
    {
      public function linkForValue($value, Module $callingModule, KurogoObject $otherValue=null) {
      
          switch ($callingModule->getID()) 
          {
              case 'people':
                //look at the location field to see which office they are from.
                //This assumes the relevant location is in the "location" field.
                $person = $otherValue;
                
                switch ($person->getField('location'))
                {
                    case 'New York':
                        // New York office is first entry
                        return array(
                            'title'=>'New York Office',
                            'url'=>buildURLForModule($this->id, 'detail', array(
                                    'featureindex'=>0,
                                    'category'=>'group:0'
                                ))
                        );
                        break;
                        
                    case 'Boston':
                        // Boston office is the 2nd entry
                        return array(
                            'title'=>'Boston Office',
                            'url'=>buildURLForModule($this->id, 'detail', array(
                                    'featureindex'=>0,
                                    'category'=>'group:1'
                                ))
                        );
                        break;
                    default:
                        // don't include link
                        return array(
                            'title'=>$value
                        );
                }
                break;
              default:
                //return the default implementation for other modules
                return parent::linkForValue($value, $callingModule, $otherValue);
          }
      }
    }



======================================
Enabling interaction from new modules
======================================

If you are writing a new module then there are several methods needed to allow full 
interaction. 

* *searchItems($searchTerms, $limit=null, $options=null)* - This method should return
  an array of objects that conform to the *KurogoObject* interface using the *searchTerms*
  as a filter. Your implementation should call the necessary methods to perform a
  simple search using this criteria. You can also utilize the options array to 
  perform more structured queries. If you utilize the default implementation of the
  federated search method, it will include a *federatedSearch=>true* value in order to
  handle that case in a unique way if you wish. 
* *linkForItem(KurogoObject $object, $options=null)* - This method should return an array
  suitable for a :ref:`list item <listitem>` based on the object included. This would typically be an
  object that is returned from the *searchItems* method. An options array is included
  to permit further customization of the link. For examples, see the People, News,
  Calendar and Video modules.
