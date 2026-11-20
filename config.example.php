<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '3307');
define('DB_NAME', 'comfort_zone_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Enter your local MySQL password here

define('SITE_NAME', 'Comfort Zone');
define('SITE_URL', '/comfort_zone/');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}

function getDB() {
    static  = null;
    if ( === null) {
         = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
         = new PDO(, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return ;
}
