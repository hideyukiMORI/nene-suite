<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Probes each app's `/health` for its installed version, persists every fresh reading, and falls
 * back to the last-known stored version when a probe fails or reports no version (resilient to
 * transient sibling downtime). A product never seen with a version resolves to null (the aggregator
 * keeps it `unknown`). Best-effort throughout: a single unreachable sibling never blocks the others.
 */
final readonly class InstalledVersionResolver implements InstalledVersionResolverInterface
{
    public function __construct(
        private SiblingHealthClientInterface $client,
        private InstalledVersionRepositoryInterface $repository,
    ) {
    }

    public function resolve(iterable $apps, DateTimeImmutable $now): array
    {
        $checkedAt = $now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        $versions = [];

        foreach ($apps as $app) {
            $probed = $this->client->fetchVersion($app->publicUrl);

            if ($probed !== null) {
                $this->repository->record($app->catalogId, $probed, $checkedAt);
                $versions[$app->catalogId] = $probed;

                continue;
            }

            $versions[$app->catalogId] = $this->repository->current($app->catalogId);
        }

        return $versions;
    }
}
