#!/bin/bash
# Arguments = -f file -u user -v -q

usage()
{
cat << EOF
usage: $0 options

This script will untar a file and run a KurogoShell command.

OPTIONS:
    -h      Show this help message
    -f      File to untar
    -u      User to run commands as
    -v      Verbose
    -q      Silent
EOF
}

ROOTDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
USER=
FILE=
HASFILE=0
VERBOSE=0
QUIET=0

# log only if in verbose mode
function log () {
    if [[ $VERBOSE -eq 1 ]]; then
        output "$@"
    fi
}

# echo only if not in quiet mode
function output () {
    if [[ $QUIET -ne 1 ]]; then
        echo "$@"
    fi
}

# get options
while getopts “hu:f:vq” OPTION
do
    case $OPTION in
        h)
            usage
            exit 1
            ;;
        f)
            FILE=$OPTARG
            HASFILE=1
            ;;
        u)
            USER=$OPTARG
            ;;
        v)
            VERBOSE=1
            ;;
        q)
            QUIET=1
            VERBOSE=0
            ;;
        ?)
            usage
            exit
            ;;
    esac
done

log "Running $0"

if [[ -z $USER ]]; then
    output "You must specify a user with -u"
    usage
    exit 1
fi

# If the -f parameter was used
if [[ $HASFILE -eq 1 ]]; then
    # Check if the file is an empty string
    if [[ -z $FILE ]]; then
        output "File must not be blank"
        exit 1
    fi
    # Check if the file exists
    if [ ! -f $FILE ]; then
        output "$FILE: No such file"
        exit 1
    fi
    
    log "Extracting file $FILE..."

    # Extract the file given by the first arguement
    # to the root directory, removing the container folder
    if [[ $QUIET -eq 1 ]]; then
        su -c 'tar --strip-components 1 -xf "'"$FILE"'" -C "'"$ROOTDIR"'" > /dev/null 2>&1' -s '/bin/sh' "$USER"
        ERROR=$?
    else
        if [[ $VERBOSE -eq 1 ]]; then
            su -c 'tar --strip-components 1 -xvf "'"$FILE"'" -C "'"$ROOTDIR"'"' -s '/bin/sh' "$USER"
            ERROR=$?
        else
            su -c 'tar --strip-components 1 -xf "'"$FILE"'" -C "'"$ROOTDIR"'"' -s '/bin/sh' "$USER"
            ERROR=$?
        fi
    fi

    # if tar returned a non-zero error code exit with that code
    if [[ $ERROR -ne 0 ]]; then
        exit $ERROR
    fi

    log "Extraction complete"
fi

# run the core deployPostFlight command
if [[ $QUIET -eq 1 ]]; then
    su -c '"'"$ROOTDIR"'"/lib/KurogoShell core deployPostFlight > /dev/null 2>&1' -s '/bin/sh' "$USER"
    ERROR=$?
else
    if [[ $VERBOSE -eq 1 ]]; then
        su -c '"'"$ROOTDIR"'"/lib/KurogoShell core deployPostFlight -v' -s '/bin/sh' "$USER"
        ERROR=$?
    else
        su -c '"'"$ROOTDIR"'"/lib/KurogoShell core deployPostFlight' -s '/bin/sh' "$USER"
        ERROR=$?
    fi
fi

# return the result of deployPostFlight
exit $ERROR
