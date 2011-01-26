######################################
Making Your First Module - Hello World
######################################

This section will give you an overview of the module creation process. It is meant to be followed 
along, but most of the in-depth technical knowledge can be found in :doc:`modulenew`. Most of the 
content in this section is elaborated in more depth elsewhere.

------------------------------------
Creating the module folder structure
------------------------------------

The Kurogo Framework looks in a variety of locations for module data (see :doc:`tour`). In most cases
you will want to create your module in your site's *modules* folder. 

Create a folder named *hello* inside the *modules* folder of your *SITE_FOLDER*. Note: if the modules folder
does not exist, you will need to create it.

------------------------------
Creating the module class file
------------------------------
   
Inside the *hello* directory create a file named *SiteHelloModule.php* that contains the following contents::

    <?php
    
    class SiteHelloModule extends Module
    {
      protected $id='hello';
      protected function initializeForPage() {
        $this->assign('message', 'Hello World!');
      }
    }
    
--------------------------
Creating the template file
--------------------------

Inside the *hello* directory create a file named *index.tpl* that contains the following contents:


.. code-block:: html

      {include file="findInclude:common/header.tpl"}
    
      <h1 class="focal">Hello World!</h1>
    
      {include file="findInclude:common/footer.tpl"}

Your folder structure should look similar to this:

.. image:: images/helloworld_files.png

--------------------------
Creating the nav bar image
--------------------------

Create a 56 x 56 PNG file named *title-hello.png* and place it in *SITE_FOLDER/themes/default/common/images/compliant*.

------------------
Viewing the module
------------------

At this time you should be able to view your new module in your web browser. Assuming your site is on port 8888
on your local machine go to :kbd:`http://localhost:8888`. If successful you should see your new module:

.. image:: images/helloworld_view.png

Congratulations! You've just built a simple module.

.. seealso::

  :doc:`modulenew`
    Detailed explanation of creating a new module.

  :doc:`moduleextend`
    Detailed explanation of extending an existing module
