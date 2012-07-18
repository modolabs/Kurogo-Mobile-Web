#!/bin/bash
# Arguments = -f file -v

usage()
{
cat << EOF
usage: $0 options

This script will untar a file and run a KurogoShell command.

OPTIONS:
    -h      Show this help message
    -f      File to untar
    -v      Verbose
EOF
}

FILE=
VERBOSE=

# log only if in verbose mode
function log () {
    if [[ $VERBOSE -eq 1 ]]; then
        echo "$@"
    fi
}

# get options
while getopts “hf:v” OPTION
do
    case $OPTION in
        h)
            usage
            exit 1
            ;;
        f)
            FILE=$OPTARG
            ;;
        v)
            VERBOSE=1
            ;;
        ?)
            usage
            exit
            ;;
    esac
done

if [[ -z $FILE ]]; then
    usage
    exit 1
fi

log "Running $0"

if [ ! -f $FILE ]; then
    log "$FILE: No such file"
    exit 1
else
    log "Extracting file $FILE..."
fi

# extract the file given by the first arguement
# to the root directory, removing the container folder
tar --strip-components 1 -xf "$FILE" -C ../

# if tar returned a non-zero error code exit with that code
if [[ $? -ne 0 ]]; then
    exit $?
fi

log "Extraction complete"

# run the core deployPostFlight command
if [[ $VERBOSE -eq 1 ]]; then
    ../lib/KurogoShell core deployPostFlight -v
else
    ../lib/KurogoShell core deployPostFlight
fi

# return the result of deployPostFlight
exit $?
