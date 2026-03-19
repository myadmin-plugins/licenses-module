<?php

declare(strict_types=1);

/**
 * PHPUnit test bootstrap file.
 *
 * Loads the Composer autoloader and includes the API functions file
 * so that global functions are available to the test suite.
 */

// Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Define NORMAL_BILLING constant if not already defined (required by Plugin::$settings)
if (!defined('NORMAL_BILLING')) {
    define('NORMAL_BILLING', 0);
}

// Define MYSQL_ASSOC constant if not already defined (used in api.php)
if (!defined('MYSQL_ASSOC')) {
    define('MYSQL_ASSOC', 1);
}

// Include the API functions file so global functions are available
require_once dirname(__DIR__) . '/src/api.php';
