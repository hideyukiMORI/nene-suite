<?php

declare(strict_types=1);

use Nene2\Config\ConfigLoader;
use NeNeSuite\Http\ControlDatabaseConfigResolver;

require_once __DIR__ . '/vendor/autoload.php';

// ADR 0011: prefer NENE_SUITE_CONTROL_DATABASE_URL; fall back to NENE2 ConfigLoader.
// Phinx accepts mysql:// URLs natively, so pass the raw URL directly when set.
$rawUrl = ControlDatabaseConfigResolver::rawUrl();

if ($rawUrl !== null) {
    $dbEnvironmentName = 'production';
    $dbEnvironment = ['url' => $rawUrl];
} else {
    $database = (new ConfigLoader(__DIR__))->load()->database;
    $dbEnvironmentName = $database->environment;
    $dbEnvironment = $database->usesUrl()
        ? ['url' => $database->url]
        : [
            'adapter'  => $database->adapter,
            'host'     => $database->host,
            'name'     => $database->name,
            'user'     => $database->user,
            'pass'     => $database->password,
            'port'     => $database->port,
            'charset'  => $database->charset,
        ];
}

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds'      => 'database/seeds',
    ],
    'environments' => [
        'default_environment' => $dbEnvironmentName,
        $dbEnvironmentName    => $dbEnvironment,
    ],
    'version_order' => 'creation',
];
