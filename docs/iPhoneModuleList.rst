===================
Configuring Modules
===================

------------
Module Order
------------

The list of modules can be configured by editing 
``App Delegate/MITModuleList.h``, the following chunk of
code creates the default list of modules

.. code-block:: objective-c

    // add your MITModule subclass here by duplicating this line
    //[result addObject:[[[YourMITModuleSubclass alloc] init] autorelease]];
    [result addObject:[[[NewsModule alloc] init] autorelease]];
    [result addObject:[[[ShuttleModule alloc] init] autorelease]];
    [result addObject:[[[CMModule alloc] init] autorelease]];
    [result addObject:[[[CalendarModule alloc] init] autorelease]];
    [result addObject:[[[StellarModule alloc] init] autorelease]];
    [result addObject:[[[PeopleModule alloc] init] autorelease]];
    [result addObject:[[[EmergencyModule alloc] init] autorelease]];
    [result addObject:[[[MobileWebModule alloc] init] autorelease]];
    [result addObject:[[[SettingsModule alloc] init] autorelease]];
    [result addObject:[[[AboutModule alloc] init] autorelease]];

Changing the order of these lines will result in changing the default
order of the modules.  Simply removing a line, will result in the
removal of the module from the application.  It should be noted that
the user is not allowed to reorder ``MobileWebModule``, ``SettingsModule``,
or ``AboutModule``, so they need to remain at the end of the list. If you
are adding a new module you will need to include an import statement
similar to the other import statements at the top of this file.

-----------
Module Name
-----------

You can customize the name of each module.  Modules have a
short and long name.  In order to change these names, you have
to edit a  subclass of MITModule.  For example for the People
Directory you have to edit code in ``Modules/People/PeopleModule.m``

.. code-block:: objective-c

   - (id)init
   {
       if (self = [super init]) {
           self.tag = DirectoryTag;
           self.shortName = @"Directory";
           self.longName = @"People Directory";
           self.iconName = @"people";

           viewController = [[[PeopleSearchViewController alloc] initWithStyle:UITableViewStyleGrouped] autorelease];
	   viewController.navigationItem.title = self.longName;
        
           [self.tabNavController setViewControllers:[NSArray arrayWithObject:viewController]];
       }
       return self;
   }

In the code above you change ``self.shortName``, ``self.longName``, ``self.iconName``.
However, if you change the icon name, you need to be sure to change the names
of the modules icon files.

Below is a list of each MITModule file


* ``Modules/About/AboutModule.m``
* ``Modules/Calendar/CalendarModule.m``
* ``Modules/Campus Map/CMModule.m``
* ``Modules/Emergency/EmergencyModule.m``
* ``Modules/Mobile Web/MobileWebModule.m``
* ``Modules/News/NewsModule.m``
* ``Modules/People/PeopleModule.m``
* ``Modules/Settings/SettingsModule.m``
* ``Modules/ShuttleTrack/ShuttleModule.m``
* ``Modules/Stellar/StellarModule.m``


---------------------------------
Configuring the Mobile Web Module 
---------------------------------

You can configure the domain name of the website this
module opens launches by changing the constant
``MITMobileWebDomainString`` which can be found in ``Common/MITConstants.m``.
The constant has three versions, a version for the development, staging, and 
production build of the application.