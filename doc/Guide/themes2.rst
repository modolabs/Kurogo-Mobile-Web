################
Style and Themes
################

The Kurogo Framework makes it easy to visually customize your web application to reflect your organization's visual brand identity. Basic visual properties (such as colors, content and header backgrounds, fonts, and more) are very easy to customize across all device classes with changes to a single CSS file. Logos, module icons, header and body backgrounds, and other images can be easily replaced to complete your visual branding. Kurogo also gives you the flexibility to deliver advanced CSS and high-resolution images to devices and browsers that can support them, or use a simpler set of theme assets to simplify theme creation and maintenance.

Beyond straightforward visual branding, Kurogo theming can also extend deeper into application-level styling, templates, and images. Just about anything your users can see or interact with can be customized, depending on your institution's needs and your development team's technical abilities.

This section includes:

* Theming overview
* A tutorial on how to quickly create a simple theme
* A tutorial on how how to extend your theme with support for high-density screens and advanced browsers
* Technical details of Kurogo's theming layer

Theming requires a working understanding of CSS, and skill with an image editor such as Photoshop or GIMP.

****************
Theming Overview
****************

The Kurogo Framework has a theming layer which allows sites to make most stylistic changes to the web application without modifying the core libraries.  The advantage of using the theming layer is that site changes are isolated from the framework sources and can be more easily moved to a new version of the framework.

The core visual interface of Kurogo lives in "app/".  It is made up of HTML templates, CSS and Javascript files.  All HTML, CSS and Javascript in the core interface can be overridden by a theme. While it's possible to directly edit the files in "app/", doing so will increase the probability that future upgrades to Kurogo will break your site. As with everything else you build with Kurogo, it is highly recommended that you *not* directly edit any contents of this directory.

Each theme is contained within a directory inside the *SITE_DIR/themes* folder. By convention the default theme is named *default*. Each site can have multiple themes, but only one theme can be active at any time. You can easily switch between active themes from the *Site Configuration > Theme* screen in the Kurogo administration console.

Themes have the same directory structure as the core visual interface directory (app/). This allows paths in the CSS and HTML to be the same for the core interface and the theme interface.

*******************************
Tutorial: Create a Simple Theme
*******************************

It's recommended that you build a new theme by duplicating the default theme, editing its theme CSS, and replacing key image files. This allows you to quickly switch back to the default theme to check the effect of changes you're making in your new theme, or to revert to a working theme if you run into trouble.

1. **Create a working theme directory:** In *SITE_DIR/themes*, duplicate the *default* directory. Give the new directory a descriptive name. For the purposes of this tutorial, we'll call it *newtheme*.

2. **Modify the basic theme CSS:** In the *newtheme* directory, open *common/css/common.css*. This is the base theme CSS file. The essential rules you'll need to edit include:
	a. *body*: Set the background color (and tiling image, if you so desire) and base text size, line height, and font family. Almost all of the other font sizes throughout your web app will be calculated as percentages of this base font size, which can be specified in points (preferred) or pixels. (lines 8-9)
	b. *body, th, td, li, option, select, input*: Primary text color (line 14)
	c. *a, a:visited*: Default link text color (line 18)
	d. *a:hover*: Mouseover text color (only used on certain cursor-driven devices, such as BlackBerries and some feature phones) (line 21)
	e. *dt, .label, .legend, .legend.nonfocal, .legend h2, .legend h3, .searchlegend*: Accent/highlight text color used in a variety of places (line 59)
	f. *.address, .smallprint, .fineprint, .dek, #footer, .copyright, #footerlinks a, #footerlinks a:visited*: Secondary text color used for less important text (line 64)
	g. *.shaded, .HomeModule .blockborder*: Shaded content backgrounds, used in the header of certain tabbed screens and in homescreen portlets on tablets (line 84)
	h. *#navbar*: Size, background color/image, and base font size for the navigation bar at the top of every screen other than the home screen. Does not apply to Basic device class. It is recommended that the height not be modified. (lines 93-94)
	i. *.breadcrumbs, .breadcrumbs a, .breadcrumbs a:visited, .pagetitle*: Breadcrumbs and page titles at the top of every screen other than the home screen. Does not apply to Basic device class. This text color should contrast with the background color or image specified in *#navbar* for legibility. (line 99)
	
	Other styles may be modified as well, but the nine listed above are essential for any theme.

3. **Add your logo or other branding artwork:** Your organization's logo (or other identifying/branding image to be used in your mobile web app) will typically appear in several places:
	a. On the homepage: You'll need to create a version of the logo to appear on the homescreen for each of the major device classes. These images must be placed in the *SITE_DIR/themes/modules/home/images/* directory, as follows:
		* *logo-home.gif* must be a GIF image typically with a transparent background and transparency matte color matching the background color of your web app's homepage, for use on Basic- and Touch-class devices. This image will be centered horizontally within the screen. The default size is 208x35px, cropped tight to the actual artwork. [1]
		* *logo-home.png* must be a PNG image, typically 24-bit with transparency, for use on Compliant-class devices. The default size is 280x60px, cropped tight the actual artwork. [1] [2]
		[1] *THEME_DIR/config.ini* stores the height and width of the homescreen logo/banner image for different device classes. The values defined in this config file are written into the actual HTML as attributes on the <img> tag. The reason these image dimensions are handled this way, rather than in CSS, is that many browsers will not apply a CSS height and width until the image is loaded, but will always reserve the space defined in the <img> object's *height* and *width* attributes. The CSS-driven approach will cause the items on the home screen to jump vertically as soon as the logo image finishes loading, causing a usability problem, especially on touchscreen devices. 
		
		[2] If you create a home-page design a full-bleed focal image at the top of the page (e.g., a large photograph with your logo superimposed on it), you can set the image dimensions in *THEME_DIR/config.ini* to *banner-width = 100%* and *banner-height = auto*. This approach is only recommended for the Compliant device class, as the GIF image(s) used for the Basic and Touch device classes will render very poorly when scaled.

