<?php

/*
 * Default configuration. Any of these settings can be overridden in config.user.php
 */

return [
    'db' => [
        'host'     => '127.0.0.1',
        'username' => '', // set in config.user.php
        'password' => '', // set in config.user.php
        'database' => 'phasem',
    ],
    'devMode' => true,
    'encryptionKey' => '', // generate with vendor\bin\generate-defuse-key and set in config.user.php
];
