<?php

declare(strict_types=1);

/**
 * Structural validation for docs/openapi/openapi.yaml (run via `composer openapi`).
 *
 * Complements redocly lint (CI) with suite-specific contract rules:
 * OpenAPI 3.1, unique camelCase operationIds, a success + Problem Details
 * response per operation, and the suite Problem Details `type` base.
 * Route ↔ spec consistency lives in tests/OpenApi/.
 */

use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../vendor/autoload.php';

$path = __DIR__ . '/../docs/openapi/openapi.yaml';
$errors = [];

if (!is_file($path)) {
    fwrite(STDERR, "FAIL: OpenAPI contract not found: {$path}\n");
    exit(1);
}

/** @var mixed $doc */
$doc = Yaml::parseFile($path);

if (!is_array($doc)) {
    fwrite(STDERR, "FAIL: OpenAPI root must be a mapping.\n");
    exit(1);
}

$openapi = $doc['openapi'] ?? null;
if (!is_string($openapi) || !str_starts_with($openapi, '3.1')) {
    $errors[] = 'openapi version must be 3.1.x (got ' . var_export($openapi, true) . ')';
}

$problemBase = 'https://nene-suite.dev/problems/';
$paths = $doc['paths'] ?? null;
$operationIds = [];

if (!is_array($paths) || $paths === []) {
    $errors[] = 'paths object is missing or empty';
    $paths = [];
}

$httpMethods = ['get', 'put', 'post', 'delete', 'patch'];

foreach ($paths as $route => $item) {
    if (!is_array($item)) {
        $errors[] = "path {$route}: must be a mapping";

        continue;
    }

    foreach ($item as $method => $operation) {
        if (!in_array($method, $httpMethods, true)) {
            continue;
        }

        if (!is_array($operation)) {
            $errors[] = "{$method} {$route}: operation must be a mapping";

            continue;
        }

        $where = strtoupper((string) $method) . ' ' . $route;

        $operationId = $operation['operationId'] ?? null;
        if (!is_string($operationId) || $operationId === '') {
            $errors[] = "{$where}: missing operationId";
        } else {
            if (preg_match('/^[a-z][a-zA-Z0-9]*$/', $operationId) !== 1) {
                $errors[] = "{$where}: operationId '{$operationId}' must be camelCase";
            }
            if (isset($operationIds[$operationId])) {
                $errors[] = "{$where}: duplicate operationId '{$operationId}' (also {$operationIds[$operationId]})";
            }
            $operationIds[$operationId] = $where;
        }

        $responses = $operation['responses'] ?? null;
        if (!is_array($responses)) {
            $errors[] = "{$where}: missing responses";

            continue;
        }

        $statuses = array_map('strval', array_keys($responses));
        $hasSuccess = false;
        foreach ($statuses as $status) {
            if (str_starts_with($status, '2')) {
                $hasSuccess = true;
            }
        }
        if (!$hasSuccess) {
            $errors[] = "{$where}: no 2xx success response";
        }

        $hasErrorStatus = false;
        foreach ($statuses as $status) {
            if (str_starts_with($status, '4') || str_starts_with($status, '5')) {
                $hasErrorStatus = true;
            }
        }
        if (!$hasErrorStatus) {
            $errors[] = "{$where}: no 4xx/5xx error response (Problem Details expected)";
        }
    }
}

// Problem Details `type` URIs in component examples must use the suite base.
$encoded = (string) json_encode($doc);
if (preg_match_all('#https://[a-z0-9.-]+/problems/#', $encoded, $matches) > 0) {
    foreach (array_unique($matches[0]) as $base) {
        if ($base !== $problemBase) {
            $errors[] = "Problem Details base must be {$problemBase} (found {$base})";
        }
    }
}

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "FAIL: {$error}\n");
    }
    fwrite(STDERR, "\nOpenAPI contract validation failed with " . count($errors) . " error(s).\n");
    exit(1);
}

echo 'OpenAPI contract valid: ' . count($operationIds) . " operation(s) in docs/openapi/openapi.yaml\n";
