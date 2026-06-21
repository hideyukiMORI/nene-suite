<?php

declare(strict_types=1);

/**
 * Serving-only tenancy backfill (milestone A5): on every server start, idempotently grants
 * existing operators their default-org membership before A6 makes the session read one
 * (the lockout firewall). Invoked from ops/docker/entrypoint.sh on the `apache2-foreground`
 * command only, after `phinx migrate` (it reads tenancy tables the migration creates).
 *
 * WARN-AND-CONTINUE: a backfill failure must NOT abort the boot. Aborting every start would
 * convert partial membership coverage into a total self-inflicted outage on every restart —
 * strictly worse than the A6 lockout it guards against. The operation is idempotent, so a
 * transient failure self-heals next start; a permanent one stays visible via the
 * `[backfill] WARNING` and is recoverable via the superadmin console (A7). Hence `exit(0)`
 * even on failure (the catch is the outermost frame; `set -e` in the entrypoint is not tripped).
 */

use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Installer\BackfillTenancyInput;
use NeNeSuite\Installer\BackfillTenancyUseCaseInterface;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

try {
    $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
    $useCase = $container->get(BackfillTenancyUseCaseInterface::class);

    if (!$useCase instanceof BackfillTenancyUseCaseInterface) {
        throw new RuntimeException('Backfill tenancy use case service is invalid.');
    }

    $output = $useCase->execute(new BackfillTenancyInput());

    if ($output->skipped) {
        echo "[backfill] no operators yet — skipped (fresh install owns bootstrap)\n";
    } else {
        echo '[backfill] OK — org ' . ($output->organization?->id ?? '(none)')
            . ', ' . $output->operatorsBackfilled . " operator(s) ensured\n";
    }
} catch (Throwable $exception) {
    $message = $exception->getMessage();
    for ($cause = $exception->getPrevious(); $cause !== null; $cause = $cause->getPrevious()) {
        $message .= ' — ' . $cause->getMessage();
    }

    fwrite(STDERR, '[backfill] WARNING: tenancy backfill failed (will retry next start): ' . $message . "\n");
}

exit(0);
