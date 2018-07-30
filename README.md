# Phasem - a PHP, Angular, Slim, Nginx, and MySQL starter project

Get your app off the ground quickly with routing, authentication,
and a RESTful API.

## Getting started

1. Download and unzip this project
2. Install PHP 7.1+, Composer, Node.js, MySQL, and nginx.
3. Start MySQL and enable MySQL extension in php.ini
4. Include phasem.conf file in nginx.conf http block
5. From nginx directory, run `start nginx` and `php-cgi -b 127.0.0.1:9000`
6. Run `composer install` and `npm install`
7. Run PHP tests via `vendor\bin\phpunit`

## Features

* Back-end REST API built with Slim Framework
* Front-end built with Angular and TypeScript
* TypeScript code is standardized via TSLint
* User registration
* Token-based authentication
* Account settings page (change name, email, and password)
* Users are prompted to reload the page when a front-end update is available

## Todo

- [ ] Reset password via email
- [ ] Two-factor authentication
- [ ] Offline support
