################
Style and Themes
################

The Kurogo Framework makes it easy to visually customize your web application to reflect your organization's visual brand identity. Basic visual properties (such as colors, content and header backgrounds, fonts, and more) are very easy to customize across all device classes with changes to a single CSS file. Logos, module icons, header and body backgrounds, and other images can be easily replaced to complete your visual branding. Kurogo also gives you the flexibility to deliver advanced CSS and high-resolution images to devices and browsers that can support them, or use a simpler set of theme assets to simplify theme creation and maintenance.

Beyond straightforward visual branding, Kurogo theming can also extend deeper into application-level styling, templates, and images. Just about anything your users can see or interact with can be customized, depending on your institution's needs and your development team's technical abilities.

Theming requires a working understanding of CSS, and skill with an image editor such as Photoshop or GIMP.



****************
Theming Overview
****************

The Kurogo Framework has a theming layer which allows sites to make most stylistic changes to the web application without modifying the core libraries.  The advantage of using the theming layer is that site changes are isolated from the framework sources and can be more easily moved to a new version of the framework.

The core visual interface of Kurogo lives in "app/".  It is made up of HTML templates, CSS and Javascript files.  All HTML, CSS and Javascript in the core interface can be overridden by a theme. While it's possible to directly edit the files in "app/", doing so will increase the probability that future upgrades to Kurogo will break your site. As with everything else you build with Kurogo, it is highly recommended that you *not* directly edit any contents of this directory.

Each theme is contained within a directory inside the *SITE_DIR/themes* folder. By convention the default theme is named *default*. Each site can have multiple themes, but only one theme can be active at any time. You can easily switch between active themes from the *Site Configuration > Theme* screen in the Kurogo administration console.

Themes have the same directory structure as the core visual interface directory (app/). This allows paths in the CSS and HTML to be the same for the core interface and the theme interface.


********************
Designing Your Theme
********************

Content coming soon!



**********************************
Tutorial: Implement a Simple Theme
**********************************

It's recommended that you build a new theme by duplicating the default theme, editing its theme CSS, and replacing key image files. This allows you to quickly switch back to the default theme to check the effect of changes you're making in your new theme, or to revert to a working theme if you run into trouble.


-----------------------------------
1. Create a working theme directory
-----------------------------------
In *SITE_DIR/themes*, duplicate the *default* directory and give the new directory a descriptive name.

In your site's Kurogo administration console, go to the *Site Configuration > Theme* page and select your new theme, and click the "Save" button.

In a modern web browser (e.g., Chrome, Firefox 4+, Safari 3+), open a few test views of your site for different device classes:

* *http://[SITE_PATH]/device/compliant/home/*
* *http://[SITE_PATH]/device/compliant-bbplus/home/*
* *http://[SITE_PATH]/device/touch/home/*
* *http://[SITE_PATH]/device/basic/home/*, 
* *http://[SITE_PATH]/device/tablet/home/*

As you make the changes detailed below, come back to your browser and refresh the relevant test views to make sure that the changes have the intended effect.


-----------------------------
2. Modify the basic theme CSS
-----------------------------
In your theme directory (which we'll refer to from now on as *THEME_DIR*), open *common/css/common.css*. This is the base theme CSS file. The essential rules you'll need to edit include:

* *body*: Set the background color (and tiling image, if you so desire) and base text size, line height, and font family. Almost all of the other font sizes throughout your web app will be calculated as percentages of this base font size, which can be specified in points (preferred) or pixels. (lines 8-9)
* *body, th, td, li, option, select, input*: Primary text color (line 14)
* *a, a:visited*: Default link text color (line 18)
* *a:hover*: Mouseover text color (only used on certain cursor-driven devices, such as BlackBerries and some feature phones) (line 21)
* *dt, .label, .legend, .legend.nonfocal, .legend h2, .legend h3, .searchlegend*: Accent/highlight text color used in a variety of places (line 59)
* *.address, .smallprint, .fineprint, .dek, #footer, .copyright, #footerlinks a, #footerlinks a:visited*: Secondary text color used for less important text (line 64)
* *.shaded, .HomeModule .blockborder*: Shaded content backgrounds, used in the header of certain tabbed screens and in homescreen portlets on tablets (line 84)
* *#navbar*: Size, background color/image, and base font size for the navigation bar at the top of every screen other than the home screen. Does not apply to Basic device class. It is recommended that the height not be modified. (lines 93-94)
* *.breadcrumbs, .breadcrumbs a, .breadcrumbs a:visited, .pagetitle*: Breadcrumbs and page titles at the top of every screen other than the home screen. Does not apply to Basic device class. This text color should contrast with the background color or image specified in *#navbar* for legibility. (line 99)

Other styles may be modified as well, but the nine listed above are essential for any theme.


------------------------------------------
3. Add your logo or other branding artwork
------------------------------------------
Your organization's logo (or other identifying/branding image to be used in your mobile web app) will typically appear in several places:

Homepage
~~~~~~~~
You'll need to create a version of the logo to appear on the homepage: [#f2]_ [#f3]_

* Basic and Touch device classes: *THEME_DIR/modules/home/images/logo-home.gif* must be a GIF image,  transparent with a transparency matte color matching your homepage background color. This image will be centered horizontally within the screen. The default size is 208x35px, cropped tight to the actual artwork.
* Compliant device class: *THEME_DIR/modules/home/images/logo-home.png* must be a PNG image, typically 24-bit with transparency. The default size is 280x60px [#f1]_, cropped tight the actual artwork. 
	

Header logos
~~~~~~~~~~~~
The top left corner of every screen for every device class includes a logo/branding image. This image appears to the left of the page title on the Basic device class, and as the leftmost part of the header/navigation bar on all other device classes.

* Basic device class: *THEME_DIR/common/images/basic/logo.gif* must be a GIF image, typically with a transparent background and transparency matte color matching the background color. The default size is 35x35px.
* Compliant device class: *THEME_DIR/common/images/compliant/homelink.png* must be a PNG image, typically 24-bit with transparency, for use on Compliant-class devices. The default size is 57x45px [#f1]_. It should be designed in such a way that it appears seamlessly on top of the header/navigation bar background (navback.png, in the same directory).
* Tablet device class: *THEME_DIR/common/images/tablet/homelink.png* must be a PNG image, typically 24-bit with transparency, for use on Tablet-class devices. The default size is 66x52px [#f1]_. This be designed in such a way that it appears seamlessly on top of the header/navigation bar background (navback.png, in the same directory). **NEED TO CLEAN UP AND DOCUMENT TABLET HOMESCREEN ICON IMAGE, AND HOME MODULE ICON IMAGE**
	
	
Favicon and bookmark icons
~~~~~~~~~~~~~~~~~~~~~~~~~~
* *THEME_DIR/common/images/favicon.ico* must be a 16x16px ICO file, which is variously used by different browsers as the favicon, bookmarks and history icon, and in the screen title bar.
* *THEME_DIR/common/images/icon.png* must be a 57x57 (or pixel-doubled 114x114px [#f1]_) PNG, used as the homescreen shortcut icon for iOS devices and some Android devices.  
	
	
----------------------------------------
4. Customize or replace the module icons
----------------------------------------
Each module is visually represented by an icon on all device classes other than Basic. Kurogo's default theme includes a full set of professionally-created module icons, including many for modules not actually included in Kurogo. You are free to use and modify these icons, or replace some or all of them with ones that you create or license. If you're creating or licensing your own module icons, it's highly recommended that you start with vector images (e.g., Illustrator or EPS), which can be scaled to any size at full quality. If you can't create or purchase vector icon images, at least make every effort to start with bitmap (e.g., Photoshop) images at a large size such as 200x200px before scaling down to the actual sizes and formats you'll need for your web app. The module icons need to be saved in the following sizes and formats:


Main module icons
~~~~~~~~~~~~~~~~~
These appear on the homepage, as well as the Customize Homescreen module and the desktop-oriented Info module. 

* Compliant device class: The module icons in *THEME_DIR/modules/home/images/complaint/* must be PNG images, typically 24-bit with transparency. They should be the same size as the springboard images for modern BlackBerry devices (as set in *THEME_DIR/common/css/compliant-bbplus.css*, lines 26-27, and *THEME_DIR/common/css/compliant-blackberry.css*, lines 17-18). By default this is 64x64px, which is slightly larger than the default size for other Compliant devices. [#f1]_
* Touch device class: The module icons in *THEME_DIR/modules/home/images/touch/* must be GIF images, typically transparent with a transparency matte color matching your homepage background color. 


Breadcrumb module icons
~~~~~~~~~~~~~~~~~~~~~~~


Tablet module icons
~~~~~~~~~~~~~~~~~~~~~
	
	
-------------------------------------------	
5. Customize or replace supporting graphics
-------------------------------------------
The following secondary and support graphics should be color-adjusted or replaced to match your overall theme design:

Help buttons
~~~~~~~~~~~~
Buttons in the top right of the screen for Compliant and Tablet device classes: 

* Compliant device class: *THEME_DIR/common/images/compliant/help.png* must be a PNG image, typically 24-bit with transparency, for use on Compliant-class devices. The default size is 46x45px [#f1]_. It should be designed in such a way that it appears seamlessly on top of the header/navigation bar background (navback.png, in the same directory).
* Tablet device class: *THEME_DIR/common/images/tablet/help.png* must be a PNG image, typically 24-bit with transparency, for use on Compliant-class devices. The default size is 52x52px. It should be designed in such a way that it appears seamlessly on top of the header/navigation bar background (navback.png, in the same directory).


Header bar backgrounds
~~~~~~~~~~~~~~~~~~~~~~

Breadcrumb images
~~~~~~~~~~~~~~~~~

Other graphics
~~~~~~~~~~~~~~
Color-adjust or replace any or all of the following with images of the same size and format:

* Bullet images: *THEME_DIR/common/images/compliant/bullet.png* and *THEME_DIR/common/images/tablet/bullet.png* (identical), and *THEME_DIR/common/images/touch/bullet.gif*
* Search buttons: *THEME_DIR/common/images/compliant/search_button.png* and *THEME_DIR/common/images/tablet/bullet.png* (identical)


	
.. rubric:: Footnotes
.. [#f1] **Support for high-density device displays:** iOS devices with Retina Displays (iPhone 4, iPod Touch 4) have twice the number of pixels per inch (pixel density) that the original iPhone and iPod Touch did. Android devices with HDPI displays (e.g., with the common 480x800px or 480x854px screens), Windows Phone 7 devices, and some recent webOS devices have 1.5 times (or more) the pixel density of first-generation smartphones. Because these devices have more physical screen pixels in the same space, text and images can look sharper and more legible, especially for small text and detailed graphics. On web pages, providing a higher-resolution image while retaining the display size (through HTML attributes or CSS) can yield images that are visibly sharper and more legible on-screen. For instance, substituting a Compliant *home_logo.png* at 560x120px (twice the default 280x60px size) while retaining the *width=280, height=60* attributes in HTML will make that image have maximum possible visual quality on high-density displays. However, this comes at the cost of larger file size. You need to evaluate whether the increased visual quality and legibility are worth the tradeoff. Generally, logos, highly detailed images, and images incorporating text will benefit most from using high-density versions. In many cases, you can get visible benefits by creating 1.5x assets (e.g., 420x90px version of the Compliant *home_logo.png*) while incurring a smaller file-size penalty. You may want to experiment with different multipliers, viewing the results on different devices, to find the best tradeoff on an image-by-image basis. **Note:** BlackBerry devices running any OS prior to 6.0 do not scale images well, so it's best to use images sized exactly for them. 
.. [#f2] **Custom homepage logo/banner image sizes:** *THEME_DIR/config.ini* stores the height and width of the homescreen logo/banner image for different device classes. The values defined in this config file are written into the actual HTML as attributes on the <img> tag. The reason these image dimensions are handled this way, rather than in CSS, is that many browsers will not apply a CSS height and width until the image is loaded, but will always reserve the space defined in the <img> object's *height* and *width* attributes. The CSS-driven approach will cause the items on the home screen to jump vertically as soon as the logo image finishes loading, causing a usability problem, especially on touchscreen devices. 
.. [#f3] **Homepage with full-bleed banner image:** If you create a home-page design a full-bleed focal image at the top of the page (e.g., a large photograph with your logo superimposed on it), you can set the image dimensions in *THEME_DIR/config.ini* to *banner-width = 100%* and *banner-height = auto*. You should create the artwork at a minimum width of 320px, with a recommended maximum height of 240px. Note that this approach is only recommended for the Compliant device class, as the GIF image(s) used for the Basic and Touch device classes will render very poorly when scaled.
