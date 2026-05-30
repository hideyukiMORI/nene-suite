<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;

/**
 * Resolves an operator's requested catalog ids into the full, dependency-ordered
 * set that the installer must provision (dependencies first). Enforces R-01:
 * unknown ids, non-installable apps, and unmet dependencies are rejected as
 * validation errors (mapped to 422 validation-failed).
 */
final readonly class AppDependencyResolver
{
    /**
     * @param list<string> $requested
     *
     * @return list<string> dependency-ordered, de-duplicated, installable ids
     *
     * @throws ValidationException when an id is unknown, not installable, or has an unmet dependency
     */
    public function resolve(Catalog $catalog, array $requested): array
    {
        $byId = [];
        foreach ($catalog->apps as $app) {
            $byId[$app->id] = $app;
        }

        $errors = [];
        foreach ($requested as $id) {
            $app = $byId[$id] ?? null;
            if ($app === null) {
                $errors[] = new ValidationError('selectedApps', "Unknown app: {$id}", 'unknown_app');
            } elseif ($app->status !== 'installable') {
                $errors[] = new ValidationError('selectedApps', "App is not installable: {$id}", 'app_not_installable');
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $resolved = [];
        foreach ($requested as $id) {
            $this->visit($id, $byId, [], $resolved, $errors);
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $resolved;
    }

    /**
     * @param array<string, CatalogApp> $byId
     * @param array<string, true>       $visiting nodes on the current DFS path (cycle guard)
     * @param list<string>              $resolved
     * @param list<ValidationError>     $errors
     */
    private function visit(string $id, array $byId, array $visiting, array &$resolved, array &$errors): void
    {
        if (in_array($id, $resolved, true) || isset($visiting[$id])) {
            return;
        }

        $app = $byId[$id] ?? null;
        if ($app === null) {
            $errors[] = new ValidationError('selectedApps', "Unmet dependency: {$id}", 'dependency_unmet');

            return;
        }

        if ($app->status !== 'installable') {
            $errors[] = new ValidationError('selectedApps', "Dependency is not installable: {$id}", 'app_not_installable');

            return;
        }

        $visiting[$id] = true;
        foreach ($app->requires as $dependency) {
            $this->visit($dependency, $byId, $visiting, $resolved, $errors);
        }

        if (!in_array($id, $resolved, true)) {
            $resolved[] = $id;
        }
    }
}
