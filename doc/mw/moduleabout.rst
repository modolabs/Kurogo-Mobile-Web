############
About Module
############

The about module provides a standardized way to include information about your site and organization
and allow users to contact you.

=============
Configuration
=============

You can configure the values for the menu list as well as the content in the pages.

In the *config/about/page-index.ini* file you can configure the list items that appear in the
module. These values map to the :ref:`listitem` template. 

There are 2 strings defined in the *[strings]* section of the *config/about/module.ini* file. The
*SITE_ABOUT_HTML* value is shown in the *About this website* section. The *ABOUT_HTML* value is
shown in the *About {Organization}* section. Each of those values are represented by arrays. Each
element of the array represents a paragraph of text. 

.. code-block:: ini

    [strings]
    SITE_BLOCK_HTML[] = "This is a paragraph"
    SITE_BLOCK_HTML[] = "This is another paragraph with <i>html</i>"
    
Take care to ensure your HTML is valid markup. This module does not attempt to adjust any HTML
in the configuration.