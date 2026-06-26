<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use NeNeSuite\InstallSession\InstallSession;

/**
 * Resolves an app's database target with the install session in context (ADR 0022
 * mode A), layering the sources highest-priority first:
 *
 * 1. **session override** — the operator's per-app choice carried on the session
 *    (set through the install wizard / `setDatabaseTargets` API), validated through
 *    the shared {@see DatabaseTargetFactory}.
 * 2. **env / default** — when the app carries no override, delegate to the env
 *    resolver ({@see EnvDatabaseTargetResolver}), which itself defaults to the
 *    historical provision-on-suite-server target.
 *
 * Both layers run their inputs through the same factory, so an operator-supplied
 * target is validated identically to an env one (external = adopt-only, safe name).
 * The default case (no override, no env) is byte-identical to the suite's historical
 * single-model behaviour.
 */
final readonly class SessionDatabaseTargetResolver
{
    public function __construct(
        private DatabaseTargetResolverInterface $fallback,
        private DatabaseTargetFactory $factory,
    ) {
    }

    /**
     * @throws \InvalidArgumentException               on an unsafe database name (override or env)
     * @throws ExternalProvisionNotSupportedException  when `provision` is paired with an external server (ADR 0021 OQ2)
     */
    public function resolve(InstallSession $session, string $catalogId): DatabaseTarget
    {
        $override = $session->databaseTargetFor($catalogId);

        if ($override !== null) {
            return $this->factory->create(
                $catalogId,
                $override->mode,
                $override->server,
                $override->name,
            );
        }

        return $this->fallback->resolve($catalogId);
    }
}
