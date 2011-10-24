#####################
Creating a new module
#####################

The framework is built to make adding new functionality easy. The goal is to allow you to focus
on creating the logic and design that is unique to your module rather than worry about basic functionality.

This chapter will describe the creation of a simple module and gradually add more features. This module
will parse the data from a video feed using the Google YouTube Web service. 

==========================
Creating the initial files
==========================

In order to ensure that your module does not conflict with current or future modules in the framework,
you will want to create your files in the *SITE_DIR/app/modules/* folder. 

Inside this folder is the module class file as well as a folders for templates, css and javascript.
Each template file is placed in the *templates* named according to which *page* you are on, 
with the default page named *index*. The class file follows the format (ModuleID)WebModule.php. 
This file should be a sublcass of the *WebModule* class and at very least must contain
a property named *id* that indicates the module id and a implementation of *initializeForPage()*

-----
Steps
-----
* Create a folder named *video* in the SITE_DIR/app/modules folder
* Create a templates folder inside the SITE_DIR/app/modules/video folders
* Create *VideoWebModule.php* with the following contents:

.. code-block:: php

    <?php
    
    class VideoWebModule extends WebModule
    {
      protected $id='video';
      protected function initializeForPage() {
      }
    }

* Create *templates/index.tpl* with the following contents:

.. code-block:: html

      {include file="findInclude:common/templates/header.tpl"}
    
      <h1 class="focal">Video</h1>
    
      {include file="findInclude:common/templates/footer.tpl"}

* Create a 56x56 PNG image named *title-video.png* and place it in 
  *SITE_DIR/themes/default/common/images/compliant*. This will be the image that will show up in the
  nav bar for this module

You can now access this module by going to */video* on your server

===========================
Retrieving and Parsing Data
===========================

Now it's time to get some data. Most web services provide their data by making HTTP requests with
certain parameters. We will use the `YouTube Data API <http://code.google.com/apis/youtube/2.0/reference.html>`_ 
as the source of our data. It can return results in a variety of formats, but for simplicity we will
choose JSON. 

-----------------------
Creating a Data Library
-----------------------

We will utilize the *DataController* class to deal with the retrieval, parsing and caching of this data.
Our first step is to create a subclass of DataController and add the appropriate methods to search for
videos based on our topic. It will have a public method called *search* that will accept a string to 
search in YouTube and then return an array of videos based on that query. In your own implementation you
could have a fixed query that returns videos from a specific channel or user. Consult the YouTube API
reference for more information.

Libraries should be placed in the *SITE_DIR/lib* folder. You should create this folder if it does not exist.

-----
Steps
-----

* Create *YouTubeDataController.php* in the SITE_DIR/lib folder with the following contents:

.. code-block:: php
   :linenos:

    <?php
    
    class YouTubeDataController extends DataController
    {
        protected $cacheFolder = "Videos"; // set the cache folder
        protected $DEFAULT_PARSER_CLASS='JSONDataParser'; // the default parser
        
        public function search($q)
        {
            // set the base url to YouTube
            $this->setBaseUrl('http://gdata.youtube.com/feeds/mobile/videos'); 
            $this->addFilter('alt', 'json'); //set the output format to json
            $this->addFilter('q', $q); //set the query 
            $this->addFilter('format', 6); //only return mobile videos
            $this->addFilter('v', 2); // version 2
            
            $data = $this->getParsedData();
            $results = $data['feed']['entry'];
            
            return $results;
        }
            
        // not used yet
        public function getItem($id){}
        
    }  

Some notes on this listing:

* The *cacheFolder* property sets the cache location
* The *DEFAULT_PARSER_CLASS* property sets which parser will be used (it can be overridden by setting the
  *PARSER_CLASS* key when using the factory method.
* The *search* method sets the base URL and adds filters. Filters work as parameters that are added to 
  the url's query string. The *getParsedData* method is called which will retrieve that data (using
  the cache if necessary) and run the data through the parser (a JSON parser in this case). In the
  case of the YouTube feed, the entries are present in the *entry* field of the *feed* field. You
  can use the print_r() or vardump() functions to output the contents of the data to understand its
  structure
* Note that to keep this entry short, we are not utilizing any error control. This should not be 
  considered a robust solution

Now that we have a controller, we can utilize it in our module. Here is an updated *VideoWebModule.php*

.. code-block:: php
   :linenos:

    <?php
    
    class VideoWebModule extends WebModule
    {
      protected $id='video';
      protected function initializeForPage() {
        //instantiate controller
        $controller = DataController::factory('YouTubeDataController');

        switch ($this->page)
        {
           case 'index':
                //search for videos
                $items = $controller->search('mobile web');
                $videos = array();
                
                //prepare the list
                foreach ($items as $video) {
                    $videos[] = array(
                        'title'=>$video['title']['$t'],
                        'img'=>$video['media$group']['media$thumbnail'][0]['url']
                    );
                }
                
                $this->assign('videos', $videos);
                break;
        }
      }
    }

Some notes on this listing:

* We instantiate our controller using the DataController factory method with the name of the class
  as the first parameter. Any options can be specified in an associative array in the second parameter.
* Using a *switch* statement allows us to have different logic depending on which page we are on. We
  can add logic for other pages shortly
* Then we use our search method and search for a fixed phrase. The method returns an array of entries
* We iterate through the array and assign values for each item. We're using the video title for the item 
  title and grabbing a thumbnail to use as our image
* We then assign the videos array to the template

Finally we update the *index.tpl* file to utilize a results list to show the list of videos:

.. code-block:: html

    {include file="findInclude:common/templates/header.tpl"}
    
    {include file="findInclude:common/templates/results.tpl" results=$videos resultsID="videoList" titleTruncate=40}
    
    {include file="findInclude:common/templates/footer.tpl"}
    
* We include the results.tpl file which expects an array of items set in the results variable. We set
  a titleTruncate value to cut off lengthy video titles
* We also set the resultsID variable to assist in styling

You should now be able to view the list of videos by going to */video*. There are two things we will
need to add.

#. Showing the movie details
#. Styling the list to look better

We will address the first item next.
    
===========
Detail Page
===========

Most modules will have more than one page to show content. In this module we will allow the user to 
drill down and see more detail for a video and then play it in the browser. In order to maintain the
breadcrumb navigation properly, we use the *buildBreadcrumbURL($page, $args, $addBreadcrumb)* method
which is part of the WebModule object. This method takes 3 parameters, the page name we wish to link to
(within the same module), and an array of arguments that get passed. The $addBreadcrumb parameter is
a boolean to determine whether breadcrumbs should be generated. The default is true and this is
typically what we want. Adding the url to the list is simple by adding another key to our item
array in *VideoWebModule.php*::

    <?php
    
    //prepare the list
    foreach ($items as $video) {
        $videos[] = array(
            'title'=>$video['title']['$t'],
            'img'=>$video['media$group']['media$thumbnail'][0]['url'],
            'url'=>$this->buildBreadcrumbURL('detail', array(
                'videoid'=>$video['media$group']['yt$videoid']['$t']
                ))
        );
    }

* We simply add a *url* key to our array and use the *buildBreadcrumbURL* method to build an appropriate
  url. We set the page to *detail*. The *args* parameter is set to an array that has one key: *videoid* 
  which we will pass the videoid of our video. We will use that parameter when loading the detail.

-------------------
Retrieving an Entry
-------------------

We will now need to update the *YouTubeDataController* to implement the *getItem($id)* method. This method
is used to retrieve a single item from the collection based on its id. The concept of what makes an 
id is dependent on the context and should be documented to assist others on how to retrieve values. 
It can be any value as long as it is unique. Some systems have the ability to retrieve details on 
specific items. We will use YouTube's API to retrieve a specific item.

Update the *getItem* method in *YouTubeDataController.php* ::

    <?php

    // retrieves a YouTube Video based on its video id    
    public function getItem($id) 
    {
        $this->setBaseUrl("http://gdata.youtube.com/feeds/mobile/videos/$id"); 
        $this->addFilter('alt', 'json'); //set the output format to json
        $this->addFilter('format', 6); //only return mobile videos
        $this->addFilter('v', 2); // version 2
        
        $data = $this->getParsedData();
        return isset($data['entry']) ? $data['entry'] : false;
    }

* We first set the base url to add the video id
* We add the appropriate filters to use the correct API in JSON format
* After gettings the parsed result, we return the *entry* key which contains the details of the video
* You should return FALSE if the entry could not be found
* In a more generic controller, we would return a video object that would abstract all the field details
  and provide an interface to these details. We will leave that exercise to you.

----------------------------------------
Preparing and displaying the detail view
----------------------------------------

Now that we have this method, we can use it in our module. We extract the fields we need and assign
them to our template. We simply add another entry to the our *switch* branch for our *detail* page
in *VideoWebModule.php*::

      <?php
      case 'detail':
         $videoid = $this->getArg('videoid');
         if ($video = $controller->getItem($videoid)) {
            $this->assign('videoid', $videoid);
            $this->assign('videoTitle', $video['title']['$t']);
            $this->assign('videoDescription', $video['media$group']['media$description']['$t']);
         } else {
            $this->redirectTo('index');
         }
         break;

* Use the *getArg()* method to retrieve the *videoid* parameter. It is important in any implementation
  to ensure that you handle cases where this value may not be present.
* You then use the *getItem* method to retrieve an entry for that id. 
* We then assign a few variables to use in our template.
* If the video is not available (i.e. *getItem* returns false), we use the *redirectTo* method to
  redirect to the index page

Now it is time to write our *detail.tpl* template

.. code-block:: html

    {include file="findInclude:common/templates/header.tpl"}
    
    <h1 class="focal videoTitle">{$videoTitle}</h1>
    <p class="nonfocal">
        <iframe class="youtube-player" type="text/html" width="298" height="200" src="http://www.youtube.com/embed/{$videoid}" frameborder="0">
        </iframe>
    </p>
    <p class="focal">{$videoDescription}</p>
    
    {include file="findInclude:common/templates/footer.tpl"}
    
* This template uses simple variable substitution to create a few elements for the title and 
  description. We then use an iframe to `embed the YouTube player <http://apiblog.youtube.com/2010/07/new-way-to-embed-youtube-videos.html>`_
  Keep in mind that some videos will not play on all devices due to difference in encoding methods.


=================
Adding some Style
=================

Although the module already has some formatting due to built in styles, there is some additional
css styling that can be done to improve the look. 

* Create a *css* folder inside the *video* module folder

Create *compliant.css* in the css folder with the following contents:

.. code-block:: css

    #videoList li {
     height: 75px;
     padding: 0 10px 0 0;
     overflow: hidden;
    }

    #videoList a {
      margin-left: 100px;
      padding: 5px 18px 5px 10px; 
      height: 65px;
      line-height: 22px;
    }

    #videoList img {
     height: 75px;
     width: 100px;
     left: -100px;
     top: 0;
    }
    
    .videoTitle {
        font-size: 20px;
        line-height: auto;
    }
    
* We fix the height of the results row to 75 pixels and reset the padding. A 10px padding on the right
  ensures that the arrow is offset appropriately from the right side.
* All of the list item content is wrapped in an anchor tag. We move the margin to the left to make room
  for the image and then reset the padding, and adjusted the height and line-height to accommodate longer
  titles
* The image is fixed to a 75x100 size and moved 100 pixels from the left.
* The video title on the detail page is shrunk to accommodate longer titles

This could be improved further, but with a few simple rules we have made the output look better.

=============
Configuration
=============

Now we will explore some possibilities with using configuration files to add the module to the home
screen, refine the experience and make the module more flexible. 

-----------
Home Screen
-----------

Adding the module to the home screen is simple. You can either use the :ref:`admin-module`
or by editing the *SITE_DIR/config/home/module.ini* file. 

#. In the *[primary_modules]* section, add an entry that says :kbd:`video="Video"`
#. Create a 72x72 PNG image named *video.png* and place it in the *SITE_DIR/themes/default/modules/home/images/compliant*

This will create a link to the video module with a label that says Video. 

------------------
Page configuration
------------------

Each module should have a configuration file that determines the name of each page. These names are 
used in the title and navigation bar. 

Create a file named *pages.ini* in *SITE_DIR/config/video/* with the following contents:

.. code-block:: ini

    [index]
    pageTitle = "Video"
    
    [detail]
    pageTitle = "Detail"

Each section of a page ini file is the name of the page (i.e. the url). It has a series of values (all
are optional)

* *pageTitle* - Used to set the value used in the title tag (uses module name by default)
* *breadcrumbTitle* - Used to set the name of the page in the navigation bar (uses pageTitle by default)
* *breadcrumbLongTitle* - Used to set the name of the page in the footer of basic pages (uses pageTitle by default)

--------------------
Module Configuration
--------------------

The first implementation used a fixed string to search for videos. In order to include a more flexible
solution, you can utilize a configuration parameter to set the string to search. 

Create (or edit) a file named *module.ini* in *SITE_DIR/config/video/* with the following contents:

.. code-block:: ini

    title = "Video"
    disabled = 0
    protected = 0
    search = 0
    secure = 0
    SEARCH_QUERY = "mobile web"
    
The module configuration file contains some fields used by all modules, and also can contain values 
unique to that module. The common values include:

* *title* - The module title. Used in the title bar and other locations
* *disabled* - Whether or not the module is disabled. A disabled module cannot be used by anyone
* *protected* - Protected modules require the user to be logged in. See :doc:`authentication`.
* *search* - Whether or not the module provides search in the federated search feature.
* *secure* - Whether or not the module requires a secure (https) connection. 

You can also add your own values to use in your module. In this case we have added a *SEARCH_QUERY*
parameter that will hold the query to use for the list.

We can now use it in our *VideoWebModule.php* file when we call the search method:

.. code-block:: php

    <?php
    
    //search for videos
    $items = $controller->search($this->getModuleVar('SEARCH_QUERY'));

The method *getModuleVar* will attempt to retrieve a value from the *config/MODULEID/module.ini* file.
You can also use the *getSiteVar* method to retrive a value from *config/site.ini* which is used by
all modules

