#!/bin/bash

cd ..

PREFIX0=$PWD/opt
PREFIX1=$PWD/mobi-web

PREFIX0_esc=${PREFIX0//\//\\\/}
PREFIX1_esc=${PREFIX1//\//\\\/}

cp setup/.htaccess mobi-web

sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' mobi-web/.htaccess
sed -i .bak 's/\/var\/www\/html/'${PREFIX1_esc}'/g' mobi-web/.htaccess
rm mobi-web/.htaccess.bak

cd opt/mitmobile
[ -d mobi-config ] || mkdir mobi-config
cp ../../mobi-config/*.* mobi-config

sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' mobi-config/*.inc
sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' mobi-config/*.ini
sed -i .bak 's/\/var\/www\/html/'${PREFIX1_esc}'/g' mobi-config/*.inc

ln -sf ../../mobi-lib
cd ../../mobi-lib
ln -sf ../opt/mitmobile/mobi-config/lib_constants.inc

cd ../opt/mitmobile/bin
for aFile in `ls ../../../mobi-scripts`; do ln -sf $aFile; done



