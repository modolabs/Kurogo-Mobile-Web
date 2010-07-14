#!/bin/bash

PREFIX0=/opt                # directory to install third party apps
### Apache document root...
PREFIX1=/usr/local/apache2/htdocs
# Debian users uncomment the following
#PREFIX1=/var/www
# Red Hat users uncomment the following
#PREFIX1=/var/www/html
# MAMP users uncomment the following
#PREFIX1=/Applications/MAMP/htdocs 

# if mysql is installed outside of $PATH (e.g. on MAMP),
# enter the path to the command line program
MYSQL_BIN=`which mysql`
# MAMP users can just uncomment the line below
#MYSQL_BIN=/Applications/MAMP/Library/bin/mysql

# mysql info for this program (if using)
MYSQL_USER=mysql_user
MYSQL_PASS=mysql_pass
MYSQL_DB=mysql_db

# users: do not edit anything below this line
# -------------------------------------------------------------------------

# check dependencies

ERRORS=0
WARNINGS=0
USE_MYSQL=1

echo "checking prefixes..."
if [ ! -d "$PREFIX0" ]; then
    echo "ERROR: cannot find $PREFIX0"
    ERRORS=$((ERRORS+1))
fi

if [ ! -d "$PREFIX1" ]; then
    echo "ERROR: cannot find $PREFIX1"
    ERRORS=$((ERRORS+1))
fi

echo "checking for PHP..."
if [ -z `which php` ]; then
    echo "ERROR: cannot find PHP"
    ERRORS=$((ERRORS+1))
fi

echo "checking PHP version..."
PHPVERSION=`php -v | grep -o "PHP 5"`
if [ -z "$PHPVERSION" ]; then
    echo "ERROR: only PHP 5.x is supported"
    ERRORS=$((ERRORS+1))
fi

echo "checking for sed..."
if [ -z `which sed` ]; then
    echo "ERROR: cannot find sed"
    echo "this is not a showstopper, but you have to configure manually"
    ERRORS=$((ERRORS+1))
fi

if [ "$1" = "--update" ]; then
   echo "skipping mysql"
   USE_MYSQL=0
else
   echo "checking for mysql..."
   if [ -z "$MYSQL_BIN" ]; then
      echo "WARNING: cannot find mysql, installation will proceed without mysql"
      USE_MYSQL=0
      WARNINGS=$((WARNINGS+1))
   fi
fi

echo "$ERRORS errors and $WARNINGS warnings"

if [ "$ERRORS" -gt "0" ]; then
    echo "system check failed, exiting"
    exit 1
fi

# create uninstall script

cp uninstall-mobiweb.sh.init uninstall-mobiweb.sh

PREFIX0_esc=${PREFIX0//\//\\\/}
PREFIX1_esc=${PREFIX1//\//\\\/}
MYSQL_BIN_esc=${MYSQL_BIN//\//\\\/}

sed -i .bak 's/PREFIX0=/PREFIX0='${PREFIX0_esc}'/g' uninstall-mobiweb.sh
sed -i .bak 's/PREFIX1=/PREFIX1='${PREFIX1_esc}'/g' uninstall-mobiweb.sh

if [ "$USE_MYSQL" = "1" ]; then
    sed -i .bak 's/USE_MYSQL=/USE_MYSQL=1/g' uninstall-mobiweb.sh
    sed -i .bak 's/MYSQL_BIN=/MYSQL_BIN='${MYSQL_BIN_esc}'/g' uninstall-mobiweb.sh
    sed -i .bak 's/MYSQL_USER=/MYSQL_USER='${MYSQL_USER}'/g' uninstall-mobiweb.sh
    sed -i .bak 's/MYSQL_PASS=/MYSQL_PASS='${MYSQL_PASS}'/g' uninstall-mobiweb.sh
    sed -i .bak 's/MYSQL_DB=/MYSQL_DB='${MYSQL_DB}'/g' uninstall-mobiweb.sh
fi

chmod 755 uninstall-mobiweb.sh

# copy files to mitmobile directory

cd ..
echo "copying files..."
if [ -d "$PREFIX0/mitmobile" ]; then
    echo "$PREFIX0/mitmobile found, copying contents..."
    # assume this directory was created by a previous installation
    cp -rp opt/mitmobile/* $PREFIX0/mitmobile
else
    echo "creating $PREFIX0/mitmobile..."
    cp -rp opt/mitmobile $PREFIX0
fi

cp -rp mobi-config $PREFIX0/mitmobile
cp -rp mobi-lib $PREFIX0/mitmobile
cp -rp mobi-scripts/* $PREFIX0/mitmobile/bin
cp -rp mobi-web $PREFIX0/mitmobile # we're doing this to help uninstallation

# copy web files

cp -rp mobi-web/* $PREFIX1
cp -p setup/.htaccess $PREFIX1

# create symlinks

ln -sf $PREFIX0/mitmobile/mobi-config/lib_constants.inc $PREFIX0/mitmobile/mobi-lib/lib_constants.inc
ln -sf $PREFIX0/mitmobile/maptiles/crushed $PREFIX1/api/map/tile2
ln -sf $PREFIX0/mitmobile/maptiles/tiles_last_updated.txt $PREFIX1/api/map/tiles_last_updated.txt


# fill in variables

echo "configuring copied files..."
sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' $PREFIX0/mitmobile/mobi-config/*.inc
sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' $PREFIX0/mitmobile/mobi-config/*.ini
sed -i .bak 's/\/var\/www\/html/'${PREFIX1_esc}'/g' $PREFIX0/mitmobile/mobi-config/*.inc
sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' $PREFIX1/.htaccess
sed -i .bak 's/\/opt\/mitmobile/'${PREFIX0_esc}'\/mitmobile/g' $PREFIX0/mitmobile/bin/mobi-maptiles.sh

if [ "$USE_MYSQL" = "1" ]; then
   echo "configuring MySQL parameters..."
   for aFile in `ls $PREFIX0/mitmobile/mobi-config/lib_constants_*.inc`; do
      sed -i .bak 's/mysql_user/'${MYSQL_USER}'/g' $aFile
      sed -i .bak 's/mysql_pass/'${MYSQL_PASS}'/g' $aFile
      sed -i .bak 's/mysql_db/'${MYSQL_DB}'/g' $aFile
   done

   echo "setting up mysql credentials..."
   cp setup/mobi-web.sql.init setup/mobi-web.sql

   read -p "mysql root password: " MYSQL_ROOT_PASS

   sed -i .bak 's/mysql_user/'${MYSQL_USER}'/g' setup/mobi-web.sql
   sed -i .bak 's/mysql_pass/'${MYSQL_PASS}'/g' setup/mobi-web.sql
   sed -i .bak 's/mysql_db/'${MYSQL_DB}'/g' setup/mobi-web.sql

   ${MYSQL_BIN}admin --user=root --password=$MYSQL_ROOT_PASS create $MYSQL_DB
   ${MYSQL_BIN} --user=root --password=$MYSQL_ROOT_PASS $MYSQL_DB < setup/mobi-web.sql
   ${MYSQL_BIN} --user=root --password=$MYSQL_ROOT_PASS $MYSQL_DB < setup/mobi-api.sql

fi

echo "done"
