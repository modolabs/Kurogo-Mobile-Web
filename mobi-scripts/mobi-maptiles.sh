#!/bin/bash

NOW=`date +%s`
BINDIR="/usr/local/bin"       # where this script goes
DATADIR="/var/local/maptiles" # where "tile" directory should go
TILEDIR=$DATADIR/tile2
PHPSCRIPT=$BINDIR/mobi-maptiles-download.php
CHECKSUM=$DATADIR/export.md5

if [ "$1" = "--force" ]; then
    php $PHPSCRIPT --force
else
    php $PHPSCRIPT
fi

if [ "$?" -ne 0 ]; then
    if [ -f "$CHECKSUM" ]; then
	echo "removing $CHECKSUM"
	rm $CHECKSUM
    fi
    echo "$PHPSCRIPT failed, exiting"
    exit $?
fi

if [ ! -f "$CHECKSUM" ]; then 
    echo "$PHPSCRIPT failed to create checksum file, exiting"
    exit 1
else
    CACHETIME=`stat -c %Y $CHECKSUM 2>/dev/null`
    if [ $CACHETIME -lt $NOW ]; then 
	echo "checksum file not modified, our job is done"
	exit 0
    fi
fi

# from here on we create a temporary copy of the entire tile directory
# and overwrites the original tiles after they have been optimized

# benchmark stats per level... 
#
# lvl   time  pre   tile  post  tile  redux  cols  rows
#  13   0:00  360K   45K  164K   20K  54.4%     2     4
#  14   0:02  1.1M   39K  492K   18K  55.3%     4     7
#  15   0:08  2.4M   25K  1.3M   14K  45.8%     8    12
#  16   0:20  4.9M   16K  3.1M   10K  36.7%    14    22
#  17   0:55   12M   10K  8.2M  6.9K  31.7%    27    44
#  18   3:03   31M  6.7K   24M  5.2K  22.6%    53    87
#  19  11:04   91M  5.0K   75M  4.1K  17.6%   105   174
#

# assume we're in the same directory that contains the directory `tile`\
# and the executable pngcrush binary is also here

TMPDIR=/tmp/tile${RANDOM}

# this takes about 40 seconds
echo "`date +%H:%M:%S` copying files"
cp -r $TILEDIR $TMPDIR

# all tile files follow the convention level/y/x
# where level, y, and x are integers
for LEVEL in `ls $TMPDIR`; do
    echo "`date +%H:%M:%S` compressing tiles in level $LEVEL"
    for Y in `ls $TMPDIR/$LEVEL/`; do
        # suppress errors from using `*` on empty directories
	$BINDIR/pngcrush -q -d $TILEDIR/$LEVEL/$Y $TMPDIR/$LEVEL/$Y/* > /dev/null 2>&1 
    done
done

rm -rf $TMPDIR

echo "`date +%H:%M:%S` complete"
