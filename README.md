# LapStore
Magento e-commerce website for selling laptops and electronic devices

# 1.Updating repositories

`sudo apt-get update && sudo apt-get upgrade`

# 2.Install Nginx  & Configure Firewall

`sudo apt-get install nginx`

After installing Nginx, the commands below will be helpful for Nginx service to always start up with the server boots.


```sudo systemctl stop nginx.service
sudo systemctl start nginx.service
sudo systemctl enable nginx.service
```

## Configuring Firewall

```sudo ufw app list
sudo ufw allow in "Nginx Full"
sudo ufw allow "Nginx HTTP"
sudo ufw allow "Nginx HTTPS"
sudo ufw allow OpenSSH
sudo ufw enable
```
Enter "y"(yes)

`sudo ufw status`

# 3.Install MySQL
`sudo apt update && sudo apt install mysql-server`

Enter "y"

`sudo service mysql status`

## MySQL Secure Installation

`sudo mysql_secure_installation`

**Hit "ENTER Button"**
Enter Strong Password

Press Y and hit enter

-"-  n and hit enter

-"-  Y and hit enter

-"-  Y and hit enter

`sudo mysqladmin -p -u root version`

# 4.Installing Repositories & php7.3

```
sudo apt-get install software-properties-common

sudo add-apt-repository ppa:ondrej/php

sudo apt-get update

sudo apt-get install php7.3 php7.3-mysql php7.3-fpm php7.3-soap php7.3-bcmath php7.3-xml php7.3-mbstring php7.3-xsl php-xdebug php7.3-xmlrpc php7.3-gd php7.3-common php7.3-cli php7.3-curl php7.3-intl php7.3-zip php7.3-opcache php7.3-json php-imagick


sudo vim /etc/php/7.3/fpm/php.ini
```

```
upload_max_filesize = 100M
max_file_uploads = 60

short_open_tag = On
memory_limit = 2048M
```
As per requirement
```
max_execution_time = 18000
date.timezone = America/Chicago
```

`sudo systemctl reload php7.3-fpm`


# 5.Creating a MySQL Database and User for MagentoDB

```
sudo mysql
CREATE USER store_admin@localhost IDENTIFIED BY 'fr0st3rVId4u';
GRANT ALL PRIVILEGES ON * . * TO store_admin@localhost;

CREATE DATABASE lapstore DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

GRANT ALL ON store_admin.* TO store_admin@localhost IDENTIFIED BY 'db_password';

FLUSH PRIVILEGES;

exit;
```


# 6. Editing Some IMP Files as per our Magento CMS Installation --

`sudo vim /etc/nginx/sites-available/default`


Uncomment some lines, add "index.php" and change "php7.0-fpm to php7.2-fpm"
```
location ~ \.php$ {
	try_files $fastcgi_script_name = 404;
	include fastcgi_params;
	fastcgi_index index.php;
	fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
	fastcgi_param DOCUMENT_ROOT $realpath_root;
	fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
}
```

Use this **"ctrl+c -->> :wq -->> hit enter"** to save edited file

`sudo vim /etc/nginx/nginx.conf`

Find the "server_names_hash_bucket_size" directive and remove the # symbol to uncomment the line:

`client_max_body_size 100M;`

```
sudo nginx -t

sudo systemctl reload nginx
```

**Creating magento setup files directory**

```
sudo mkdir /var/www/magento_dir

sudo mkdir /var/www/magento_dir/html
```

# 7. Clone this repository to your Magento directory
```
cd /var/www/magento_dir/html

git clone git@github.com:vuthanglong/lapstore.git
```


# 8.Creating a user for Magento Installation....best practice secured installation
```
sudo adduser ur_username

sudo usermod -g www-data ur_username

sudo chown ur_username:www-data /var/www/magento_dir/html/

cd /var/www/magento_dir/html
```

# 9.Downloading Latest Magento 2 Setup file in "magento_dir/html" folder

**You must switch to created user**
 
```
su ur_username

find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + && find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + && chown -R :www-data . && chmod u+x bin/magento

exit
```
# 10.Nginx Configuration:

`sudo vim /etc/nginx/sites-available/magento_dir.conf`

**Add below content as it is and replace the targeted content
```
upstream fastcgi_backend {
  server unix:/var/run/php/php7.3-fpm.sock;
}

server {
    listen 80;
    listen [::]:80;

    server_name ur_domain_name *.ur_domain_name;
    index index.php;

    set $MAGE_ROOT /var/www/magento_dir/html;
    set $MAGE_MODE developer;

    #access_log /var/log/nginx/ur_domain_name-access.log;
    #error_log /var/log/nginx/ur_domain_name-error.log;

    include /var/www/magento_dir/html/nginx.conf.sample;
}
```
**You can use your custome domain name or obtained IP as it is as a server name


**Creating symbolic link**
```
sudo ln -s /etc/nginx/sites-available/magento_dir.conf /etc/nginx/sites-enabled/

sudo nginx -t

sudo systemctl reload nginx
```
# 11.Installing Magento2 through Browser

Go to browser and reload the index page



# Optional

**After reloading the page, if you get the blank page, follow the below command lines,**

*switch to created user*

```su ur_username
php bin/magento setup:install --base-url=https://ur_domain_name/ \
        --base-url-secure=https://ur_domain_name/ \
        --admin-firstname="ur_name" \
        --admin-lastname="ur_last_name" \
        --admin-email="ur_email_id@gmail.com" \
        --admin-user="admin_user" \
        --admin-password="1234@Abcd" \
        --db-name="db_name" \
        --db-host="localhost" \
        --db-user="db_user" \
        --currency=INR \
        --timezone= Asia/Kolkata \
        --use-rewrites=1 \
        --db-password="db_password"
```
--------------------------------------------------------------

# 12.Installing Cron for Magento:
*switch to created user*
```
su ur_username

bin/magento cron:install

bin/magento c:c
```
**IP and custome domain name mapping**

`sudo nano /etc/nginx`

**********************************************************


# 13.Installing Sample Data

*just copy paste below commands and at last run*


```
bin/magento deploy:mode:set developer

rm -rf generated/metadata/* generated/code/*

bin/magento c:c

bin/magento sampledata:deploy

Public Key: ur_publicKey
Private Key: ur_privateKey

bin/magento setup:upgrade

bin/magento c:c
```

*don't use "https:" if you get redirected to https:// then u should clear cache data of ur browser*
