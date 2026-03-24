<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if (($_SERVER['APP_ENV'] ?? '') === 'test') {
    $projectDir = dirname(__DIR__);
    $varDir = $projectDir.'/var';
    if (!is_dir($varDir)) {
        mkdir($varDir, 0775, true);
    }
    $dbPath = str_replace(\DIRECTORY_SEPARATOR, '/', $varDir.'/test.sqlite');
    $_ENV['DATABASE_URL'] = $_SERVER['DATABASE_URL'] = 'sqlite:///'.$dbPath;
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
