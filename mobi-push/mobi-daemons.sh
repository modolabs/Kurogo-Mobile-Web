#!/bin/bash

source configure_paths.sh

DAEMON_LIST="apns_push apns_feedback emergency my_stellar shuttle"
SCRIPT_DIR=$DOCUMENT_ROOT/api/push # location of php files to be started
DAEMON_DIR=$AUX_PATH/pushd         # location of pid/command files

stop() {
    for DAEMON_PROG in $DAEMON_LIST; do
	echo "stopping $DAEMON_PROG"
	echo "stop" > $DAEMON_DIR/$DAEMON_PROG/command.txt
    done
}

start() {
    # name of php script must be in format ${DAEMON_PROG}_daemon.php
    for DAEMON_PROG in $DAEMON_LIST; do
	echo "starting $DAEMON_PROG"
	$SCRIPT_DIR/${DAEMON_PROG}_daemon.php --background > /dev/null 2>>$AUX_PATH/logs/$DAEMON_PROG\_error.log
    done
}

restart() {
    stop

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

    start
}

status() {
    for DAEMON_PROG in $DAEMON_LIST; do
	PIDFILE=$DAEMON_DIR/$DAEMON_PROG/$DAEMON_PROG.pid
	if [ -f $PIDFILE ]; then
	    PID=`cat $PIDFILE`
	    if [ -d /proc/$PID ]; then
		echo "$DAEMON_PROG is running"
	    else
		echo "$DAEMON_PROG is not running, but pid file exists"
	    fi
	else
	    PID=`pgrep $DAEMON_PROG`
	    if [ -z "$PID" ]; then
		echo "$DAEMON_PROG is not running"
	    else
	        echo "$DAEMON_PROG is running, but no pid file exists"
	    fi
	fi
    done
}

case "$1" in
start)
    start
    ;;
stop)
    stop
    ;;
restart)
    restart
    ;;
status)
    status
    ;;
*)
    echo $"Usage: $0 {start|stop|status|restart}"
    exit 1
esac


