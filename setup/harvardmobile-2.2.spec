Summary: Harvard Mobile Web
Name:    harvardmobile
Version: 2.2
License: MIT License
Group:   Applications/Web
Source:  harvardmobile-%{version}-%{release}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Vendor: Modo Labs, Inc.
URL: http://imobileu.webfactional.com
Packager: Sonya Huang <sonya.huang@modolabs.com>
Provides: harvardmobile
Requires: httpd, php >= 5.2, php-gd, php-ldap, php-mysql, php-xml
Prefix:   /opt

%description

Harvard Mobile Web plus API extensions. This is an interim spec file. The long
term plan is to have the MIT Mobile Web framework as a versioned install, and
a separate install method that covers the code for the Harvard-specific pieces.
However, for the sake of expedience and simplicity in deployment, we're using
a single RPM as we've done in the past.

%prep
%setup

%pre

%install
rm -rf $RPM_BUILD_ROOT
mkdir $RPM_BUILD_ROOT
cp -r $RPM_BUILD_DIR/$RPM_PACKAGE_NAME-$RPM_PACKAGE_VERSION/* $RPM_BUILD_ROOT

%clean

%files

%defattr(-,apache,apache,-)
/opt/harvardmobile/config
/opt/harvardmobile/doc
/opt/harvardmobile/lib
/opt/harvardmobile/site
/opt/harvardmobile/templates
/opt/harvardmobile/web

%post
if [ "$1" = "1" ]; then # user is installing
   # set selinux permissions on cache file directories
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/harvardmobile/site/Harvard/cache
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/harvardmobile/site/Harvard/data

   # TODO: the following apache/php settings are required
   # in directory, AllowOverride All (/etc/httpd/conf/httpd.conf)
   # make sure short_open_tag is enabled (/etc/php.ini)
fi

%preun

%postun

%changelog
