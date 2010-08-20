Summary:	MIT Mobile Web
Name:		mitmobile-web
Version:	2.1
Release:	0.1fc12
License:	MIT License
Group:		Applications/Web
Source:		mitmobile-web-%{version}-%{release}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Vendor:		Modo Labs, Inc.
URL:		http://imobileu.webfactional.com
Packager:	Sonya Huang <sonya.huang@modolabs.com>
Provides:	mitmobile-web
Requires:	httpd, php >= 5.2, php-xml
Prefix:		/opt /var/www/html

%description

MIT Mobile Web standalone installation (no API)
# optional packages:
# php-ldap, php-nusoap

%prep
%setup

%install
rm -rf $RPM_BUILD_ROOT
mkdir $RPM_BUILD_ROOT
cp -r $RPM_BUILD_DIR/$RPM_PACKAGE_NAME-$RPM_PACKAGE_VERSION/* $RPM_BUILD_ROOT

%clean

%files

%defattr(-,root,root,-)
/opt/mitmobile/mobi-config
/opt/mitmobile/mobi-lib
/var/www/html/3down
/var/www/html/a
/var/www/html/about
/var/www/html/calendar
/var/www/html/careers
/var/www/html/courses
/var/www/html/customize
/var/www/html/download
/var/www/html/e
/var/www/html/emergency
/var/www/html/error-page
/var/www/html/gtfs
/var/www/html/home
/var/www/html/.htaccess
/var/www/html/index.php
/var/www/html/libraries
/var/www/html/links
/var/www/html/map
/var/www/html/mobile-about
/var/www/html/n
/var/www/html/page_builder
/var/www/html/people
/var/www/html/robots.txt
/var/www/html/setup
/var/www/html/shuttleschedule
/var/www/html/sms
/var/www/html/techcash

%defattr(-,apache,apache,-)
/opt/mitmobile/cache
/opt/mitmobile/logs
/opt/mitmobile/moduledata
/var/www/html/Basic
/var/www/html/Touch
/var/www/html/Webkit

%post
# required httpd settings:
# in directory, AllowOverride All (/etc/httpd/conf/httpd.conf)
# make sure short_open_tag is enabled (/etc/php.ini)

# set selinux permissions on cache file directories
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/cache
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/logs
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/moduledata

# edit config files for changed prefixes
if [ "$RPM_INSTALL_PREFIX0" != "/opt" ]; then
   PREFIX0=${RPM_INSTALL_PREFIX0//\//\\\/}
   sed -i 's/\/opt\/mitmobile/'${PREFIX0}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/*
   sed -i 's/\/opt\/mitmobile/'${PREFIX0}'/g' $RPM_INSTALL_PREFIX1/.htaccess
fi

if [ "RPM_INSTALL_PREFIX1" != "/var/www/html" ]; then
   PREFIX1=${RPM_INSTALL_PREFIX1//\//\\\/}
   sed -i 's/\/var\/www\/html/'${PREFIX1}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/web_constants_*.ini
fi

ln -s $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants_prod.inc $RPM_INSTALL_PREFIX0/mitmobile/mobi-lib/lib_constants.inc

%postun
if [ "$1" = "0" ]; then
   rmdir $RPM_INSTALL_PREFIX0/mitmobile
fi

%changelog

