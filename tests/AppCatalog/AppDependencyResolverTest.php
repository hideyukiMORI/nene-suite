<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use Nene2\Validation\ValidationException;
use NeNeSuite\AppCatalog\AppDependencyResolver;
use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use PHPUnit\Framework\TestCase;

final class AppDependencyResolverTest extends TestCase
{
    public function testAddsRequiredDependencyAndOrdersItFirst(): void
    {
        $resolved = (new AppDependencyResolver())->resolve($this->catalog(), ['nene-clear']);

        self::assertSame(['nene-invoice', 'nene-clear'], $resolved);
    }

    public function testDeduplicatesAndPreservesDependencyOrder(): void
    {
        $resolved = (new AppDependencyResolver())->resolve($this->catalog(), ['nene-clear', 'nene-invoice']);

        self::assertSame(['nene-invoice', 'nene-clear'], $resolved);
    }

    public function testEmptySelectionResolvesToEmpty(): void
    {
        self::assertSame([], (new AppDependencyResolver())->resolve($this->catalog(), []));
    }

    public function testRejectsUnknownApp(): void
    {
        $this->expectException(ValidationException::class);

        (new AppDependencyResolver())->resolve($this->catalog(), ['nene-ghost']);
    }

    public function testRejectsNonInstallableApp(): void
    {
        try {
            (new AppDependencyResolver())->resolve($this->catalog(), ['nene-vault']);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $exception) {
            $codes = array_map(static fn (array $e): string => $e['code'], $exception->errorsForResponse());
            self::assertContains('app_not_installable', $codes);
        }
    }

    private function catalog(): Catalog
    {
        return new Catalog(1, [
            $this->app('nene-invoice', 'installable', []),
            $this->app('nene-clear', 'installable', ['nene-invoice']),
            $this->app('nene-vault', 'planned', []),
        ]);
    }

    /**
     * @param list<string> $requires
     */
    private function app(string $id, string $status, array $requires): CatalogApp
    {
        return new CatalogApp(
            id: $id,
            name: ucfirst($id),
            repository: null,
            path: $id,
            status: $status,
            requires: $requires,
            provides: [],
            installEntry: '/install/index.php',
            databaseEnvPrefix: null,
        );
    }
}
