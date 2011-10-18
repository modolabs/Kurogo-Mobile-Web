###############################
Active Directory Authentication
###############################

The active directory authority allows you to easily configure your site to authenticate against an Active Directory
domain. It is a subclass of :doc:`LDAP Authentication <LDAPAuthentication>` and provides simpler configuration settings since AD
domains share similar basic characteristics. 

=============
Configuration
=============

Because attributes in active directory are standardized, there are only a few parameters necessary:

* *LDAP_HOST* - Required. The DNS address of your active directory domain. You could include a specific domain controller if desired.
* *LDAP_PORT* - Optional (Default 389) The port to connect. Use 636 for SSL connections (recommended if available)
* *LDAP_SEARCH_BASE* - The LDAP search base of your active directory domain. 
* *LDAP_ADMIN_DN* - Most active directory domains do not permit anonymous queries. If necessary you will need to provide a full 
  distinguished name for an account that has access to the directory. For security this account should
  only have read access and be limited to the search bases to which it needs to access.
* *LDAP_ADMIN_PASSWORD* - The password for the *LDAP_ADMIN_DN* account.

Once configured, users can login with their short name (samaccountname) or their email address (if present).

