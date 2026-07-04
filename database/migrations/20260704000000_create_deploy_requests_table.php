<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateDeployRequestsTable extends AbstractMigration
{
    public function change(): void
    {
        // Deploy-control seam queue (ADR 0019 OQ1, S2-1a / #361): one row per "recreate service X
        // at image digest D" request the suite hands to the host-side deploy agent. The suite only
        // ever writes `pending` rows and the agent's reported terminal result (`succeeded`/`failed`)
        // — the actual `compose pull` + recreate happens outside the container, on the compose host.
        // `service` is a catalog app id (explicit allow-list); `image_digest` is an immutable
        // `sha256:` pin (OQ2 stage 1). Timestamps are UTC ISO-8601 strings like the sibling tables.
        // Cross-engine via the Phinx adapter API (ADR 0016).
        $this->table('deploy_requests', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', ['limit' => 26, 'null' => false])
            ->addColumn('service', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('image_digest', 'string', ['limit' => 96, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('requested_by', 'string', ['limit' => 26, 'null' => true])
            ->addColumn('detail', 'text', ['null' => true])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('completed_at', 'string', ['limit' => 32, 'null' => true])
            ->addIndex(['status', 'created_at'])
            ->create();
    }
}
