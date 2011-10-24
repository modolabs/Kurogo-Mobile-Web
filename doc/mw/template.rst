#########
Templates
#########

In addition to the logic parts of the module, pages use templates to output the content to the browser.

The framework utilizes the `Smarty template engine <http://www.smarty.net/>`_. This engine has a variety
of features including template inheritance, inclusions, looping constructs, and variables. The framework
wraps around the Smarty engine using it to display the HTML. Smarty uses a variety of special tags 
enclosed in braces {} to handle variables, functions and control structures. The tags most often
used in the framework are explained below. For a quick reference see the 
`Smarty Crash Course <http://www.smarty.net/crash_course>`_.

As part of the display process, the template engine is initialized, and a template file is displayed 
based on the current page. The engine will search the module folder for a template file with
the name name as the current page based on the rules of :ref:`pageandplatform`.

=======================
Variables and Modifiers
=======================

Including values from variables is a two step process:

#. In your module PHP file, call *$this->assign(string $var, mixed $value)* to assign a value to a 
   template variable. :kbd:`$this->assign('thing', 42)` will assign the value 42 to the template 
   variable "thing"
#. In your template you can refer to this variable as *{$thing}*

You can also use `modifiers <http://www.smarty.net/docs/en/language.modifiers.tpl>`_ to alter the 
presentation of a value. 

=================================
Including and Extending Templates
=================================

Templates can include other templates. This allows the creation of reusable blocks for common fragments
of HTML. The framework heavily utilizes this feature. 

To include a template use the {include} tag. Use it in the framework like this::

  {include file="findInclude:template.tpl"}

Using the *findInclude* ensures that if there are multiple versions of the template, the proper one
will be used based on the pagetype and platform. 

You can also assign variables when including the template::

  {include file="findInclude:template.tpl" thing=42}
  
This will assign the value 42 to the variable thing

------
Blocks
------

When designing templates for multiple device types, often the case is that you only need to change
a certain part of the template and leave the rest as is. Smarty has this capability to *extend* templates
replacing only what's needed. It uses 2 tags, {block} and {extend} to provide this feature

Consider a base template template.tpl::

  This template is the base template. 
  
  {block name="content1"}
  This is some content
  {/block}

  {block name="content2"}
  This is some more content
  {/block}

Notice that the {block} tag has an opening and closing part. We can use the {extends} tag on the 
specific types. For a template that extends another, you simply provide alternate content for whatever
blocks you wish to replace. If you wish to eliminate a block, simply include a blank block pair. If
you do not specific a block, it will be included as is.

An example for template-compliant.tpl::

    {extends file="findExtends:template.tpl"}

    {block name="content1"}
      This content will be shown to compliant browsers
    {/block}
    

In this case, the *content1* block will have alternate content and the *content2* block will be displayed as is.

An example for template-basic.tpl::

    {extends file="findExtends:template.tpl"}

    {block name="content2"}{/block}

In this case, the *content2* block will not be shown at all and the *content1* block will be displayed as is.

This technique will permit you to create layered content that has exceptions or alternative versions for 
different device types. Keep in mind that in child templates, you can only define content inside {block}s,
any content outside the blocks will be ignore. See the included module templates for more examples and
the section on `template inheritance <http://www.smarty.net/docs/en/advanced.features.template.inheritance.tpl>`_ 
in the Smarty Documentation.

==================
Control Structures
==================

You can include some basic logic inside your templates to affect flow and conditionally present content.
Most of these structures utilize syntax that is identical to the corresponding PHP structures.

---------
{foreach}
---------

Iterates through an array::

  {foreach $arr as $key=>$value}
    {$key} = {$value}
  {foreach}
  

See more in the `Smarty Documentation <http://www.smarty.net/docs/en/language.function.foreach.tpl>`_

-------------
{if} / {else}
-------------

Conditionally displays content::

  {if $test}
  This will be displayed if test is true
  {/if}

Smarty uses the same conventions as PHP to determine the truth value of an expression. 
See more in the `Smarty Documentation <http://www.smarty.net/docs/en/language.function.foreach.tpl>`_

.. _templates:

===========================
Standard Template Fragments
===========================

There are a variety of template fragments included that will allow you to include common interface
elements in your own templates. 

-----------------------
header.tpl / footer.tpl
-----------------------

The header and footer files should generally appear at the top and bottom respectively of your main
template files. This ensures that the site navigation and other wrapper content::

  {include file="findInclude:common/templates/header.tpl" scalable=false}
  
  Content goes here....
  
  {include file="findInclude:common/templates/footer.tpl"}
  
-----------
navlist.tpl
-----------

One of the most important elements is the navigation list. It renders an HTML list based on an array
of list items. This list is formatted appropriately for the device. 

There are several variables you can pass to affect how it is displayed:

* *navlistID* this will assign the value to the id of the list. This would allow custom CSS rules to
  be applied to this list
* *navlistItems* an array of list items (each of which is an array). See *listitem* for a list of keys each list item should have
* *secondary* adds the *secondary* class to the navlist

.. _listitem:

------------
listitem.tpl
------------

Used by the navlist template for each list item. When passing the values to the navlist each item in the
array is a list item. Each item should be an array. There are a variety of keys used for each item:

* *title* - The text shown on the list item

Optional keys

* *label* - A textual label for the item
* *boldLabels* - if true, the label will be bolded
* *labelColon* - If false, the colon following the label will be suppressed
* *url* - A url that this item links to when clicked/tapped.
* *img* - A url to an image icon that is displayed next to the item
* *imgWidth* - The width of the image 
* *imgHeight* - The height of the image 
* *imgAlt* - The alt text for the image
* *class* - CSS class for the item
* *subtitle* - Subtitle for the item
* *badge* - Content (typically numerical) that will appear in a badge