.. Kurogo documentation master file, created by
   sphinx-quickstart on Thu Jan  6 09:09:26 2011.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

Kurogo Developer's Guide
************************

The Kurogo Framework is a PHP based web application that can help institutions efficiently deliver
campus services and information to a wide array of mobile devices. Based on the MIT Framework, this
open source project provides a modular way to present mobile versions of various data sources in an
extendable, customizable fashion.

At a high level, the Kurogo Framework includes:

* A mechanism for detecting device characteristics and displaying content best suited for that device
* A object oriented system for retrieving, parsing and displaying data from a variety of external sources
* A robust templating system that utilizes themeable reusable parts to easily construct consistent user interfaces
* A series of prebuilt, customizable modules for gathering directory, news and event information
* A system of authentication and authorization for controlling access to content and administrative functions

This guide serves as a tour of the project source code and its features.

**Note:** This is documentation for a Beta product. Please be aware that certain details may change before it 
reaches 1.0 status

.. toctree::
   :maxdepth: 2

   overview
   github
   setup
   helloworld
   tour
   devicedetection
   configuration
   requests
   modules
   moduleslist
   template
   themes
   libs
   datacontroller
   modulenew
   moduleextend
   authenticationintro
   glossary   