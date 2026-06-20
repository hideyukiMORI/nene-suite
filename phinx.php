<?php

declare(strict_types=1);

use Nene2\Config\ConfigLoader;
use NeNeSuite\Http\ControlDatabaseConfigResolver;

require_once __DIR__ . '/vendor/autoload.php';

// ADR 0011 + ADR 0014: resolve the control DB via the same resolver the runtime
// uses — NENE_SUITE_CONTROL_DATABASE_URL first, then the NENE2 ConfigLoader.
// Phinx does NOT understand mysql:// URLs (it cannot extract the database name),
// so we always pass explicit adapter/host/name/... fields parsed by the resolver.
$database = (new ControlDatabaseConfigResolver())->resolve(new ConfigLoader(__DIR__));

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
