#!/bin/bash

RPMBUILDDIR=~/rpmbuild
VERSION=2.1
RPMVERSION=2
OS=fc12
ORIGPWD=$PWD
PACKAGE=mitmobile-all

# todo: parse parameters for version, rpmversion, os

cd `dirname $0`
cd ..

# section on getting and compiling pngcrush source
# not sure if it's kosher to build it in rpmbuild

CRUSHVER=1.7.11
PNGCRUSH=pngcrush-${CRUSHVER}

if [ ! -d ${PNGCRUSH} ]; then
   if [ ! -f /tmp/${PNGCRUSH}.tar.gz ]; then
      wget http://cdnetworks-us-2.dl.sourceforge.net/project/pmt/pngcrush/00-${CRUSHVER}/${PNGCRUSH}.tar.gz -O /tmp/${PNGCRUSH}.tar.gz
   fi
   tar -zxf /tmp/${PNGCRUSH}.tar.gz
   cd $PNGCRUSH
   make
   cd ..
fi

# rpm proper

echo "creating rpms for version $VERSION"

TARBALL=${PACKAGE}-${VERSION}-${RPMVERSION}.${OS}.tar.gz
SRCROOT=${PACKAGE}-${VERSION}
mkdir $SRCROOT
mkdir -p $SRCROOT/var/www/
cp -r mobi-web $SRCROOT/var/www/html
cp setup/.htaccess $SRCROOT/var/www/html

cp -r opt $SRCROOT
cp -r mobi-config $SRCROOT/opt/mitmobile
cp -r mobi-lib $SRCROOT/opt/mitmobile
cp mobi-scripts/* $SRCROOT/opt/mitmobile/bin

if [ -f $PNGCRUSH/pngcrush ]; then
   cp $PNGCRUSH/pngcrush opt/mitmobile/bin
fi

tar -zcf ${TARBALL} ${SRCROOT} --exclude=.git*
mv ${TARBALL} ${RPMBUILDDIR}/SOURCES

SPEC=${PACKAGE}-${VERSION}.spec

cp setup/${SPEC} ${RPMBUILDDIR}/SPECS

rpmbuild -ba ${RPMBUILDDIR}/SPECS/${SPEC}

# cleanup

rm -r $SRCROOT


