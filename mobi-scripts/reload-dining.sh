#!/bin/bash

# Crontab entry: 15 06 * * 0 /opt/mitmobile/bin/reload-dining.sh

# The sudo is here so that people running this script as "sudo ./reload-dining.sh"
# don't accidentally make the file owned by root (breaking future attempts to
# copy by apache).
sudo -u apache cp /home/huds/upload/menu.csv /opt/mitmobile/static/menu.csv