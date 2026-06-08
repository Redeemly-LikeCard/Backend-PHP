<?php

$root = getcwd();

if (file_exists($root . DIRECTORY_SEPARATOR . 'artisan') || file_exists($root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php')) {
    return;
}

if (!file_exists($root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Routes.php') && !file_exists($root . DIRECTORY_SEPARATOR . 'spark')) {
    return;
}

$autoload = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!file_exists($autoload)) {
    return;
}

require $autoload;

if (class_exists(\LuckyCode\IntegrationHelper\CodeIgniter\Installer::class)) {
    \LuckyCode\IntegrationHelper\CodeIgniter\Installer::postInstall();
}
