#!/bin/bash

NOW=`date +%s`
DATADIR=/opt/mitmobile/maptiles # where "tile" directory should go
PHPSCRIPT=/opt/mitmobile/bin/mobi-maptiles-download.php
CHECKSUM_FINAL=$DATADIR/export.md5
CHECKSUM_TEMP=$DATADIR/temp-export.md5
PNGCRUSH=/opt/mitmobile/bin/pngcrush

if [ "$1" = "--force" ]; then
    php $PHPSCRIPT --force
else
    php $PHPSCRIPT
fi

if [ "$?" -ne 0 ]; then
    if [ -f "$CHECKSUM_TEMP" ]; then
	echo "removing $CHECKSUM_TEMP"
	rm $CHECKSUM_TEMP
    fi
    echo "$PHPSCRIPT failed, exiting"
    exit $?
fi

if [ ! -f "$CHECKSUM_TEMP" ]; then 
    echo "$PHPSCRIPT failed to create checksum file, exiting"
    exit 1
else
    if [[ -f $CHECKSUM_FINAL && "$1" != "--force" ]]; then
        if [ `cat $CHECKSUM_FINAL` == `cat $CHECKSUM_TEMP` ]; then 
	    echo "checksum not modified, our job is done"
            rm $CHECKSUM_TEMP
	    exit 0
        fi
    fi
fi


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
# and the executable pngcrush is in the path

RAW_DATADIR=$DATADIR/raw

# all tile files follow the convention level/y/x
# where level, y, and x are integers
for LEVEL in `ls $RAW_DATADIR`; do
    echo "`date +%H:%M:%S` compressing tiles in level $LEVEL"
    if [ ! -d $DATADIR/crushed/$LEVEL ]; then
        mkdir $DATADIR/crushed/$LEVEL
    fi

    for Y in `ls $RAW_DATADIR/$LEVEL/`; do
        if [ ! -d $DATADIR/crushed/$LEVEL/$Y ]; then
	    mkdir $DATADIR/crushed/$LEVEL/$Y
        fi

        # suppress errors from using `*` on empty directories
	$PNGCRUSH -q -d $DATADIR/crushed/$LEVEL/$Y $RAW_DATADIR/$LEVEL/$Y/* > /dev/null 2>&1 
    done
done

mv $CHECKSUM_TEMP $CHECKSUM_FINAL

# cleanup
if [ -f $PNGCRUSH ]; then
   rm -r $RAW_DATADIR/*
else
   cp -r $RAW_DATADIR/* $DATADIR/crushed
fi

echo "`date +%H:%M:%S` complete"
