<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $httpsAtivo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $httpsAtivo,
    ]);
}

require_once __DIR__ . '/vendor/autoload.php';

use PhiloQuest\Config;

Config::load(__DIR__);
Config::applyRuntimeSettings();
Config::validateProduction();
require_once __DIR__ . '/src/layout_helpers.php';

if (!defined('PHILOQUEST_WEB_ROOT')) {
    define('PHILOQUEST_WEB_ROOT', philoquest_web_root());
}
if (!defined('PHILOQUEST_BASE')) {
    define('PHILOQUEST_BASE', PHILOQUEST_WEB_ROOT);
}
