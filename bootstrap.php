<?php

/*
 * Bootstrap the application
 */

declare(strict_types=1);

use Phasem\App;

date_default_timezone_set('UTC');

// Composer autoloading
require 'vendor/autoload.php';

App::setRequestTime();

// read config
$config = require 'config.php';

if (is_readable(__DIR__ . '/config.user.php')) {
    $localConfig = require 'config.user.php';
    $config = array_replace_recursive($config, $localConfig);
}

App::setConfig($config);

unset($config, $localConfig);
