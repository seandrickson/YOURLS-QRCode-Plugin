<?php

/*
 * Plugin Name: Sean's QR Code Short URLs
 * Plugin URI: https://github.com/seandrickson/YOURLS-QRCode-Plugin
 * Description: Allows you to get the QR code by simply clicking on a button in the Admin area (or by adding <tt>.qr</tt> to the end of the keyword.) Works with <a href="https://github.com/seandrickson/YOURLS-Case-Insensitive">Case-Insensitive</a> to create smaller QR codes.
 * Version: 1.6
 * Author: Sean Hendrickson
 * Author URI: https://github.com/seandrickson
 */

declare(strict_types=1);

// No direct call
if (!defined('YOURLS_ABSPATH')) {
    die();
}

/**
 * The following configuration constants can be overridden
 * by defining them in user/config.php, or with environment
 * variables of the same name (handy with Docker)
 */

/**
 * The value for a config constant: an environment variable of the same name
 * when it's set and valid, otherwise the default.
 * (Runs before the PHP version check, so keep it compatible with old PHP.)
 *
 * @param string   $name    the constant name, e.g. 'SEAN_QR_SCALE'
 * @param bool|int $default the value to use when the variable isn't set or valid
 *
 * @return bool|int
 */
function sean_qr_config($name, $default)
{
    $env = getenv($name);
    if (!is_string($env) || $env === '') {
        return $default;
    }
    if (is_bool($default)) {
        $parsed = filter_var($env, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    } else {
        $parsed = filter_var($env, FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]);
    }
    return $parsed === null ? $default : $parsed;
}

// Each option: user/config.php wins, then the environment variable, then the default.

// size of a QR code pixel (SVG, IMAGE_*), HTML -> via CSS
defined('SEAN_QR_SCALE') || define('SEAN_QR_SCALE', sean_qr_config('SEAN_QR_SCALE', 5));

// size in QR modules, multiply with QROptions::$scale for pixel size
defined('SEAN_QR_LOGO_SPACE') || define('SEAN_QR_LOGO_SPACE', sean_qr_config('SEAN_QR_LOGO_SPACE', 13));

// should we include a QR code in the share boxes
defined('SEAN_QR_ADD_TO_SHAREBOX')
    || define('SEAN_QR_ADD_TO_SHAREBOX', sean_qr_config('SEAN_QR_ADD_TO_SHAREBOX', true));

// outside margin of QR code in 'virtual' pixels:
defined('SEAN_QR_MARGIN') || define('SEAN_QR_MARGIN', sean_qr_config('SEAN_QR_MARGIN', 4));

// should we place the logo (logo.png) in the middle of the QR code
defined('SEAN_QR_LOGO') || define('SEAN_QR_LOGO', sean_qr_config('SEAN_QR_LOGO', true));

// should the QR Code button in the admin list always be visible,
// or only on hover like the other action buttons
defined('SEAN_QR_BUTTON_ALWAYS_VISIBLE')
    || define('SEAN_QR_BUTTON_ALWAYS_VISIBLE', sean_qr_config('SEAN_QR_BUTTON_ALWAYS_VISIBLE', true));

/*
 * Check requirements before loading anything that needs them, so a missing
 * `composer install` or an old PHP shows an admin notice instead of a fatal error.
 * PHP parses this whole file before running the check, so keep this file
 * parseable on PHP 7.0 (CI checks this): modern code goes in hooks.php and src/.
 */
$sean_qr_error = '';
if (version_compare(PHP_VERSION, '8.2', '<')) {
    $sean_qr_error = 'requires PHP 8.2 or newer (this server runs PHP ' . PHP_VERSION . ').';
} elseif (!is_readable(__DIR__ . '/vendor/autoload.php')) {
    $sean_qr_error = 'is missing its dependencies. Run <code>composer install</code> in the <code>user/plugins/seans-qrcode</code> folder.';
}
if ($sean_qr_error) {
    if (function_exists('yourls_add_notice')) {
        $sean_qr_error = "<strong>Sean's QR Code Short URLs</strong> {$sean_qr_error} QR codes are disabled until this is fixed.";
        yourls_add_notice($sean_qr_error, 'error');
    }
    unset($sean_qr_error);
    return;
}
unset($sean_qr_error);

require_once __DIR__ . '/vendor/autoload.php';

// Load the plugin's own classes (SeansQRCode\Foo => src/Foo.php)
// (no `: void` return type: that's PHP 7.1+, and this file must parse on 7.0)
spl_autoload_register(static function (string $class) {
    $prefix = 'SeansQRCode\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// The YOURLS hooks (actions, filters and admin output)
require_once __DIR__ . '/hooks.php';
