# Phasem - a PHP, Angular, Slim, Nginx, and MySQL starter project

Get your app off the ground quickly with routing, authentication,
and a RESTful API.

## Getting started

1. Download and unzip this project
2. Install PHP 8.0+, Composer, Node.js, MySQL, and nginx.
3. Start MySQL and enable MySQL extension in php.ini
4. Include phasem.conf file in nginx.conf http block
5. From nginx directory, run `start nginx` and `php-cgi -b 127.0.0.1:9000`
6. Run `composer install`, `npm install`, and `npm run dev`
7. Run PHP tests via `vendor\bin\phpunit`
8. Run static analysis type checks via `vendor\bin\psalm`

## Deploying to production

1. Use deploy/server_setup.sh to configure a production Ubuntu VPS.
2. Run `npm run build`, then `npm run deploy` and follow the prompts.

## Features

* Back-end REST API built with Slim Framework
* Front-end built with Angular and TypeScript
* TypeScript/Angular code is standardized via ESLint
* User registration
* Token-based authentication
* Account settings page (change name, email, and password)
* Two-factor authentication with recovery codes
* Zero downtime deployment
* Users are prompted to reload the page when a front-end update is available

## Todo

- [ ] Track invalid login attempts with IP and headers
- [ ] Rate limiting for incorrect passwords
- [ ] Use device cookies to mitigate brute force attacks
- [ ] Show password strength when registering and changing password
- [ ] Allow users to view active sessions with login date, last activity date, and IP address
- [ ] Display account security events (login, 2FA completion, recovery code used, invalid login/2FA)
- [ ] Reset password via email (don't leak valid emails)
- [ ] Progressive web app manifest

## DB backup

```shell script
date=$(date +%Y_%m_%d)
file="phasemdb_$date"
mysqldump -u $USER -p phasem > "$file.sql"
zip "$file" "$file.sql"
echo "Run scp $USER@example.com:~/$file.zip $file.zip from the destination machine."
```
