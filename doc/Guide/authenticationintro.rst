################################
Authentication and Authorization
################################


While many services are suitable for a public audience, there are instances where you want to restrict
access to certain modules or data to authenticated users. You may also want to provide personalized 
content or allow users to participate in feedback.

The Kurogo framework provides a robust system for authenticating users and authorizing access to content.
You can provide the ability to authenticate against private or public services and authorize access or
administration based on the user's identity or membership in a particular group. 

Kurogo is designed to integrate with existing identity systems such as Active Directory, MySQL databases,
Twitter, Facebook and Google. You can supplement this information by creating groups managed by the
framework independent of the user's original identity.

.. toctree::
   :maxdepth: 1

   authentication
   PasswdAuthentication
   DatabaseAuthentication
   LDAPAuthentication
   ActiveDirectoryAuthentication
   FacebookAuthentication
   TwitterAuthentication
   GoogleAuthentication
   GoogleAppsAuthentication
   authorization
