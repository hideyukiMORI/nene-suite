<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use DateTimeImmutable;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\Origin\GetOriginUpdatesUseCaseInterface;
use NeNeSuite\Origin\OriginUpdateSignal;
use NeNeSuite\Origin\OriginUpdateStatus;

/**
 * S2-1b (ADR 0019 §3): computes the dependency-ordered "update all" plan from the
 * installed roster, the verified Origin signals, the catalog `requires` DAG, and the
 * catalog image-digest pins. Read-only — execution is S2-1c. Defensive throughout:
 * unknown installed versions are skipped (never updated blindly), and every
 * min-version / structural problem becomes a conflict that blocks `executable`
 * (refuse, don't guess — the halt-don't-unwind posture starts at planning).
 */
final readonly class ComputeDeployPlanUseCase implements ComputeDeployPlanUseCaseInterface
{
    private const CONSTRAINT_PATTERN = '/^>=\s*([0-9]+(?:\.[0-9]+)*(?:[-+][0-9A-Za-z.-]+)?)$/';

    public function __construct(
        private DeployAgentConfig $config,
        private GetOriginUpdatesUseCaseInterface $updates,
        private CatalogAppRepositoryInterface $catalog,
    ) {
    }

    public function execute(DateTimeImmutable $now): DeployPlan
    {
        $output = $this->updates->execute($now);

        if (!$output->available) {
            return DeployPlan::unavailable($this->config->enabled, DeployPlan::REASON_ORIGIN_DISABLED);
        }

        /** @var array<string, CatalogApp> $catalogById */
        $catalogById = [];

        foreach ($this->catalog->load()->apps as $app) {
            $catalogById[$app->id] = $app;
        }

        /** @var array<string, OriginUpdateSignal> $signals roster order preserved */
        $signals = [];

        foreach ($output->updates as $signal) {
            $signals[$signal->product] = $signal;
        }

        $skipped = [];
        $conflicts = [];
        /** @var array<string, OriginUpdateSignal> $candidates */
        $candidates = [];

        foreach ($signals as $product => $signal) {
            switch ($signal->status) {
                case OriginUpdateStatus::Forced:
                case OriginUpdateStatus::UpdateAvailable:
                    if ($signal->latestVersion === null) {
                        $skipped[] = new DeployPlanSkip($product, DeployPlanSkip::REASON_UNAVAILABLE, 'No verified target version.');

                        break;
                    }

                    $candidates[$product] = $signal;

                    break;
                case OriginUpdateStatus::Unknown:
                    $skipped[] = new DeployPlanSkip($product, DeployPlanSkip::REASON_INSTALLED_VERSION_UNKNOWN);

                    break;
                case OriginUpdateStatus::UpToDate:
                    $skipped[] = new DeployPlanSkip($product, DeployPlanSkip::REASON_UP_TO_DATE);

                    break;
                case OriginUpdateStatus::Unavailable:
                    $skipped[] = new DeployPlanSkip($product, DeployPlanSkip::REASON_UNAVAILABLE, $signal->reason);

                    break;
            }
        }

        foreach ($candidates as $product => $signal) {
            $app = $catalogById[$product] ?? null;

            if ($app === null) {
                $conflicts[] = new DeployPlanConflict(
                    $product,
                    DeployPlanConflict::TYPE_MISSING_DEPENDENCY,
                    sprintf("Installed app '%s' is not in the catalog.", $product),
                );
            } elseif ($app->imageDigest === null) {
                $conflicts[] = new DeployPlanConflict(
                    $product,
                    DeployPlanConflict::TYPE_MISSING_DIGEST_PIN,
                    sprintf("The catalog has no deploy.image_digest pin for '%s' (ADR 0019 OQ2 stage 1 requires an immutable digest).", $product),
                );
            }
        }

        $ordered = $this->orderRoster(array_keys($signals), $catalogById, $candidates, $conflicts);

        $this->gateMinVersions($candidates, $signals, $conflicts);

        $steps = [];
        $order = 1;

        foreach ($ordered as $product) {
            $signal = $candidates[$product] ?? null;
            $app = $catalogById[$product] ?? null;

            if ($signal === null || $app === null || $app->imageDigest === null || $signal->latestVersion === null) {
                continue;
            }

            $steps[] = new DeployPlanStep(
                order: $order++,
                service: $product,
                installedVersion: $signal->installedVersion,
                targetVersion: $signal->latestVersion,
                imageDigest: $app->imageDigest,
                forced: $signal->status === OriginUpdateStatus::Forced,
            );
        }

        return new DeployPlan(
            enabled: $this->config->enabled,
            available: true,
            reason: null,
            executable: $steps !== [] && $conflicts === [],
            steps: $steps,
            skipped: $skipped,
            conflicts: $conflicts,
        );
    }

    /**
     * Topological order of the roster over the catalog `requires` DAG (dependencies
     * first). Cycles and out-of-roster dependencies of plan candidates surface as
     * explicit conflicts — never a silent reorder.
     *
     * @param list<string>                      $roster
     * @param array<string, CatalogApp>         $catalogById
     * @param array<string, OriginUpdateSignal> $candidates
     * @param list<DeployPlanConflict>          $conflicts
     *
     * @return list<string>
     */
    private function orderRoster(array $roster, array $catalogById, array $candidates, array &$conflicts): array
    {
        $rosterSet = array_fill_keys($roster, true);
        $ordered = [];
        $state = []; // product => 'visiting' | 'done'

        $visit = function (string $product, array $path) use (&$visit, &$ordered, &$state, &$conflicts, $rosterSet, $catalogById, $candidates): void {
            if (($state[$product] ?? null) === 'done') {
                return;
            }

            if (($state[$product] ?? null) === 'visiting') {
                $cycle = array_slice($path, (int) array_search($product, $path, true));
                $conflicts[] = new DeployPlanConflict(
                    $product,
                    DeployPlanConflict::TYPE_DEPENDENCY_CYCLE,
                    sprintf('Dependency cycle in the catalog: %s.', implode(' -> ', [...$cycle, $product])),
                );

                return;
            }

            $state[$product] = 'visiting';
            $path[] = $product;

            foreach ($catalogById[$product]->requires ?? [] as $dependency) {
                if (isset($rosterSet[$dependency])) {
                    $visit($dependency, $path);
                } elseif (isset($candidates[$product])) {
                    $conflicts[] = new DeployPlanConflict(
                        $product,
                        DeployPlanConflict::TYPE_MISSING_DEPENDENCY,
                        sprintf("'%s' requires '%s', which is not installed.", $product, $dependency),
                    );
                }
            }

            $state[$product] = 'done';
            $ordered[] = $product;
        };

        foreach ($roster as $product) {
            if (isset($catalogById[$product])) {
                $visit($product, []);
            }
        }

        return $ordered;
    }

    /**
     * ADR 0019 §3 min-version gating: for each candidate's verified `requires` map,
     * the dependency's **resulting** version (its target if it is also in the set,
     * else its installed version) must satisfy the constraint. Unknown resulting
     * versions refuse defensively.
     *
     * @param array<string, OriginUpdateSignal> $candidates
     * @param array<string, OriginUpdateSignal> $signals
     * @param list<DeployPlanConflict>          $conflicts
     */
    private function gateMinVersions(array $candidates, array $signals, array &$conflicts): void
    {
        foreach ($candidates as $product => $signal) {
            foreach ($signal->requires as $dependency => $constraint) {
                if ($dependency === $product) {
                    continue;
                }

                if (!isset($signals[$dependency])) {
                    $conflicts[] = new DeployPlanConflict(
                        $product,
                        DeployPlanConflict::TYPE_MISSING_DEPENDENCY,
                        sprintf("Target %s %s requires '%s' (%s), which is not installed.", $product, (string) $signal->latestVersion, $dependency, $constraint),
                    );

                    continue;
                }

                if (preg_match(self::CONSTRAINT_PATTERN, trim($constraint), $matches) !== 1) {
                    $conflicts[] = new DeployPlanConflict(
                        $product,
                        DeployPlanConflict::TYPE_UNSUPPORTED_CONSTRAINT,
                        sprintf("Constraint '%s' on '%s' is not a supported >= range — refusing rather than guessing.", $constraint, $dependency),
                    );

                    continue;
                }

                $minimum = $matches[1];
                $resulting = isset($candidates[$dependency])
                    ? $candidates[$dependency]->latestVersion
                    : $signals[$dependency]->installedVersion;

                if ($resulting === null) {
                    $conflicts[] = new DeployPlanConflict(
                        $product,
                        DeployPlanConflict::TYPE_UNKNOWN_DEPENDENCY_VERSION,
                        sprintf("Cannot verify '%s' %s: the resulting version of '%s' is unknown.", $dependency, $constraint, $dependency),
                    );

                    continue;
                }

                if (version_compare($resulting, $minimum, '<')) {
                    $conflicts[] = new DeployPlanConflict(
                        $product,
                        DeployPlanConflict::TYPE_MIN_VERSION_VIOLATION,
                        sprintf("Target %s %s requires '%s' >= %s, but the plan leaves it at %s.", $product, (string) $signal->latestVersion, $dependency, $minimum, $resulting),
                    );
                }
            }
        }
    }
}
