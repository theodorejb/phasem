#!/bin/bash

# in order to execute via npm script on Windows, first run:
# npm config set script-shell "C:\\Program Files\\git\\bin\\bash.exe"

set -e # exit when any command fails

rm -f deploy/archive.zip

composer install --no-dev --optimize-autoloader

# requires 7-Zip to be installed and directory added to path
7z a deploy/archive.zip @deploy/include_files.txt -xr!.git

scp deploy/archive.zip root@phasem.com:~/phasem.zip
scp deploy/deploy.sh root@phasem.com:~/deploy_phasem.sh

echo "You can now connect to the server and run bash ~/deploy_phasem.sh"
