# Kurogo Mobile Web

Kurogo is a PHP framework for delivering high quality, data driven customizable content to a wide
range of mobile devices. Its strengths lie in the customizable system that allows you to adapt
content from a variety of sources and easily present that to mobile devices from feature phones,
to early generation smart phones, to modern devices and tablets. It currently includes modules for:

* People directory
* News/RSS feeds
* Event Calendar
* Links
* Statistics
* About

## NOTICE: Pre-release version

This project is still in a beta stage with a final release in March 2011. Please be aware that certain
conventions, API and file locations may change. We will strive to provide detailed release notes 
when critical core behavior has been altered.

## History

This project is based off the original MIT Mobile Framework and was adapted for use at Harvard University.
If you have followed the progress of the Harvard project you will notice some improvements and differences:

* An overhauled configuration system with web-based administration interface
* A robust authentication system with support for many types of authentication authorities
* Authorization support to restrict access to content by user or group

There are some parts of the project that are not present

* Modules that have not been generalized are not included. Please refer to https://github.com/modolabs/Harvard-Mobile-Web for modules that you do not find here. Some modules (i.e. Maps) will eventually be put in this project.
* Native API support. The API for native apps will be changing.

The following is planned before the final release:

* A generalized, configurable Map module
* A UI and templates for tablet form factor devices
* A module for retrieving Emergency information
* An abstract module to ease displaying a variety of free form text content from      other sources

## Online Guide

We strongly recommend you read the (in progress) developer's guide at 

* http://modolabs.com/kurogo/guide 
* http://modolabs.com/kurogo/guide.pdf
