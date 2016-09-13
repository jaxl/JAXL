<?php
/**
 * Bootstrap for examples.
 */

error_reporting(E_ALL | E_STRICT);

if (PHP_SAPI !== 'cli') {
    echo 'Warning: script should be invoked via the CLI version of PHP, not the '.PHP_SAPI.' SAPI'.PHP_EOL;
}

if (!ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

foreach (array(
    dirname(__FILE__) . '/../../autoload.php',
    dirname(__FILE__) . '/../vendor/autoload.php',
    dirname(__FILE__) . '/vendor/autoload.php'
) as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
}
unset($file);
