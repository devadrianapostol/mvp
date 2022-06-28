<?php
define('ENVIROMENT', 'dev');
define('APP_URL', "http://mpv2.test");
define('APP_PATH', '/');

define("APP_ROOT",  realpath(__DIR__ . DIRECTORY_SEPARATOR ) );

define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', '12345678');
define('DB_NAME', 'mvp');
define('DB_CHAR', 'utf8mb4');

define('JWT_EXPIRE', (time() + 60000));