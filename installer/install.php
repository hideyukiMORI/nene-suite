<?php

declare(strict_types=1);

/**
 * NeNe Suite — Tier B CLI Installer
 *
 * Usage (inside Docker Compose):
 *   docker compose run --rm suite php installer/install.php
 *
 * Required env vars (set in .env.suite or via docker-compose):
 *   NENE_SUITE_BASE_URL, NENE_SUITE_ORG_NAME, NENE_SUITE_INSTALLED_APPS
 *   NENE_SUITE_DISCLAIMER_ACCEPTED=1
 *   NENE_SUITE_APEX_OPERATOR_EMAIL, NENE_SUITE_APEX_OPERATOR_PASSWORD
 *   NENE_SUITE_ENV_FILE_PATH, NENE_SUITE_PROVISION_DB_*
 *
 * See docs/explanation/suite-environment-contract.md and .env.suite.example.
 */

use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Installer\InstallerEnvReader;
use NeNeSuite\Installer\InstallerUseCaseInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

echo "\nNeNe Suite — Tier B Installer\n";
echo str_repeat('=', 40) . "\n\n";

// Run phinx migrations before bootstrapping the container.
echo "Running database migrations...\n";
$exitCode = 0;
passthru(
    sprintf('%s/vendor/bin/phinx migrate -c %s/phinx.php', dirname(__DIR__), dirname(__DIR__)),
    $exitCode,
);

if ($exitCode !== 0) {
    fwrite(STDERR, "\nERROR: Database migrations failed. Check your NENE_SUITE_CONTROL_DATABASE_URL.\n");
    exit(1);
}

echo "\n";

// Bootstrap the DI container (loads .env.suite if present).
$container = (new RuntimeContainerFactory(dirname(__DIR__)))->create();

// Read installer configuration from env.
$reader = $container->get(InstallerEnvReader::class);
assert($reader instanceof InstallerEnvReader);

try {
    $input = $reader->read();
} catch (RuntimeException $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "See .env.suite.example for required variables.\n");
    exit(1);
}

echo 'Apps to install : ' . implode(', ', $input->selectedApps) . "\n";
echo 'Organization    : ' . $input->orgName . "\n";
echo 'Base URL        : ' . $input->baseUrl . "\n";
echo 'Operator email  : ' . $input->operatorEmail . "\n\n";

// Run the installer use case.
$installer = $container->get(InstallerUseCaseInterface::class);
assert($installer instanceof InstallerUseCaseInterface);

try {
    echo "Starting install session...\n";
    $output = $installer->execute($input);
} catch (Throwable $e) {
    fwrite(STDERR, "\nERROR: Install failed — " . $e->getMessage() . "\n");
    fwrite(STDERR, 'Class: ' . get_class($e) . "\n");
    exit(1);
}

// Print summary.
echo "\n" . str_repeat('=', 40) . "\n";
echo "Installation complete\n";
echo str_repeat('=', 40) . "\n\n";
echo 'Session ID    : ' . $output->installSessionId . "\n";
echo 'Manifest ID   : ' . $output->installManifestId . "\n";
echo 'Operator ID   : ' . $output->operatorId . "\n";
echo 'Databases     : ' . implode(', ', $output->provisionedDatabases) . "\n";
echo 'Env written   : ' . $output->envFilePath . "\n";
echo 'Completed at  : ' . $output->completedAt . "\n";
echo "\n";
echo "Setup orchestration only — no business or legal warranty.\n";
echo "See docs/explanation/disclaimer.md\n\n";

exit(0);
