############################
Extending an existing module
############################

Sometimes a module exists but doesn't quite provide the behavior or look that you want. As an open
source project, you can freely edit any file you want to alter the behavior, but there are supported
ways to extend or alter a module while still maintaining the ability to cleanly upgrade your project
when new versions come around. 

There are several ways you can alter a module.

* Adjusting a page template file
* Providing alternate logic
* Replacing a module completely

=============================
Altering an existing template
=============================

Overriding a template is a very simple process. You simply provide an alternate template in your site
folder and that file will be loaded instead. 

For example, if you want to extend the *story.tpl* of the news module you would create *story.tpl* 
in *SITE_DIR/app/modules/news/templates*. 

There are two approaches to updating a template. 

* You can completely replace it. This will rewrite the entire template
* You can extend it. If the template provides {blocks} you can use the {extends} tag to replace only
  certain parts of the template

.. _extend-module:  

=======================================
Providing alternative logic (extension)
=======================================

If you want to replace some of the PHP logic you can provide a subclass of the module. This allows 
you to override a method or property. It is important to understand the consequences of the method
you override. In some cases you will want to call the *parent::* method to ensure that the base logic
is executed. An example of this would be the *initializeForPage* or *linkForValue* methods. 
If you wanted to override the people module you would create *SitePeopleModule.php* in 
*SITE_DIR/app/modules/people*::

    <?php 
    
    class SitePeopleWebModule extends PeopleWebModule
    {
        protected function initializeForPage() {
            switch ($this->page)
            {
                case 'index':
                    // insert new logic for index page.....
                    break;
                default:
                    parent::initializeForPage();
            }
        }
    }
    
This would allow you to override the logic for the index page, but keep the other pages the same.
You can include alternate page templates for whatever pages you need to replace.

.. _replace-module:

=============================
Replacing a module completely
=============================

This process is similar to extending the module except that you extend from the *Module* class rather than
the original module. This is useful if you want to have a module that has a URL that is the same as an
existing module. For instance, if you want to write a completely new *about* module you will create
a *AboutModule.php* file in the *SITE_DIR/app/modules/about* folder. It would look like this::

    <?php 
    
    class AboutWebModule extends WebModule
    {
        protected $id='about';
        protected function initializeForPage() {
            // insert logic
        }
    }
    
It is important to include the *$id* property like you would with a :doc:`new module <modulenew>`.

.. _copy-module:

=======================================
Copying a Module 
=======================================

In some cases you may want to have multiple modules that exist under different URLs that share the
same logic, but have different configurations. An example of this would be the :doc:`modulecontent` 
or :doc:`moduleurl`. In this case you simply subclass the parent module and provide a different 
*$configModule* property::

    <?php 
    
    class SomethingWebModule extends ContenWebModule
    {
        protected $configModule = 'something';
    }
    
This module would use the same logic and templates as its parent module, but it would use its
own set of configuration files, in this case in the *SITE_DIR/config/something* folder. Make sure
that the class name prefix matches the configModule value.