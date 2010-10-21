#!/bin/bash

cp constants.php.init constants.php
cp config.php.init config.php
chmod 775 cache

### modify config file
# read parameters to populate dummy variables in config.php

# mysql account (should be www)
read -p "MySQL username: " MYSQL_USER
read -s -p "MySQL password: " MYSQL_PASS
echo
read -p "MySQL db name: " MYSQL_DBNAME

# oracle params for techcash
read -p "TechCASH Oracle username: " TECHCASH_ORACLE_USER
read -s -p "TechCASH Oracle password: " TECHCASH_ORACLE_PASS
echo
read -p "TechCASH Oracle db alias: " TECHCASH_ORACLE_DB

# warehouse params for techcash
read -p "Data Warehouse Oracle username: " WAREHOUSE_ORACLE_USER
read -s -p "Data Warehouse Oracle password: " WAREHOUSE_ORACLE_PASS
echo
read -p "Data Warehouse Oracle db alias: " WAREHOUSE_ORACLE_DB

# drupal mysql account
read -p "Drupal MySQL username: " DRUPAL_USER
read -s -p "Drupal MySQL password: " DRUPAL_PASS
echo
read -p "Drupal MySQL db name: " DRUPAL_DB

# start editing stuff (so if user messes up before this point
# they can start over... not that it makes a difference)
sed -i 's/mysql_user/'${MYSQL_USER}'/g' config.php
sed -i 's/mysql_pass/'${MYSQL_PASS}'/g' config.php
sed -i 's/mysql_dbname/'${MYSQL_DBNAME}'/g' config.php
sed -i 's/techcash_oracle_user/'${TECHCASH_ORACLE_USER}'/g' config.php
sed -i 's/techcash_oracle_pass/'${TECHCASH_ORACLE_PASS}'/g' config.php
sed -i 's/techcash_oracle_db/'${TECHCASH_ORACLE_DB}'/g' config.php
sed -i 's/warehouse_oracle_user/'${WAREHOUSE_ORACLE_USER}'/g' config.php
sed -i 's/warehouse_oracle_pass/'${WAREHOUSE_ORACLE_PASS}'/g' config.php
sed -i 's/warehouse_oracle_db/'${WAREHOUSE_ORACLE_DB}'/g' config.php
sed -i 's/drupal_user/'${DRUPAL_USER}'/g' config.php
sed -i 's/drupal_pass/'${DRUPAL_PASS}'/g' config.php
sed -i 's/drupal_db/'${DRUPAL_DB}'/g' config.php

### edit constants.php

LIBDIR=`grep "define(\"LIBDIR" constants.php`
LIBDIR=${LIBDIR#*\'}
LIBDIR=${LIBDIR%%\');*}

# assume LIBDIR is the same directory as the one where people run
# this script... i see no reason anyone would do otherwise
if [[ "$LIBDIR" != "$PWD" ]]; then
    sed -i 's/'${LIBDIR}'/'${PWD}'/g' constants.php
fi

# figure out where people want cache files
CACHE_DIR=`grep "define(\"CACHE_DIR" constants.php`
CACHE_DIR=${CACHE_DIR#*\'}
CACHE_DIR=${CACHE_DIR%%\');*}

PWD_CACHE=$PWD/cache/
echo "cache files (used by the Shuttle Schedule and Libraries)"
echo "  are currently being written to $CACHE_DIR"
read -p "use cache in working directory ($PWD_CACHE) instead? [y] " USE_PWD
if [[ "$USE_PWD" = n ]]; then
    echo "using $CACHE_DIR"
else
    echo "using $PWD_CACHE"
    PWD_CACHE=${PWD_CACHE//\//\\\/}
    CACHE_DIR=${CACHE_DIR//\//\\\/}
    sed -i 's/'${CACHE_DIR}'/'${PWD_CACHE}'/g' constants.php
fi

# mysql data for stellar should be migrated directly
# from the mysql export
read -p "re-populate the stellar tables now? [n]: " REPOPULATE
if [[ "$REPOPULATE" = y ]]; then
    echo "[re]creating stellar course/subject lookup tables"
    mysql --user=$MYSQL_USER --password=$MYSQL_PASS $MYSQL_DBNAME < stellar.SQL
    php save_stellar.php
fi
