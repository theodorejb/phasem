# starting with Ubuntu 18.04 x64 image

# Add PHP ppa and install PHP
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.3-fpm
sudo apt-get install php7.3-xml
sudo apt-get install php7.3-mysql
sudo apt-get install php7.3-mbstring

# install nginx and unzip
apt install nginx
apt install unzip

# install MySQL 8
# see https://www.digitalocean.com/community/tutorials/how-to-install-the-latest-mysql-on-ubuntu-18-04
cd /tmp
# get link from https://dev.mysql.com/downloads/repo/apt/
curl -OL https://dev.mysql.com/get/mysql-apt-config_0.8.13-1_all.deb
sudo dpkg -i mysql-apt-config*
# arrow down to OK to keep defaults
sudo apt update
rm mysql-apt-config* # cleanup
sudo apt install mysql-server
# after setting root password, select mysql_native_password password hashing method since PHP doesn't support caching_sha2_password yet
mysql_secure_installation # yes to everything

# remove symlink to disable default site
rm /etc/nginx/sites-enabled/default

# create/deploy initial production build
npm run build-prod && npm run deploy

# set up MySQL user, database, and schema
mysql -u root -p
CREATE USER 'phasem'@'localhost' IDENTIFIED BY ''; # replace empty string with real password
source /var/www/phasem/schema.sql;
quit

# configure site
cd /var/www/phasem
cp config.php config.user.php
nano config.user.php
# edit file to set appropriate settings

# enable site
ln -sfn /etc/nginx/sites-available/phasem /etc/nginx/sites-enabled/
sudo service nginx restart

# enable HTTPS - see https://certbot.eff.org/lets-encrypt/ubuntubionic-nginx
sudo apt-get update
sudo apt-get install software-properties-common
sudo add-apt-repository universe
sudo add-apt-repository ppa:certbot/certbot
sudo apt-get update
sudo apt-get install certbot python-certbot-nginx
sudo certbot --nginx
