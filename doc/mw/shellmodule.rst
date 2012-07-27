######################
The ShellModule Object
######################

ShellModule provides the logic to execute commands in a Unix shell. It was created primarily
to aid in creating tasks that should be performed in the background and used with a tool
such as cron.

=================
Executing a Shell
=================

Currently shell module execution is only supported on Unix systems. To execute a shell command
you would call:

:kbd:`/path/to/kurogo/lib/KurogoShell moduleID command`

For instance:

:kbd:`/path/to/kurogo/lib/KurogoShell core version`

*KurogoShell* is a bash script that hands control over to PHP. This requires the PHP binary to
be in your path. 

===========
Properties
===========

Values the module developer should set in the class declaration:

* *id* (string) - This property should be set to the same name and 
  capitalization as the module directory. This property **must** be set by all 
  modules.

Values set by the parent class:

* *command* (string) - This property is set when the module initializes and 
  represents the command requested.

===============
Methods to Use
===============

The ShellModule is primarily used to perform actions (typically administrative actions). 

* *preFetchAllData* - This command will call *getAllControllers* and then attempt to fetch
all the data for module. This is used to prime the Kurogo Cache so that users are not waiting
for data to be fetched. Not all modules or retrievers will be able to use prefetching due
to the nature of their API calls.

-------
Output
-------

* *out* - output data to the console

===================
Methods to override
===================

* *initializeForCommand($command)* - This method represents the module's main
  logic when executing the shell. It must be overridden by each module.
* *getAllControllers* - In some modules you will need to override this method when implementing
  prefetch. This would be necessary if you are using a mechanism other than *feeds.ini*

=================
The CoreShellModule
=================

The CoreShellModule is a special subclass of ShellModule. It contains general information
about the site.
