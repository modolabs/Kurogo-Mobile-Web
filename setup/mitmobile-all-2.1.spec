Summary:	MIT Mobile Web
Name:		mitmobile-all
Version:	2.1
License:	MIT License
Group:		Applications/Web
Source:		mitmobile-all-%{version}-%{release}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Vendor:		Modo Labs, Inc.
URL:		http://imobileu.webfactional.com
Packager:	Sonya Huang <sonya.huang@modolabs.com>
Provides:	mitmobile-web, mitmobile-api
Requires:	httpd, php >= 5.2, php-gd, php-ldap, php-mysql, php-xml
Prefix:		/opt /var/www/html

%description

MIT Mobile Web plus API extensions

%prep
%setup

%pre
if [ "$1" = "2" ]; then # user is upgrading
   cp $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc /tmp
fi

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
/var/www/html/a
/var/www/html/about
/var/www/html/api
/var/www/html/calendar
/var/www/html/courses
/var/www/html/customize
/var/www/html/dining
/var/www/html/download
/var/www/html/e
/var/www/html/error-page
/var/www/html/favicon.ico
/var/www/html/home
/var/www/html/.htaccess
/var/www/html/index.php
/var/www/html/links
/var/www/html/map
/var/www/html/mobile-about
/var/www/html/n
/var/www/html/news
/var/www/html/page_builder
/var/www/html/people
/var/www/html/shuttleschedule
/var/www/html/robots.txt
/opt/mitmobile/cache
/opt/mitmobile/logs
/opt/mitmobile/pushd
/opt/mitmobile/static
/var/www/html/Basic
/var/www/html/Touch
/var/www/html/Webkit

%post
if [ "$1" = "1" ]; then # user is installing
   # set selinux permissions on cache file directories
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/cache
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/logs
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/static
   chcon -t system_u:object_r:httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/pushd

   ln -sf $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc $RPM_INSTALL_PREFIX0/mitmobile/mobi-lib/lib_constants.inc
   ln -sf $RPM_INSTALL_PREFIX0/mitmobile/maptiles/crushed $RPM_INSTALL_PREFIX1/api/maptile

   # TODO: the following apache/php settings are required
   # in directory, AllowOverride All (/etc/httpd/conf/httpd.conf)
   # make sure short_open_tag is enabled (/etc/php.ini)
fi

if [ "$1" = "2" ]; then # user is upgrading
   # get mysql username from old config file
   mysql_user=`grep MYSQL_USER /tmp/lib_constants.inc`
   mysql_user=${mysql_user%\');}
   mysql_user=${mysql_user##*\'}

   mysql_pass=`grep MYSQL_PASS /tmp/lib_constants.inc`
   mysql_pass=${mysql_pass%\');}
   mysql_pass=${mysql_pass##*\'}

   mysql_db=`grep MYSQL_DBNAME /tmp/lib_constants.inc`
   mysql_db=${mysql_db%\');}
   mysql_db=${mysql_db##*\'}

   sed -i 's/mysql_user/'${mysql_user}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc
   sed -i 's/mysql_pass/'${mysql_pass}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc
   sed -i 's/mysql_db/'${mysql_db}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/lib_constants.inc
fi

# edit config files for changed prefixes
# (do this every time since config files can change in the repo)
if [ "$RPM_INSTALL_PREFIX0" != "/opt" ]; then
   PREFIX0=${RPM_INSTALL_PREFIX0//\//\\\/}
   sed -i 's/\/opt\/mitmobile/'${PREFIX0}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/*
   sed -i 's/\/opt\/mitmobile/'${PREFIX0}'/g' $RPM_INSTALL_PREFIX1/.htaccess
fi

if [ "RPM_INSTALL_PREFIX1" != "/var/www/html" ]; then
   PREFIX1=${RPM_INSTALL_PREFIX1//\//\\\/}
   sed -i 's/\/var\/www\/html/'${PREFIX1}'/g' $RPM_INSTALL_PREFIX0/mitmobile/mobi-config/web_constants_*.ini
fi

%preun
if [ "$1" = "0" ]; then # user is uninstalling
   rm $RPM_INSTALL_PREFIX1/api/maptile
   rm $RPM_INSTALL_PREFIX0/mitmobile/mobi-lib/lib_constants.inc
fi

%postun
if [ "$1" = "0" ]; then # user is uninstalling
   rm -r $RPM_INSTALL_PREFIX0/mitmobile
fi

%changelog

