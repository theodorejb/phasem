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

if (class_exists(AppConfig::class)) {
    App::setConfig(new AppConfig());
}
