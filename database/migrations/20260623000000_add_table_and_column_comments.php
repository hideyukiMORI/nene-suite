<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds MySQL table and column COMMENTs to every control-DB table for
 * schema-level self-documentation (visible in DB clients such as HeidiSQL).
 *
 * Mechanism: each column is re-declared via changeColumn() using the EXACT
 * type/options from its create migration plus a 'comment'; only the comment
 * differs, so no schema drift. Table comments use changeComment(). Using the
 * Phinx adapter API (not raw `ALTER ... COMMENT=`) keeps this portable across
 * MySQL/Postgres. See ADR 0014 (schema migration lifecycle).
 *
 * down() clears the comments again.
 */
final class AddTableAndColumnComments extends AbstractMigration
{
    /**
     * Per-table spec: table comment + ordered columns as [type, options, comment].
     * Options mirror each table's create migration exactly (drift-free).
     *
     * @return array<string, array{comment: string, columns: array<string, array{0: string, 1: array<string, mixed>, 2: string}>}>
     */
    private function schemaComments(): array
    {
        $ts = ['limit' => 32, 'null' => false]; // created_at/updated_at style timestamp
        $tsNull = ['limit' => 32, 'null' => true];
        $ulid = ['limit' => 26, 'null' => false];
        $ulidNull = ['limit' => 26, 'null' => true];

        return [
            'operators' => [
                'comment' => 'Apex login shell accounts (operators). password_hash is a bcrypt/argon hash, never plaintext.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'email' => ['string', ['limit' => 320, 'null' => false], 'Login email address. Unique (idx_operators_email).'],
                    'password_hash' => ['string', ['limit' => 255, 'null' => false], 'Bcrypt/argon password hash. Never plaintext; never logged, audited, or exported.'],
                    'display_name' => ['string', ['limit' => 320, 'null' => true], 'Optional human-readable operator name.'],
                    'created_at' => ['string', $ts, 'Record creation time, ISO-8601 UTC string.'],
                    'updated_at' => ['string', $ts, 'Last modification time, ISO-8601 UTC string.'],
                ],
            ],
            'organizations' => [
                'comment' => 'Suite tenancy registry (SSOT). external_id is the federation UUID propagated to sibling apps; rows are status-flagged, never hard-deleted.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'external_id' => ['char', $ulid, 'Federation UUID (org_external_id in JWT claims), propagated to sibling apps. Unique.'],
                    'name' => ['string', ['limit' => 320, 'null' => false], 'Organization display name (operator-settable).'],
                    'slug' => ['string', ['limit' => 160, 'null' => false], 'URL-safe identifier for per-organization resolution (path/subdomain). Unique.'],
                    'status' => ['string', ['limit' => 32, 'null' => false], 'Lifecycle state: active | disabled. Never hard-deleted (ADR 0012).'],
                    'created_at' => ['string', $ts, 'Record creation time, ISO-8601 UTC string.'],
                    'updated_at' => ['string', $ts, 'Last modification time, ISO-8601 UTC string.'],
                ],
            ],
            'memberships' => [
                'comment' => 'Operator-to-organization role assignments. organization_id is NULL for the platform superadmin; unique per (operator_id, organization_id).',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'operator_id' => ['char', $ulid, 'Operator this membership belongs to (logical ref operators.id; no cross-DB FK).'],
                    'organization_id' => ['char', $ulidNull, 'Organization scope (logical ref organizations.id). NULL = platform-level superadmin.'],
                    'role' => ['string', ['limit' => 32, 'null' => false], 'Role: superadmin (platform, org NULL) | admin | member | viewer (org-scoped).'],
                    'created_at' => ['string', $ts, 'Record creation time, ISO-8601 UTC string.'],
                    'updated_at' => ['string', $ts, 'Last modification time, ISO-8601 UTC string.'],
                ],
            ],
            'install_sessions' => [
                'comment' => 'Installer run state (Tier B). One row per install run: in_progress to completed or failed. Holds no secrets.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'suite_id' => ['char', $ulid, 'Suite installation id (matches NENE_SUITE_ID and manifest suite_id).'],
                    'status' => ['string', ['limit' => 32, 'null' => false], 'Installer workflow state: in_progress | completed | failed.'],
                    'tier' => ['string', ['limit' => 8, 'null' => false], 'Deployment tier: B (Docker/VPS, current) | A (shared hosting, planned).'],
                    'catalog_revision' => ['integer', ['null' => false], 'App-catalog schema revision captured at install start.'],
                    'selected_apps_json' => ['text', ['null' => false], 'JSON array of selected catalog ids (dependency-resolved).'],
                    'disclaimer_accepted' => ['boolean', ['null' => false, 'default' => false], 'Whether the operator accepted the installer disclaimer (0/1).'],
                    'disclaimer_accepted_at' => ['string', $tsNull, 'When the disclaimer was accepted, ISO-8601 UTC string. NULL until accepted.'],
                    'org_external_id' => ['char', $ulidNull, 'Federation UUID (ULID) chosen for the organization. NULL until set.'],
                    'org_display_name' => ['string', ['limit' => 500, 'null' => true], 'Operator-entered organization name. NULL until set.'],
                    'install_manifest_id' => ['char', $ulidNull, 'Manifest produced on completion (logical ref install_manifests.id). NULL until completed.'],
                    'failure_code' => ['string', ['limit' => 64, 'null' => true], 'Machine-readable error code when status=failed. NULL otherwise.'],
                    'created_at' => ['string', $ts, 'Session creation time, ISO-8601 UTC string.'],
                    'updated_at' => ['string', $ts, 'Last state-change time, ISO-8601 UTC string.'],
                    'completed_at' => ['string', $tsNull, 'Completion time, ISO-8601 UTC string. NULL until completed.'],
                ],
            ],
            'install_manifests' => [
                'comment' => 'Point-in-time install snapshots (JSON, no secrets) for audit alignment and operator export. content_hash is the SHA-256 of content_json.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'suite_id' => ['char', $ulid, 'Suite installation id (logical ref install_sessions.suite_id).'],
                    'content_json' => ['text', ['null' => false], 'Manifest body as JSON (suite_id, installed_at, app_versions, org_external_id, enabled_integrations, ...). No secrets.'],
                    'content_hash' => ['string', ['limit' => 64, 'null' => false], 'SHA-256 hex of content_json for audit alignment and change detection.'],
                    'created_at' => ['string', $ts, 'Manifest creation time, ISO-8601 UTC string.'],
                ],
            ],
            'suite_audit_events' => [
                'comment' => 'Append-only audit trail of mutating suite actions (ADR 0007). before_json/after_json are sanitized snapshots with secrets redacted.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'suite_id' => ['char', $ulid, 'Suite installation id (matches NENE_SUITE_ID).'],
                    'org_external_id' => ['char', $ulidNull, 'Federation UUID when known; NULL for pre-auth wizard events.'],
                    'actor_user_id' => ['char', $ulidNull, 'Authenticated operator id (logical ref operators.id); NULL for pre-auth or system events.'],
                    'actor_label' => ['string', ['limit' => 320, 'null' => true], 'Human-readable actor label (e.g. operator email at disclaimer step). No secrets.'],
                    'action' => ['string', ['limit' => 128, 'null' => false], 'Action as {entity}.{verb}, e.g. install_session.completed, membership.granted. See audit-trail.md.'],
                    'entity_type' => ['string', ['limit' => 64, 'null' => false], 'Affected entity category, e.g. install_session | organization | membership | federation_signing_key. See audit-trail.md.'],
                    'entity_id' => ['string', ['limit' => 64, 'null' => false], 'Stable id of the affected entity (ULID, suite_id, or other domain key).'],
                    'before_json' => ['text', ['null' => true], 'Sanitized state before the change as JSON; NULL on create. Secrets redacted.'],
                    'after_json' => ['text', ['null' => true], 'Sanitized state after the change as JSON; NULL when policy allows. Secrets redacted.'],
                    'created_at' => ['string', $ts, 'Server-generated event time, ISO-8601 UTC string.'],
                    'source' => ['string', ['limit' => 32, 'null' => false], 'Origin: installer_ui | installer_cli | apex_admin | system | api.'],
                    'request_id' => ['string', ['limit' => 128, 'null' => true], 'Correlation id from the X-Request-Id header when present. NULL otherwise.'],
                    'install_session_id' => ['char', $ulidNull, 'Groups wizard steps of one install run (logical ref install_sessions.id). NULL otherwise.'],
                    'metadata_json' => ['text', ['null' => true], 'Event-specific extras as JSON (e.g. failure reason, dependency list). Sanitized.'],
                ],
            ],
            'login_attempts' => [
                'comment' => 'Fixed-window login rate-limit counters. One row per attempt_key; ephemeral, reclaimed by opportunistic GC.',
                'columns' => [
                    'attempt_key' => ['string', ['limit' => 160, 'null' => false], 'Rate-limit key and primary key, e.g. ip:192.0.2.1.'],
                    'window_started_at' => ['biginteger', ['null' => false], 'Start of the current fixed window, epoch seconds (BIGINT).'],
                    'attempt_count' => ['integer', ['null' => false], 'Failed-attempt count within the current window.'],
                ],
            ],
            'revoked_tokens' => [
                'comment' => 'Logout/revocation denylist of JWT jti values. Ephemeral; rows reclaimed once expires_at passes.',
                'columns' => [
                    'jti' => ['char', $ulid, 'JWT jti claim and primary key (ULID). Revocation is idempotent.'],
                    'token_type' => ['string', ['limit' => 32, 'null' => false], 'Revoked token type: apex_session (current). Extensible.'],
                    'expires_at' => ['biginteger', ['null' => false], 'Token exp time, epoch seconds (BIGINT). Used for GC.'],
                    'revoked_at' => ['string', $ts, 'When revocation was recorded, ISO-8601 UTC string.'],
                    'reason' => ['string', ['limit' => 64, 'null' => false], 'Revocation reason, e.g. logout. Extensible.'],
                ],
            ],
            'federation_signing_keys' => [
                'comment' => 'Federation IdP signing keys: public JWK only (private key never stored). Exactly one row is active; status drives JWKS publication.',
                'columns' => [
                    'id' => ['char', $ulid, 'ULID primary key.'],
                    'kid' => ['string', ['limit' => 64, 'null' => false], 'Key id: RFC 7638 JWK thumbprint. Unique.'],
                    'alg' => ['string', ['limit' => 16, 'null' => false], 'Signing algorithm: ES256 (current). Extensible.'],
                    'public_jwk' => ['text', ['null' => false], 'Public key as a JWK JSON object. Never contains private key material.'],
                    'status' => ['string', ['limit' => 16, 'null' => false], 'Lifecycle: active | retiring | retired | revoked. Drives JWKS publication.'],
                    'created_at' => ['string', $ts, 'Key generation time, ISO-8601 UTC string.'],
                    'activated_at' => ['string', $tsNull, 'When the key became active, ISO-8601 UTC string. NULL until activated.'],
                    'retired_at' => ['string', $tsNull, 'When the key was retired/revoked, ISO-8601 UTC string. NULL otherwise.'],
                ],
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->schemaComments() as $tableName => $spec) {
            $table = $this->table($tableName);
            foreach ($spec['columns'] as $column => [$type, $options, $comment]) {
                $table->changeColumn($column, $type, $options + ['comment' => $comment]);
            }
            $table->changeComment($spec['comment'])->update();
        }
    }

    public function down(): void
    {
        foreach ($this->schemaComments() as $tableName => $spec) {
            $table = $this->table($tableName);
            foreach ($spec['columns'] as $column => [$type, $options, $_comment]) {
                $table->changeColumn($column, $type, $options + ['comment' => '']);
            }
            $table->changeComment(null)->update();
        }
    }
}
