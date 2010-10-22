#!/bin/bash

# apache's document root env variable
export DOCUMENT_ROOT=/var/www/html/mobi-web

DAEMON_LIST="apns_push apns_feedback emergency my_stellar shuttle"
SCRIPT_DIR=$DOCUMENT_ROOT/api/push # location of php files to be started
DAEMON_DIR=$WSETCDIR/pushd         # location of pid/command files

if [ "$1" = "stop" -o "$1" = "restart" ]; then
   for DAEMON_PROG in $DAEMON_LIST; do
      echo "stop" > $DAEMON_DIR/$DAEMON_PROG/command.txt
   done
fi

if [ "$1" = "restart" ]; then
  echo "waiting 8 seconds.. to give scripts time to quit"
  sleep 8

  for DAEMON_PROG in $DAEMON_LIST; do
    PIDFILE=$DAEMON_DIR/$DAEMON_PROG/$DAEMON_PROG.pid
    if [ -f $PIDFILE ]; then
      echo "kill $DAEMON_PROG daemon"
      kill -9 `cat $PIDFILE`
    fi
  done

  sleep 3
fi

if [ "$1" = "start" -o "$1" = "restart" ]; then
   echo "starting: $DAEMON_LIST"
   # name of php script must be in format ${DAEMON_PROG}_daemon.php
   for DAEMON_PROG in $DAEMON_LIST; do
     $SCRIPT_DIR/${DAEMON_PROG}_daemon.php --background > /dev/null 2>&1
   done
fi
