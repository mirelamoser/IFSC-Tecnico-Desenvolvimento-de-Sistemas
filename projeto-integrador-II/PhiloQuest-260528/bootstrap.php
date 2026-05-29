<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/vendor/autoload.php';

use PhiloQuest\Config;

Config::load(__DIR__);
require_once __DIR__ . '/src/layout_helpers.php';

if (!defined('PHILOQUEST_WEB_ROOT')) {
    define('PHILOQUEST_WEB_ROOT', philoquest_web_root());
}
if (!defined('PHILOQUEST_BASE')) {
    define('PHILOQUEST_BASE', PHILOQUEST_WEB_ROOT);
}
