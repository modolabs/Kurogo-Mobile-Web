#!/bin/bash

RPMBUILDDIR=~/rpmbuild
VERSION=2.1
RPMVERSION=0.1
OS=fc12
ORIGPWD=$PWD

# todo: parse parameters for version, rpmversion, os

echo "creating rpms for version $VERSION"

MOBILE_WEB_TARBALL=mitmobile-web-${VERSION}-${RPMVERSION}${OS}.tar.gz
tar -zcf ${MOBILE_WEB_TARBALL} mitmobile-web-${VERSION}
mv ${MOBILE_WEB_TARBALL} ${RPMBUILDDIR}/SOURCES

cp mitmobile-web-${VERSION}.spec ${RPMBUILDDIR}/SPECS

#MOBILE_API_TARBALL=mitmobile-api-${VERSION}-${RPMVERSION}${OS}.tar.gz
#tar -zcf ${MOBILE_API_TARBALL} mitmobile-api-${VERSION}
#mv ${MOBILE_API_TARBALL} ${RPMBUILDDER}/SOURCES

#cp mitmobile-api-${VERSION}.spec ${RPMBUILDDIR}/SPECS

cd ${RPMBUILDDIR}

rpmbuild -ba SPECS/mitmobile-web-${VERSION}.spec

cd $ORIGPWD
