################
Style and Themes
################

The Kurogo Framework has a theming layer which allows sites to make most stylistic changes to the 
web application without modifying the core libraries.  The advantage of using the theming layer is 
that site changes are isolated from the framework sources and can be more easily moved to a new 
version of the framework.

The core visual interface of the Modo Mobile Framework lives in "templates/".  It is made up of 
HTML templates, CSS and Javascript files.  All HTML, CSS and Javascript in the core interface can be 
overridden by a theme.  

Each theme is contained within a directory inside the *SITE_DIR/themes* folder.  By convention the default 
theme is named *default*.

Themes have the same directory structure as the core visual interface directory (templates/).  
This allows paths in the CSS and HTML to be the same for the core interface and the theme interface. 

******************
CSS and Javascript
******************

All CSS and Javascript files are loaded automatically using Minify.  Rather than having to specify 
each CSS and Javascript file per page, Minify locates the files based on their names.  The naming 
scheme is similar to that of the templates, except there is a special file name "common" which 
indicates the file should be included for all devices:

-----------------
CSS Search Paths:
-----------------

CSS search paths from least specific to most specific.  All matching CSS files are concatenated 
together from least specific to most specific.  This allows you to override styles for specific 
pages or devices.

Check common core files in */templates/common/css/* for:

* common.css
* [PAGETYPE].css
* [PAGETYPE]-[PLATFORM].css
* [PAGE]-common.css
* [PAGE]-[PAGETYPE].css
* [PAGE]-[PAGETYPE]-[PLATFORM].css
  
Check module core files in */templates/modules/[current module]/css/* for:

* common.css
* [PAGETYPE].css
* [PAGETYPE]-[PLATFORM].css
* [PAGE]-common.css
* [PAGE]-[PAGETYPE].css
* [PAGE]-[PAGETYPE]-[PLATFORM].css

Check common theme files in *SITE_DIR/themes/[ACTIVE_THEME]/common/css*/ for:

* common.css
* [PAGETYPE].css
* [PAGETYPE]-[PLATFORM].css
* [PAGE]-common.css
* [PAGE]-[PAGETYPE].css
* [PAGE]-[PAGETYPE]-[PLATFORM].css

Check module theme files in *SITE_DIR/themes/[ACTIVE_THEME]/modules/[current module]/css/* for:

* common.css
* [PAGETYPE].css
* [PAGETYPE]-[PLATFORM].css
* [PAGE]-common.css
* [PAGE]-[PAGETYPE].css
* [PAGE]-[PAGETYPE]-[PLATFORM].css


------------------------
Javascript Search Paths:
------------------------

Because Javascript does not allow overriding of functions, only the most device specific file in 
each directory is included, and theme files completely override core files.  When overriding be aware 
that you may need to duplicate code or move it into a common file to get it included on multiple 
pagetypes or platforms.

Check common theme files in *SITE_DIR/themes/[ACTIVE_THEME]/common/javascript/* for:

* common.js
* [PAGETYPE]-[PLATFORM].js or if not check [PAGETYPE].js
* [PAGE]-common.js
* [PAGE]-[PAGETYPE]-[PLATFORM].js or if not check [PAGE]-[PAGETYPE].js

If there are no common theme files, check common core files in */templates/common/javascript/* for:

* common.js
* [PAGETYPE]-[PLATFORM].js or if not check [PAGETYPE].js
* [PAGE]-common.js
* [PAGE]-[PAGETYPE]-[PLATFORM].js or if not check [PAGE]-[PAGETYPE].js

Check module theme files in *SITE_DIR/themes/[ACTIVE_THEME]/modules/[current module]/javascript/* for:

* common.js
* [PAGETYPE]-[PLATFORM].js or if not check [PAGETYPE].js
* [PAGE]-common.js
* [PAGE]-[PAGETYPE]-[PLATFORM].js or if not check [PAGE]-[PAGETYPE].js

If there are no module theme files, check module core files in */templates/modules/[current module]/javascript/* for:

* common.js
* [PAGETYPE]-[PLATFORM].js or if not check [PAGETYPE].js
* [PAGE]-common.js
* [PAGE]-[PAGETYPE]-[PLATFORM].js or if not check [PAGE]-[PAGETYPE].js
    

Because Minify combines all files into a single file, it can be hard to tell where an given line of 
CSS or Javascript actually comes from.  When Minify debugging is turned on (MINIFY_DEBUG == 1), 
Minify adds comments to help with locating the actual file associated with a given line.

Note that the framework caches which files exist so it doesn't have to check all the possible files 
on every page load.  If you add a new file you may need to empty the minify cache to pick up the new file.

******
Images
******

Because images can live in either the core templates folder or the theme folder, image paths have 
the theme and platform directories added automatically.  Images are either common to all modules or 
belong to a specific module.  In order to allow flexible image naming, the device the image is for 
is specified by folder name rather than file name.

Images are searched across paths and the first image file present is returned.  

Common Image Search Paths: (ie: /common/images/[IMAGE_NAME].[EXT])
    
Check theme images in *SITE_DIR/themes/[ACTIVE_THEME]/common/images/* for:

* [PAGETYPE]-[PLATFORM]/[IMAGE_NAME].[EXT]
* [PAGETYPE]/[IMAGE_NAME].[EXT]
* [IMAGE_NAME].[EXT]

Check core images in */templates/common/images/* for:

* [PAGETYPE]-[PLATFORM]/[IMAGE_NAME].[EXT]
* [PAGETYPE]/[IMAGE_NAME].[EXT]
* [IMAGE_NAME].[EXT]

Module Image Search Paths: (ie: /modules/[MODULE_ID]/[IMAGE_NAME].[EXT])

Check theme images in *SITE_DIR/themes/[ACTIVE_THEME]/modules/links/images/* for:

* [PAGETYPE]-[PLATFORM]/[IMAGE_NAME].[EXT]
* [PAGETYPE]/[IMAGE_NAME].[EXT]
* [IMAGE_NAME].[EXT]

Check core images in */templates/modules/[MODULE_ID]/images/[PAGETYPE]-[PLATFORM]/* for:

* [PAGETYPE]-[PLATFORM]/[IMAGE_NAME].[EXT]
* [PAGETYPE]/[IMAGE_NAME].[EXT]
* [IMAGE_NAME].[EXT]

The rationale for searching for images rather than just specifying the full path is so that themes 
don't have to override a template just to replace an image being referenced inside it with an IMG tag.  
By dropping their own version of the image in the theme folder, the theme image will automatically be 
selected.  The device selection aspect of the image search algorithm is mostly just for convenience 
and to make the templates and CSS files more terse.

Note that image paths in CSS and templates should always be specified by an absolute path 
(ie: start with a /) but not contain the protocol, server, port, etc.  Any url base or device path 
will be prepended automatically by the framework.

*****************************
Important Assets to customize
*****************************

With the understanding of how assets are loaded, there are several file locations you should be
aware of.

-------------------
Background and Text
-------------------

* background
* text classes

----------
Navigation
----------

* home icon
* module icons
* nav background

-----------
Home Screen
-----------

* header graphic
* module icons
