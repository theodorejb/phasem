#!/bin/bash

# this should be run from the production server after copying a new build archive
# this file must have unix line endings

set -e # exit when any command fails

# unzip to new incremented folder
date=$(date +%Y_%m_%d_%H%M%S)
folder="/var/www/phasem_$date"
unzip -q ~/phasem.zip -d $folder

# copy current config file
cp /var/www/phasem/AppConfig.php $folder/AppConfig.php

# copy nginx configuration if it doesn't exist yet
cp --no-clobber $folder/phasem_prod.conf /etc/nginx/sites-available/phasem

# create/update nginx symlink to new folder
ln -sfn $folder /var/www/phasem
sudo systemctl reload nginx

# clean up old folders
find /var/www -type d -name "phasem_*" ! -name "phasem_$date" -prune -exec rm -r {} \;
