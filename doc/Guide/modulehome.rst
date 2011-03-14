###########
Home Module
###########

The home module represents the main portal to your application. It provides a unified list of modules
available to users. It can be configured to show the list in a variety of styles.

The *SITE_DIR/config/home/module.ini* file contains the standard module configuration, but also has
several other keys for controlling the configuration of the home screen.

----------------
Home Screen Type
----------------

.. code-block:: ini

  display_type = "springboard" 

The display type property is a value that controls whether the home screen displays like a grid of 
icons ("springboard") or a list of items ("list"). 

---------------------
Module list and order
---------------------

There are 2 sections *[primary_modules]* and *[secondary_modules]* that indicate which modules are
shown on the home screen.

Each section has a list of values that represent the order of the modules and their labels. The order
of these values affects the order of the modules. Each value is the format:

.. code-block:: ini

    moduleID = "Label"
    
Primary modules can be rearranged and hidden by the user using the *Customize* module, secondary modules
appear smaller, but cannot be rearranged or removed by the user. Keep in mind that even if the entry is
not on the home screen, users can still manually navigate to the url. So if you have a modules that you
do not wish to use, ensure they have been *disabled* in their module configuration file.


-----
Icons
-----

For compliant browsers, you will need to create icons for each module. These icons should be placed
in: *SITE_DIR/themes/default/modules/home/images/compliant*. Each module should have an 72x72 PNG file 
named the same as its module id (about.png, news.png, etc.)