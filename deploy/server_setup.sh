# starting with Ubuntu 20.04 x64 image

# Add PHP ppa and install PHP
sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.0-fpm php8.0-xml php8.0-mysql php8.0-mbstring

# install nginx and zip/unzip
sudo apt install nginx
sudo apt install zip unzip

# set up email
sudo apt install postfix
# choose "Internet Site" for mail configuration.

# install MySQL 8
# see https://www.digitalocean.com/community/tutorials/how-to-install-the-latest-mysql-on-ubuntu-18-04
cd /tmp
# get link from https://dev.mysql.com/downloads/repo/apt/
curl -OL https://dev.mysql.com/get/mysql-apt-config_0.8.15-1_all.deb
sudo dpkg -i mysql-apt-config*
# arrow down to OK to keep defaults
sudo apt update
rm mysql-apt-config* # cleanup
sudo apt install mysql-server
# after setting root password, select option to use strong password encryption
mysql_secure_installation # yes to everything

# remove symlink to disable default site
rm /etc/nginx/sites-enabled/default

# create/deploy initial production build
npm run build && npm run deploy # run locally, not on server

# set up MySQL user, database, and schema
mysql -u root -p

CREATE DATABASE phasem CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'phasem'@'localhost' IDENTIFIED BY ''; # replace empty string with real password
GRANT ALL PRIVILEGES ON phasem.* TO 'phasem'@'localhost';

# if creating new instance of app:
source /var/www/phasem/schema.sql;
quit # exit MySQL

# otherwise restore from backup:
scp phasemdb_[date].zip user@example.com:~/phasemdb_[date].zip
unzip phasemdb_[date].zip
mysql -u [username] -p phasem < phasemdb_[date].sql

# configure site
cd /var/www/phasem
touch AppConfig.php
# Class should extend php/Config.php. Set dev env to false for production.

# enable site
ln -sfn /etc/nginx/sites-available/phasem /etc/nginx/sites-enabled/
sudo systemctl reload nginx

# enable HTTPS - see https://certbot.eff.org/lets-encrypt/ubuntufocal-nginx
sudo apt-get update
sudo apt-get install software-properties-common
sudo add-apt-repository universe
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx
sudo certbot --nginx
