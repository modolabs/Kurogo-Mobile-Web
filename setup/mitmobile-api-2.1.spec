Summary:	MIT Mobile Web API extensions
Name:		mitmobile-api
Version:	2.1
Release:	0.1fc12
License:	MIT License
Group:		Applications/Web
Source:		mitmobile-api-%{version}-%{release}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Vendor:		Modo Labs, Inc.
URL:		http://imobileu.webfactional.com
Packager:	Sonya Huang <sonya.huang@modolabs.com>
Provides:	mitmobile-api
Requires:	mitmobile-web, mysql, php-mysql, php-pear-Log, php-pear
Prefix:		/opt /var/www/html

BuildRequires:	gcc

%description

Native app API extensions for MIT Mobile Web.

%prep
%setup -q


%build
# make pngcrush
%configure
make %{?_smp_mflags}


%install
# copy pngcrush to bin
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=$RPM_BUILD_ROOT

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/opt/mitmobile/bin
/opt/mitmobile/maptiles
/opt/mitmobile/mobi-web/api

%defattr(-,apache,apache,-)
/opt/mitmobile/pushd

%doc

%post
chcon -t httpd_sys_content_t $RPM_INSTALL_PREFIX0/mitmobile/pushd

%changelog
