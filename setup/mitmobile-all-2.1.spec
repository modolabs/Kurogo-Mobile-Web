Summary:	MIT Mobile Web
Name:		mitmobile-all
Version:	2.1
Release:	2.fc12
License:	MIT License
Group:		Applications/Web
Source:		mitmobile-all-%{version}-%{release}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Vendor:		Modo Labs, Inc.
URL:		http://imobileu.webfactional.com
Packager:	Sonya Huang <sonya.huang@modolabs.com>
Provides:	mitmobile-web, mitmobile-api
Requires:	httpd, php >= 5.2, php-ldap, php-mysql, php-pear-Log, php-xml
Prefix:		/opt /var/www/html

%description

MIT Mobile Web plus API extensions

%prep
%setup

%install
rm -rf $RPM_BUILD_ROOT
mkdir $RPM_BUILD_ROOT
cp -r $RPM_BUILD_DIR/$RPM_PACKAGE_NAME-$RPM_PACKAGE_VERSION/* $RPM_BUILD_ROOT

%clean

%files

%defattr(-,apache,apache,-)
/opt/mitmobile/bin
/opt/mitmobile/certs
/opt/mitmobile/maptiles
/opt/mitmobile/mobi-config
/opt/mitmobile/mobi-lib
/var/www/html/3down
/var/www/html/a
/var/www/html/about
/var/www/html/api
/var/www/html/calendar
/var/www/html/careers
/var/www/html/customize
/var/www/html/download
/var/www/html/e
/var/www/html/emergency
/var/www/html/error-page
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
/var/www/html/shuttleschedule
/var/www/html/sms
/var/www/html/stellar
/var/www/html/techcash
/opt/mitmobile/cache
/opt/mitmobile/logs
/opt/mitmobile/pushd
/opt/mitmobile/static
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
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/static
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/pushd

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

ln -sf $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc $RPM_INSTALL_PREFIX0/mitmobile/mobi-lib/lib_constants.inc
ln -sf $RPM_INSTALL_PREFIX0/mitmobile/maptiles/crushed $RPM_INSTALL_PREFIX1/api/maptile

%preun
if [ "$1" = "0" ]; then
   rm $RPM_INSTALL_PREFIX1/api/maptile
   rm $RPM_INSTALL_PREFIX0/mitmobile/mobi-lib/lib_constants.inc
fi

%postun
if [ "$1" = "0" ]; then
   rm -r $RPM_INSTALL_PREFIX0/mitmobile
fi

%changelog

