#####################
Creating a new module
#####################

The framework is built to make adding new functionality easy. The goal is to allow you to focus
on creating the logic and design that is unique to your module rather than worry about basic functionality.

This chapter will describe the creation of a simple module and gradually add more features. This module
will parse the data from a Twitter feed.

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
* Create a folder named *twitter* in the SITE_DIR/app/modules folder
* Create a folder named *twitter* in the SITE_DIR/config folder
* Create a templates folder inside the SITE_DIR/app/modules/twitter folders
* Create *SITE_DIR/app/modules/twitter/TwitterWebModule.php* with the following contents:

.. code-block:: php
   :linenos:

    <?php
    
    class TwitterWebModule extends WebModule
    {
      protected $id='twitter'
      protected function initializeForPage() {
      }
    }

* Create *SITE_DIR/app/modules/twitter/templates/index.tpl* with the following contents:

.. code-block:: html

      {include file="findInclude:common/templates/header.tpl"}
    
      <h1 class="focal">Twitter</h1>
    
      {include file="findInclude:common/templates/footer.tpl"}

* Create *SITE_DIR/config/twitter/module.ini* with the following contents:

.. code-block:: ini

  [module]
  title="Twitter"
  disabled = 0
  protected = 0
  search = 0
  secure = 0


* Create a 56x56 PNG image named *title-twitter.png* and place it in 
  *SITE_DIR/themes/default/common/images/compliant*. This will be the image that will show up in the
  nav bar for this module

You can now access this module by going to */twitter* on your server. At this point, it has
no useful functionality. These steps can be repeated for any future module you wish to create.

===========================
Retrieving and Parsing Data
===========================

Now it's time to get some data. Most web services provide their data by making HTTP requests with
certain parameters. We will use the `Twitter Data API <https://dev.twitter.com/docs>`_ 
as the source of our data. It returns data in JSON format.

-----------------------
Creating a Data Library
-----------------------

We will utilize the *DataRetriever* class to deal with the retrieval, parsing and caching of this data.
Our first step is to create a subclass of *URLDataRetriever* and add the appropriate methods to return tweets from a particular
users' public timeline. It will have a public method called *tweets* that will accept a username
show in Twitter and then return an array of tweets based on that query.

Libraries should be placed in the *SITE_DIR/lib* folder. You should create this folder if it does not exist.

-----
Steps
-----

* Create *TwitterDataRetriever.php* in the SITE_DIR/lib folder with the following contents:

.. code-block:: php
   :linenos:

    <?php
    
    class TwitterDataRetriever extends URLDataRetriever
    {
        protected $DEFAULT_PARSER_CLASS = 'JSONDataParser';

        public function tweets($user) {
            $this->setBaseURL('http://api.twitter.com/1/statuses/user_timeline.json');
            $this->addParameter('screen_name', $user);
            $data = $this->getData();
            return $data;
        }
    }  

Some notes on this listing:

* The *DEFAULT_PARSER_CLASS* property sets which parser will be used (it can be overridden by setting the
  *PARSER_CLASS* key when using the factory method. See :doc:`dataretriever` for more information.
* The *tweets* method sets the base URL and adds filters. Filters work as parameters that are added to 
  the URL's query string. The *getData* method is called which will retrieve that data (using
  the cache if necessary) and run the data through the parser (a JSON parser in this case). 
* Note that to keep this entry short, we are not utilizing any error control. This should not be 
  considered a robust solution.

Now that we have a retriever, we can utilize it in our module. Here is an updated *TwitterWebModule.php*

.. code-block:: php
   :linenos:

    <?php
    
    class TwitterWebModule extends WebModule
    {
      protected $id='twitter'
      protected function initializeForPage() {

        //instantiate controller
        $controller = DataRetriever::factory('TwitterDataRetriever', array());

        switch ($this->page)
        {
           case 'index':
                $user = 'kurogofwk';                
                
                //get the tweets
                $tweets = $this->controller->tweets($user);

                //prepare the list
                $tweetList = array();
                foreach ($tweets as $tweetData) {
                    $tweet = array(
                        'title'=> $tweetData['text'],
                        'subtitle'=> $tweetData['created_at']
                    );
                    $tweetList[] = $tweet;
                }
                
                //assign the list to the template
                $this->assign('tweetList', $tweetList);
                break;
        }
      }
    }

Some notes on this listing:

* We instantiate our controller using the DataRetriever factory method with the name of the class
  as the first parameter. Any options can be specified in an associative array in the second parameter.
* Using a *switch* statement allows us to have different logic depending on which page we are on. We
  will add logic for other pages shortly
* Then we use our tweets method and send it a string value. The method returns an array of tweets
* *Note:* When debugging the contents of a web service call, it can be useful to output its contents.
  You may find it useful to use the *KurogoDebug::debug($var, $halt=false)* method. The
  first parameter is a variable (typically an array or object), the second parameter is
  a boolean. If true, then script execution will stop. It will also contain a function trace
  to assist in code path debugging. 
* We iterate through the array and assign values for each item. We're using the text value for the item 
  title and the post date as our subtitle. In this example, the value is not formatted, but
  you could use the DateFormatter class to format the value.
* We then assign the tweetList array to the template

Finally we update the *index.tpl* file and utilize a results list to show the list of tweets:

.. code-block:: html

    {include file="findInclude:common/templates/header.tpl"}
    
    {include file="findInclude:common/templates/results.tpl" results=$tweetList}
    
    {include file="findInclude:common/templates/footer.tpl"}
    
* We include the results.tpl file which expects an array of items set in the results variable. 

You should now be able to view the list of tweets by going to */twitter*. 
    
===========
Detail Page
===========

Most modules will have more than one page to show content. In this module we will allow the user to 
drill down and see more detail for a tweet. In order to maintain the
breadcrumb navigation properly, we use the *buildBreadcrumbURL($page, $args, $addBreadcrumb)* method
which is part of the WebModule object. This method takes 3 parameters, the page name we wish to link to
(within the same module), and an array of arguments that get passed. The $addBreadcrumb parameter is
a boolean to determine whether breadcrumbs should be generated. The default is true and this is
typically what we want. Adding the url to the list is simple by adding another key to our item
array in *TwitterWebModule.php*::

    <?php
    
    //prepare the list
    foreach ($tweets as $tweetData) {
        $tweet = array(
            'title'=> $tweetData['text'],
            'subtitle'=> $tweetData['created_at'],
            'url'=> $this->buildBreadcrumbURL('detail', array('id'=>$tweetData['id_str']))
        );
        $tweetList[] = $tweet;
    }

* We simply add a *url* key to our array and use the *buildBreadcrumbURL* method to build an appropriate
  url. We set the page to *detail*. The *args* parameter is set to an array that has one key: *id* 
  which we will pass the id of our tweet. We will use that parameter when loading the detail.

-------------------
Retrieving an Entry
-------------------

We will now need to update the *TwitterDataRetriever* to implement the *getItem($id)* method. This method
is used to retrieve a single item from the collection based on its id. The concept of what makes an 
id is dependent on the context and should be documented to assist others on how to retrieve values. 
It can be any value as long as it is unique. Some systems have the ability to retrieve details on 
specific items. We will use Twitter's API to retrieve a specific item.

Update the *getItem* method in *TwitterDataRetriever.php* ::

    <?php

    // retrieves a tweet based on its id
    public function getItem($id) {
        $this->setBaseURL('http://api.twitter.com/1/statuses/show.json');
        $this->addParameter('id', $id);
        $data = $this->getData();
        return $data;
    }

* We set the base url to the show JSON method
* The getData() method will retrieve the data and return it parsed

----------------------------------------
Preparing and displaying the detail view
----------------------------------------

Now that we have this method, we can use it in our module. We extract the fields we need and assign
them to our template. We simply add another entry to the our *switch* branch for our *detail* page
in *TwitterWebModule.php*::

    <?php
        case 'detail':
            $id = $this->getArg('id');
            if ($tweet = $this->controller->getItem($id)) {
                $this->assign('tweetText', $tweet['text']);
                $this->assign('tweetPost', $tweet['created_at']);
            } else {
                $this->redirectTo('index');
            }
            break;

* Use the *getArg()* method to retrieve the *id* parameter. It is important in any implementation
  to ensure that you handle cases where this value may not be present.
* You then use the *getItem* method to retrieve a tweet for that id. 
* We then assign a few variables to use in our template.
* If the tweet is not available (i.e. *getItem* returns false), we use the *redirectTo* method to
  redirect to the index page

Now it is time to write our *detail.tpl* template

.. code-block:: html

    {include file="findInclude:common/templates/header.tpl"}
    <div class="focal">
    <p>{$tweetText}</p>
    
    <p class="smallprint">{$tweetPost}</p>
    </div>
    {include file="findInclude:common/templates/footer.tpl"}    
    
* This template uses simple variable substitution to create a few elements for the tweet text and 
  post date. 

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

#. In the *[primary_modules]* section, add an entry that says :kbd:`twitter="Twitter"`
#. Create a 72x72 PNG image named *twitter.png* and place it in the *SITE_DIR/themes/default/modules/home/images/compliant*

This will create a link to the twitter module with a label that says Twitter.

------------------
Page configuration
------------------

Each module should have a configuration file that determines the name of each page. These names are 
used in the title and navigation bar. 

Create a file named *pages.ini* in *SITE_DIR/config/twitter/* with the following contents:

.. code-block:: ini

    [index]
    pageTitle = "Twitter"
    
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

The first implementation used a fixed string to search for twitter. In order to include a more flexible
solution, you can utilize a configuration parameter to set the string to search. 

Create (or edit) a file named *module.ini* in *SITE_DIR/config/twitter/* with the following contents:

.. code-block:: ini

    title = "Twitter"
    disabled = 0
    protected = 0
    search = 0
    secure = 0
    TWITTER_USER = "kurogofwk"
    
The module configuration file contains some fields used by all modules, and also can contain values 
unique to that module. The common values include:

* *title* - The module title. Used in the title bar and other locations
* *disabled* - Whether or not the module is disabled. A disabled module cannot be used by anyone
* *protected* - Protected modules require the user to be logged in. See :doc:`authentication`.
* *search* - Whether or not the module provides search in the federated search feature.
* *secure* - Whether or not the module requires a secure (https) connection. 

You can also add your own values to use in your module. In this case we have added a *TWITTER_USER*
parameter that will hold the query to use for the list.

We can now use it in our *TwitterWebModule.php* file when we call the search method:

.. code-block:: php

    <?php

    $user = $this->getModuleVar('TWITTER_USER');
    $items = $controller->tweets($user);

The method *getModuleVar* will attempt to retrieve a value from the *config/MODULEID/module.ini* file.
You can also use the *getSiteVar* method to retrive a value from *config/site.ini* which is used by
all modules

