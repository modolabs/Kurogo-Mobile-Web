=====================
Creating a New Module
=====================

The best way to create a new module is create new directory
in the ``Modules`` directory to store all your code.  You will
need to extend the class ``MITModule``, the code for which is
in ``Common/MITModule.h`` and ``Common/MITModule.m``.  The following
variables will need to be defined in your subclass ``self.tag``,
``self.shortName``, ``self.longName``, ``self.iconName``.  Every
module has to have use UINavigationController as its top level controller.
This is initialized in ``MITModule`` class, but your module's code
will need to pop and push view controllers onto ``self.tabNavController``

Here is the an example of ``init`` function taken from 
``Modules/People/PeopleModule.h``

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

Each module should define its tag as a constant and add 
it too ``Common/MITConstants.h`` and ``Common/MITConstants.m``

If a module wants to keep track of its state using a URL path
like object, the module can set the properties

.. code-block:: objective-c

   @property (nonatomic, retain) NSString *currentPath;
   @property (nonatomic, retain) NSString *currentQuery;

If a module supports push notification it should set the following
propert

.. code-block:: objective-c

   @property (nonatomic, assign) BOOL pushNotificationSupported;
