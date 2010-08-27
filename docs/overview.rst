########
Overview
########

***************************************
General structure of the source code
***************************************

* ``README.markdown`` -- README file for Github.
* ``TODO.markdown`` -- TODO file for Github.
* ``docs`` -- source code for this documentation.
* ``iPhone-app`` -- iPhone Xcode project and related files.
* ``mobi-config`` -- configuration files for mobile web.
* ``mobi-lib`` -- backend files for mobile web.
* ``mobi-scripts`` -- scripts to run on the server independently of the mobile web.
* ``mobi-web`` -- mobile web and related files.
* ``opt`` -- installation directory
* ``setup`` -- directory for setup/installation scripts

**********************
Server Requirements
**********************

* Red Hat based server running Apache 2.x
* PHP 5.2+
* MySQL 5.0+

Red Hat package requirements:  
httpd, php >= 5.2, php-gd, php-ldap, php-mbstring, php-mysql, php-xml

PHP short tags MUST be enabled.

****************************
iPhone Project Requirements
****************************

* Mac OS X (Snow Leopard)
* Xcode 3.2.2 or above
* (optional) a provisioned iPhone or iPod Touch running iOS 4.0 or above

************************
Mobile Web Installation
************************

There are several ways developers can set up a local instance of the
Harvard Mobile Web.

==============================
``setup/install-mobiweb.sh``
==============================

This script deploys the mobile web code to a desktop Linux or MAMP system, including MySQL setup.  You must edit the variables ``$PREFIX0``, ``$PREFIX1``, and the ``mysql_`` variables before running the script.

To install, unpack the source code and run:

``cd /path/to/setup``
``vi install-mobiweb.sh``
``sudo ./install-mobiweb.sh``

To uninstall:

``cd /path/to/setup``
``sudo ./uninstall-mobiweb.sh``

To deploy saved code changes:

``cd /path/to/setup``
``sudo ./install-mobiweb.sh --update``

The ``--update`` option copies files to the system, but skips MySQL setup.

=================================
``setup/dev-install-mobiweb.sh``
=================================

This script is designed for MAMP users who wish for saved changes to
be reflected immediately, and do not require proper MySQL
configuration.

To install, unpack the source code to a location with NO SPACES in the
directory name, and run:

``cd /path/to/setup``
``./dev-install-mobiweb.sh``

Point MAMP to the location you unpacked the source code, followed by
``/mobi-web``.

=================================
RPM builds
=================================

On Red Hat systems, ``setup/mitmobile-make-rpm.sh`` will generate a
standard rpm file.  Following installation, MySQL configurations must
be done manually.


