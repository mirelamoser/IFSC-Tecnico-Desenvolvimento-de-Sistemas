<?php

declare(strict_types=1);

/**
 * Compatibilidade com entradas legadas que ainda referenciam autoload.php.
 * O carregamento oficial é via Composer (vendor/autoload.php) em bootstrap.php.
 */
require_once __DIR__ . '/vendor/autoload.php';
