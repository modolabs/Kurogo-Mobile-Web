##########################
CAS Authentication
##########################

The CAS authority allows you to authenticate users via a Central Authentication Service (CAS). Because it is
a limited access system, it is well suited to control access to modules to people in your organization.

Information about the `CAS server <http://www.jasig.org/cas>`_ and the `CAS Protocol <http://www.jasig.org/cas/protocol>`_ is available from `jasig.org <http://www.jasig.org/cas/>`_.

The `CAS Protocol <http://www.jasig.org/cas/protocol>`_ is a web-based single-sign-on protocol. 
Instead of entering authentication credentials directly into a login form, the user gets redirected 
to the CAS login page. Then they must authenticate there and will be subsequently redirected to your
application. Your application has no access to the user's password.

=============
Configuration
=============

The CASAuthentication authority relies on the `phpCAS library <https://wiki.jasig.org/display/CASC/phpCAS>`_ to handle communication with the CAS server. If you don't already have phpCAS, download the latest release.

To configure authentication, you only need to add a few parameters:

* *USER_LOGIN* - Should be set to *LINK*
* *CAS_PHPCAS_PATH* - If phpCAS is not in your include-path, specify the filesystem path that contains CAS.php.
* *CAS_PROTOCOL* - The protocol to use. Should be equivalent to one of the phpCAS constants, e.g. ``"2.0"``.
  *CAS_VERSION_1_0* => ``"1.0"``, *CAS_VERSION_2_0* => ``"2.0"``, *SAML_VERSION_1_1* => ``"S1"``
* *CAS_HOST* - The host name of the CAS server, e.g. ``"cas.example.edu"``
* *CAS_PORT* - The port the CAS server is listening on, e.g. ``"443"``
* *CAS_PATH* - The path of the CAS application, e.g. ``"/cas/"``
* *CAS_CA_CERT* - The filesystem path to a CA certificate that will be used to validate the authenticity of the CAS server, e.g. ``"/etc/tls/pki/certs/my_ca_cert.crt"``. If empty, no certificate validation will be performed (not recommended for production).

If your CAS server returns user attributes in a SAML-1.1 or CAS-2.0 response, you can provide these attributes
to Kurogo to display full names and support group-based :doc:`authorization`.

* *ATTRA_EMAIL* - Attribute name for the user's email adress, e.g. ``"email"``.
* *ATTRA_FIRST_NAME* - Attribute name for the user's first name, e.g. ``"givename"``.
* *ATTRA_LAST_NAME* - Attribute name for the user's last name, e.g. ``"surname"``. 
* *ATTRA_FULL_NAME* - Attribute name for the user's full name, e.g. ``"displayname"``.
* *ATTRA_MEMBER_OF* - Attribute name for the user's groups, e.g. ``"memberof"``.

.. code-block:: ini

    [cas]
    CONTROLLER_CLASS        = "CASAuthentication"
    TITLE                   = "Central Authentication Service (CAS)"
    USER_LOGIN              = "LINK"
    CAS_PROTOCOL            = "2.0"
    CAS_HOST                = "login.example.edu"
    CAS_PORT                = 443
    CAS_PATH                = "/cas/"
    CAS_CA_CERT             = ""
    ATTRA_EMAIL             = "EMail"
    ATTRA_FIRST_NAME        = "FirstName"
    ATTRA_LAST_NAME         = "LastName"
    ATTRA_FULL_NAME         = "DisplayName"
    ATTRA_MEMBER_OF         = "MemberOf"

