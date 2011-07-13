# Kurogo Mobile Web

Kurogo is a PHP framework for delivering high quality, data driven customizable content to a wide
range of mobile devices. Its strengths lie in the customizable system that allows you to adapt
content from a variety of sources and easily present that to mobile devices from feature phones,
to early generation smart phones, to modern devices and tablets. It currently includes modules for:

* People directory
* News/RSS feeds
* Event Calendar
* Maps
* Video
* Emergency
* Links
* Statistics
* About
* HTML Content
* Administration Console

## Online Guide

We strongly recommend developers read the developer's guide:

* [HTML](http://modolabs.com/kurogo/guide)
* [PDF](http://modolabs.com/kurogo/guide.pdf)

Please contact kurogo@modolabs.com for more information.

## Quick Setup and Requirements

Kurogo is a PHP application. It is currently qualified for use with
* Apache 2.x
    * mod_rewrite, and .htaccess support (AllowOverride)
* IIS 7.5
   * URL Rewrite Module 2.0
* PHP 5.2 or higher with the following modules
    * xml, dom, json, pdo (SQLite/MySQL), mbstring, LDAP, curl

To install, simply copy the files to your webserver, and point your site's document root to the included www
folder. For more detailed setup information, please see the developer's guide.

## Version 1.2

This version includes a number of fixes and improvements, including:

* Vastly streamlined default theme with updated theme documentation
* Support for grouping static contacts in the People module
* Support for grouping links in the Links module
* Added established pattern for linking to and receiving data from other modules (See Dev guide for more info)
* Modules can now present dynamic data on the home screen. (See Dev guide for more info)
* Support for different HTTP methods and headers in the DataController class
* Significant documentation improvements and updates.
* The admin module (/admin) can be used on tablets (with certain issues)
* Various bug fixes
* Many other small improvements and fixes outlined in the CHANGELOG

Important note: You MUST delete all your server caches due to underlying changes in the template engine.
Cache files in the minify and smarty folders must be removed.

## History

This project is based off the original [MIT Mobile Framework](https://github.com/MIT-Mobile/MIT-Mobile-Web) and was adapted for use at [Harvard University](https://github.com/modolabs/Harvard-Mobile-Web).
If you have followed the progress of the Harvard project you will notice some improvements and differences:

* Newer modules including video and HTML content
* An overhauled configuration system with web-based administration interface
* A robust authentication system with support for many types of authentication authorities
* Authorization support to restrict access to content by user or group
* Many other small improvements to ease in customization

There are some parts of the project that are not present:

* Modules that have not been generalized are not included. Please refer to the [Harvard Mobile Web Repository](https://github.com/modolabs/Harvard-Mobile-Web) for modules that you do not find here. Some modules will eventually be generalized and folded into this project.
