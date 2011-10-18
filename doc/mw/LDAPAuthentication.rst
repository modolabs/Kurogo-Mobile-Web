###################
LDAP Authentication
###################
 
The *LDAPAuthentication* class provides authentication and user/group information from an LDAP server.
You specify a server, LDAP search base, and other optional parameters, and your LDAP users can 
authenticate to your mobile site. 
 
You can provide both user authentication as well as group membership information, if available.
 
=============
Configuration
=============
 
The *LDAPAuthentication* authority has a number of possible configuration values:
 
* *LDAP_HOST* - Required. The dns/ip address of the LDAP server. 
* *LDAP_PORT* - Optional (Default 389) The port to connect. Use 636 for SSL connections (recommended if available)
* *LDAP_USER_SEARCH_BASE* - Required if providing user authentication. Set this to the LDAP base dn where your
  user objects are located. It can be as specific as you wish. If necessary you could specify a specific
  container or OU.
* *LDAP_USER_UID_FIELD* - Optional (default uid) - Specifies the ldap field to use that stores the user's login id
* *LDAP_USER_EMAIL_FIELD* - Optional (default mail) - Specifies the ldap field to use that stores the user's
  email address
* *LDAP_USER_FIRSTNAME_FIELD* - Optional (default givenname) - Specifies the ldap field to use that stores the user's
  first name
* *LDAP_USER_LASTNAME_FIELD* - Optional (default sn) - Specifies the ldap field to use that stores the user's
  last name
* *LDAP_GROUP_SEARCH_BASE* - Required if providing group information. Set this to the LDAP base dn where your
  group objects are located. It can be as specific as you wish. If necessary you could specify a specific
  container or OU.
* *LDAP_GROUP_GROUPNAME_FIELD* - Optional (default cn). Specifies the ldap field to use that stores the group
  short name. 
* *LDAP_GROUP_GID_FIELD* - Optional (default gid),  Specifies the ldap field to use that stores the numerical
  group id. 
* *LDAP_GROUP_MEMBERS_FIELD* - Required if providing group information. Specifies the ldap field that indicates
  the members in the group. This authority assumes that this is a multi-value field in the group object.
* *LDAP_ADMIN_DN* - Some servers do not permit anonymous queries. If necessary you will need to provide a full 
  distinguished name for an account that has access to the directory. For security this account should
  only have read access and be limited to the search bases to which it needs to access.
* *LDAP_ADMIN_PASSWORD* - The password for the *LDAP_ADMIN_DN* account for systems that do not permit anonymous
  access. If you use an admin account with passwords, you should ensure you are connecting to your
  server using SSL (set *LDAP_PORT* to port 636)
  
============
How it Works
============

-------------------
User Authentication
-------------------

If you support user authentication (by setting the USER_LOGIN option to FORM) the authority will search
for a user with the *LDAP_USER_UID_FIELD* or *LDAP_USER_EMAIL_FIELD* matching the login typed in. If that record
is found it will attempt to bind to the ldap server using the password found. If the bind is successful
the user is authenticated. It is *strongly* recommended that you use SSL (by setting *LDAP_PORT* to 636) 
to protect the security of passwords.

-----------
User Lookup
-----------

Users are looked by executing an LDAP search beginning at *LDAP_USER_SEARCH_BASE* in either the *LDAP_USER_UID_FIELD*
or *LDAP_USER_EMAIL_FIELD*. If found, a user object is populated with *LDAP_USER_USERID_FIELD*, *LDAP_USER_EMAIL_FIELD*
and *LDAP_USER_FIRSTNAME_FIELD*,*LDAP_USER_LASTNAME_FIELD* and *LDAP_USER_FULLNAME_FIELD* if present. Other values are
placed in the *attributes* property of the user object.

------------
Group Lookup
------------

Groups are looked by executing an LDAP search beginning at *LDAP_GROUP_SEARCH_BASE* in *LDAP_GROUP_GROUPNAME_FIELD*. If found
A group object is populated with *LDAP_GROUP_GROUPNAME_FIELD* and *LDAP_GROUP_GID_FIELD* 

-----------------------
Group Membership Lookup
-----------------------

Group membership is looked up by executing an LDAP search for the *LDAP_GROUP_GROUPNAME_FIELD* and retrieving 
the *LDAP_GROUP_MEMBERS_FIELD* values. Each value should be a valid user id. It will return an array of user 
objects. LDAP authorities can only return user objects from the same authority. 

