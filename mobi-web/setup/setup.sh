#!/bin/bash

# this script needs to be run as a user
# with read access to apache logs in /var/log/httpd

cp ../constants.php.init ../constants.php

# check if we have unpacked in the default directory
# otherwise we need to set different constants
WEBROOT=${PWD%/*}/

LIBDIR=`grep "define(\"LIBDIR" ../constants.php`
LIBDIR=${LIBDIR%%\');*}
LIBDIR=${LIBDIR#*\'}

# there should be a more efficient way than the followin 3 lines
# for getting values of defined constants in php...
DEFAULTWEBROOT=`grep "define(\"WEBROOT" ../constants.php`
DEFAULTWEBROOT=${DEFAULTWEBROOT%%\');*}
DEFAULTWEBROOT=${DEFAULTWEBROOT#*\'}
if [[ "$WEBROOT" != "$DEFAULTWEBROOT" ]]; then
    echo "using $WEBROOT as webroot"
    echo "to use another directory (not sure why), enter it below"
    read -p "web directory [$WEBROOT]: " NEW_WEBROOT
    if [[ -n "$NEW_WEBROOT" ]]; then
	WEBROOT=$NEW_WEBROOT
	echo "setting WEBROOT to $WEBROOT"
    fi
    WEBROOT_FROM=${DEFAULTWEBROOT//\//\\\/}
    WEBROOT_TO=${WEBROOT//\//\\\/}
    sed -i 's/'${WEBROOT_FROM}'/'${WEBROOT_TO}'/g' ../constants.php

    # i wish the following worked but
    # some people will probably be running this as root
    #USERDIR=`whoami`
    USERDIR=${PWD#/home/}
    USERDIR=${USERDIR%%/*}

    # set LIBDIR since we are not the production server
    DEFAULTLIBDIR=$LIBDIR
    # guess user's libdir (/home/username/lib/trunk)
    LIBDIR=/home/$USERDIR/lib/trunk/
    echo "production lib directory is set to $DEFAULTLIBDIR"
    echo "setting user lib directory to $LIBDIR"
    echo "to use a different directory, enter it below"
    read -p "lib directory [$LIBDIR]: " NEW_LIBDIR
    if [[ -n "$NEW_LIBDIR" ]]; then
	LIBDIR=$NEW_LIBDIR
	echo "setting LIBDIR to $LIBDIR"
    fi
    LIBDIR_FROM=${DEFAULTLIBDIR//\//\\\/}
    LIBDIR_TO=${LIBDIR//\//\\\/}
    sed -i 's/'${LIBDIR_FROM}'/'${LIBDIR_TO}'/g' ../constants.php

    # set HTTPROOT since we are not the production server
    DEFAULTHTTPROOT=`grep "define(\"HTTPROOT" ../constants.php`
    DEFAULTHTTPROOT=${DEFAULTHTTPROOT%%\');*}
    DEFAULTHTTPROOT=${DEFAULTHTTPROOT#*\'}
    # guess user's httproot (/~username/)
    HTTPROOT=/~$USERDIR${WEBROOT##*/web/trunk}
    echo "using $HTTPROOT as HTTPROOT"
    echo "to use a different directory, enter it below"
    read -p "httproot [$HTTPROOT]: " NEW_HTTPROOT
    if [[ -n "$NEW_HTTPROOT" ]]; then
	HTTPROOT=$NEW_HTTPROOT
	echo "setting HTTPROOT to $HTTPROOT"
    fi
    HTTPROOT_FROM="'$DEFAULTHTTPROOT'"
    HTTPROOT_FROM=${HTTPROOT_FROM//\//\\\/}
    HTTPROOT_TO="'$HTTPROOT'"
    HTTPROOT_TO=${HTTPROOT_TO//\//\\\/}
    sed -i 's/'${HTTPROOT_FROM}'/'${HTTPROOT_TO}'/g' ../constants.php
fi

# make bucket directories group-writeable
# for attribs cache files from mobi-service via page_builder
chmod 775 ../Basic
chmod 775 ../Touch
chmod 775 ../Webkit

# get mysql params from LIBDIR/config.php
echo "reading mysql params from ${LIBDIR}config.php"
MYSQL_USER=`grep "define(\"MYSQL_USER" ${LIBDIR}config.php`
MYSQL_USER=${MYSQL_USER%%\');*}
MYSQL_USER=${MYSQL_USER#*\'}
MYSQL_PASS=`grep "define(\"MYSQL_PASS" ${LIBDIR}config.php`
MYSQL_PASS=${MYSQL_PASS%%\');*}
MYSQL_PASS=${MYSQL_PASS#*\'}
MYSQL_DBNAME=`grep "define(\"MYSQL_DBNAME" ${LIBDIR}config.php`
MYSQL_DBNAME=${MYSQL_DBNAME%%\');*}
MYSQL_DBNAME=${MYSQL_DBNAME#*\'}

# create_web_tables.sql drops and recreates the table mobi_web_page_views
echo "[re]creating web-only tables in mysql"
mysql --user=$MYSQL_USER --password=$MYSQL_PASS $MYSQL_DBNAME < create_web_tables.sql

# generate_stats_from_logs.php repopulates the table
cp /var/log/httpd/access_log /tmp
echo "regenerating page view statistics from apache logs"
php generate_stats_from_logs.php
