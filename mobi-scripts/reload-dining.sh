#!/bin/bash

# Crontab entry: 15 06 * * 0 /opt/mitmobile/bin/reload-dining.sh

cp /home/huds/upload/menu.csv /opt/mitmobile/static/menu.csv

# This is supposed to be run by user apache, but it might be run by someone
# doing sudo ./reload-dining.sh --> so make sure we set the permissions properly
# in that case.

chown apache /opt/mitmobile/static/menu.csv
chgrp apache /opt/mitmobile/static/menu.csv