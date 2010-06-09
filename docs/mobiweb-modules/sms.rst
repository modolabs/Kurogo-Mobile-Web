===
SMS
===

The SMS module provides an overview of the interactive SMS service and
a cheat sheet for all general and module commands.

In index.php, the class Module provides a wrapper for storing the
module name, description, keywords (i.e. the command to enter in the
text message), and examples. The class SMSInstructions provides a
wrapper around a list of Module objects.

An SMSInstructions object is populated with instructions to use the
People Directory, Shuttle Schedule, and Stellar. ip/index.html and
sp/index.html both contain the general overview, and sections where
each module’s instructions are rendered.
