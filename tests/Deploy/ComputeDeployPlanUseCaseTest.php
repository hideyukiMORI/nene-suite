<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use DateTimeImmutable;
use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\Deploy\ComputeDeployPlanUseCase;
use NeNeSuite\Deploy\DeployAgentConfig;
use NeNeSuite\Deploy\DeployPlan;
use NeNeSuite\Deploy\DeployPlanConflict;
use NeNeSuite\Deploy\DeployPlanSkip;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdatesOutput;
use NeNeSuite\Origin\OriginUpdateStatus;
use NeNeSuite\Tests\AppCatalog\InMemoryCatalogAppRepository;
use PHPUnit\Framework\TestCase;

final class ComputeDeployPlanUseCaseTest extends TestCase
{
    private const DIGEST_INVOICE = 'sha256:1111111111111111111111111111111111111111111111111111111111111111';
    private const DIGEST_CLEAR = 'sha256:2222222222222222222222222222222222222222222222222222222222222222';

    public function testUnavailableWhenOriginIsDisabled(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::disabled());

        self::assertFalse($plan->available);
        self::assertSame(DeployPlan::REASON_ORIGIN_DISABLED, $plan->reason);
        self::assertFalse($plan->executable);
        self::assertSame([], $plan->steps);
    }

    public function testOrdersDependenciesBeforeDependents(): void
    {
        // The roster lists clear first; the catalog DAG (clear requires invoice) must win.
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0'),
            $this->signal('nene-invoice', '1.3.0', OriginUpdateStatus::UpdateAvailable, '1.4.0'),
        ]));

        self::assertTrue($plan->available);
        self::assertTrue($plan->executable);
        self::assertSame([], $plan->conflicts);
        self::assertCount(2, $plan->steps);
        self::assertSame(['nene-invoice', 'nene-clear'], array_map(static fn ($s) => $s->service, $plan->steps));
        self::assertSame([1, 2], array_map(static fn ($s) => $s->order, $plan->steps));
        self::assertSame(self::DIGEST_INVOICE, $plan->steps[0]->imageDigest);
        self::assertFalse($plan->steps[0]->forced);
    }

    public function testUnknownInstalledVersionIsDefensivelySkipped(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', null, OriginUpdateStatus::Unknown, '1.4.0'),
        ]));

        self::assertSame([], $plan->steps);
        self::assertFalse($plan->executable);
        self::assertCount(1, $plan->skipped);
        self::assertSame(DeployPlanSkip::REASON_INSTALLED_VERSION_UNKNOWN, $plan->skipped[0]->reason);
    }

    public function testMinVersionViolationWhenDependencyStaysBelowConstraint(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.4.0', OriginUpdateStatus::UpToDate, '1.4.0'),
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0', ['nene-invoice' => '>=2.0.0']),
        ]));

        self::assertFalse($plan->executable);
        self::assertCount(1, $plan->conflicts);
        self::assertSame(DeployPlanConflict::TYPE_MIN_VERSION_VIOLATION, $plan->conflicts[0]->type);
        self::assertSame('nene-clear', $plan->conflicts[0]->service);
    }

    public function testConstraintSatisfiedByTheUpdateSetItself(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.4.0', OriginUpdateStatus::UpdateAvailable, '2.0.0'),
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0', ['nene-invoice' => '>=2.0.0']),
        ]));

        self::assertTrue($plan->executable);
        self::assertSame([], $plan->conflicts);
        self::assertSame(['nene-invoice', 'nene-clear'], array_map(static fn ($s) => $s->service, $plan->steps));
    }

    public function testMissingDigestPinBlocksThePlan(): void
    {
        $catalog = new Catalog(1, [
            $this->app('nene-invoice', [], null),
        ]);

        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.3.0', OriginUpdateStatus::UpdateAvailable, '1.4.0'),
        ]), $catalog);

        self::assertFalse($plan->executable);
        self::assertCount(1, $plan->conflicts);
        self::assertSame(DeployPlanConflict::TYPE_MISSING_DIGEST_PIN, $plan->conflicts[0]->type);
        self::assertSame([], $plan->steps);
    }

    public function testDependencyCycleIsAnExplicitConflict(): void
    {
        $catalog = new Catalog(1, [
            $this->app('nene-invoice', ['nene-clear'], self::DIGEST_INVOICE),
            $this->app('nene-clear', ['nene-invoice'], self::DIGEST_CLEAR),
        ]);

        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.3.0', OriginUpdateStatus::UpdateAvailable, '1.4.0'),
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0'),
        ]), $catalog);

        self::assertFalse($plan->executable);
        self::assertNotEmpty(array_filter(
            $plan->conflicts,
            static fn (DeployPlanConflict $c): bool => $c->type === DeployPlanConflict::TYPE_DEPENDENCY_CYCLE,
        ));
    }

    public function testUnsupportedConstraintRefusesInsteadOfGuessing(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.4.0', OriginUpdateStatus::UpToDate, '1.4.0'),
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0', ['nene-invoice' => '^1.2']),
        ]));

        self::assertFalse($plan->executable);
        self::assertSame(DeployPlanConflict::TYPE_UNSUPPORTED_CONSTRAINT, $plan->conflicts[0]->type);
    }

    public function testUnknownDependencyVersionRefusesDefensively(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', null, OriginUpdateStatus::Unknown, '2.0.0'),
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0', ['nene-invoice' => '>=1.0.0']),
        ]));

        self::assertFalse($plan->executable);
        self::assertSame(DeployPlanConflict::TYPE_UNKNOWN_DEPENDENCY_VERSION, $plan->conflicts[0]->type);
    }

    public function testManifestDependencyOutsideRosterIsAConflict(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-clear', '1.1.0', OriginUpdateStatus::UpdateAvailable, '1.2.0', ['nene-records' => '>=1.0.0']),
        ]));

        self::assertFalse($plan->executable);
        self::assertSame(DeployPlanConflict::TYPE_MISSING_DEPENDENCY, $plan->conflicts[0]->type);
    }

    public function testForcedUpdateIsMarkedOnTheStep(): void
    {
        $plan = $this->plan(OriginUpdatesOutput::enabled([
            $this->signal('nene-invoice', '1.0.0', OriginUpdateStatus::Forced, '1.4.0'),
        ]));

        self::assertTrue($plan->executable);
        self::assertTrue($plan->steps[0]->forced);
    }

    /**
     * @param array<string, string> $requires
     */
    private function signal(string $product, ?string $installed, OriginUpdateStatus $status, ?string $latest, array $requires = []): OriginUpdateSignal
    {
        return new OriginUpdateSignal(
            product: $product,
            channel: 'stable',
            installedVersion: $installed,
            status: $status,
            latestVersion: $latest,
            requires: $requires,
        );
    }

    /**
     * @param list<string> $requires
     */
    private function app(string $id, array $requires, ?string $digest): CatalogApp
    {
        return new CatalogApp($id, $id, null, $id, 'installable', $requires, [], '/install/index.php', null, $digest);
    }

    private function plan(OriginUpdatesOutput $output, ?Catalog $catalog = null): DeployPlan
    {
        $useCase = new ComputeDeployPlanUseCase(
            new DeployAgentConfig(true, str_repeat('k', 32)),
            new FixedOriginUpdatesUseCase($output),
            new InMemoryCatalogAppRepository($catalog ?? $this->defaultCatalog()),
        );

        return $useCase->execute(new DateTimeImmutable('2026-07-05T00:00:00Z'));
    }

    private function defaultCatalog(): Catalog
    {
        return new Catalog(1, [
            $this->app('nene-invoice', [], self::DIGEST_INVOICE),
            $this->app('nene-clear', ['nene-invoice'], self::DIGEST_CLEAR),
        ]);
    }
}
