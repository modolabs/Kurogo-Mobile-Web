#!/bin/bash

rm -rf /opt/mitmobile/cache/ACADEMIC_CALENDAR/*
rm -rf /opt/mitmobile/cache/ARCGIS/*
rm -rf /opt/mitmobile/cache/DINING/*
rm -rf /opt/mitmobile/cache/GAZETTE/*
rm -rf /opt/mitmobile/cache/GAZETTE_SEARCH/*
rm -rf /opt/mitmobile/cache/STELLAR_COURSE/*
rm -rf /opt/mitmobile/cache/STELLAR_FEEDS/*
rm -rf /opt/mitmobile/cache/TRUMBA_CALENDAR/*
rm -rf /opt/mitmobile/cache/WMSCapabilities.xml

rm -rf /var/www/html/api/newsimages/*

# Reload dining data
sudo -u apache cp /home/huds/upload/menu.csv /opt/mitmobile/static/menu.csv
