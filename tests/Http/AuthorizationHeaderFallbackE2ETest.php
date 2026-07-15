<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Auth\TokenIssuerInterface;
use NeNeSuite\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * End-to-end proof that the opt-in X-Authorization fallback receiver (NENE2 #1558 /
 * ADR 0019) is wired into this product's runtime pipeline.
 *
 * Front-end fleet clients (`@hideyukimori/nene2-client` v1.1.0) mirror every bearer
 * token into `X-Authorization: Bearer <token>` so that shared hosting (HETEML-type
 * Tier A) — where an upstream proxy strips the standard `Authorization` header before
 * PHP sees it — can still authenticate. `RuntimeServiceProvider` enables the receiver
 * via `enableAuthorizationHeaderFallback: true`, so the framework's
 * AuthorizationHeaderFallbackMiddleware restores `Authorization` from the mirror
 * (only when `Authorization` is absent/empty) unconditionally at the head of the
 * middleware stack — before any handler runs, including this product's own
 * request-scoped bearer check.
 *
 * Unlike NeNe Field, this product does not run NENE2's own bearer auth middleware:
 * every authenticated endpoint reads `Authorization` itself via
 * {@see \NeNeSuite\Auth\BearerTokenAuthenticator} (see its docblock — "used ... in
 * place of a global middleware while only a few endpoints require auth"). That
 * authenticator maps BOTH a missing and an invalid token to the same
 * `UnauthorizedException` (401 `.../unauthorized`), so — unlike Field, which can
 * distinguish `error="missing_token"` vs `error="invalid_token"` on a bearer
 * middleware challenge header — a mirror-only request with an invalid token cannot be
 * distinguished here from a request with no credential at all. What CAN be proven at
 * this layer: (a) a *valid* token delivered only via the mirror reaches and passes
 * authentication, (b) an invalid mirror is correctly rejected (not blindly trusted),
 * (c) a valid standard `Authorization` header still wins when an invalid mirror is
 * also present (no regression on hosting that delivers the standard header intact).
 *
 * `GET /api/v1/organizations` (`ListOrganizationsHandler`) is bearer-protected and,
 * being a platform-superadmin-only endpoint, resolves the caller entirely from JWT
 * claims via {@see \NeNeSuite\Tenancy\SuperadminGuard} — no org path segment, no
 * membership/session DB lookup, no seeded tenant. The superadmin check runs strictly
 * after authentication and strictly before the (DB-backed) list use case, so a
 * non-superadmin token isolates "authenticated but forbidden" (403) from
 * "not authenticated" (401) without touching the database — the signal this test
 * suite uses to prove the credential reached the auth check.
 *
 * The tests fail if the opt-in flag is removed from RuntimeServiceProvider: a
 * mirror-only request would then never restore `Authorization`, `verifiedClaims()`
 * would see no header, and every mirror-only request would be rejected 401
 * `unauthorized` — including the ones this suite asserts reach 403.
 */
final class AuthorizationHeaderFallbackE2ETest extends TestCase
{
    private const PROTECTED_PATH = '/api/v1/organizations';

    private RequestHandlerInterface $app;
    private TokenIssuerInterface $issuer;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        $app = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $app);
        $this->app = $app;

        $issuer = $container->get(TokenIssuerInterface::class);
        self::assertInstanceOf(TokenIssuerInterface::class, $issuer);
        $this->issuer = $issuer;
    }

    /**
     * The mirror end-to-end proof: a valid (non-superadmin) bearer token supplied
     * ONLY in the `X-Authorization` header (no standard `Authorization`) is restored
     * by the fallback receiver and reaches `SuperadminGuard::ensure()` — proven by a
     * 403 `forbidden` response (the guard authenticated the token, then rejected on
     * the *authorization* dimension), not a 401 `unauthorized` (which is what a
     * request the auth layer never saw a credential for would get). The claims
     * include the A6 context keys (`org_external_id`/`role`/`superadmin`) so
     * `BearerTokenAuthenticator::principal()` resolves the principal from the token
     * itself, with no DB-backed session-context lookup.
     */
    public function test_valid_token_in_mirror_only_reaches_the_authenticated_guard(): void
    {
        $token = $this->issuer->issue([
            'sub' => '01J8XR0G7Q9V2H7K3N5M0B8TCA',
            'org_external_id' => null,
            'role' => null,
            'superadmin' => false,
            'exp' => time() + 3600,
        ]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer ' . $token);

        $response = $this->app->handle($request);

        self::assertSame(
            403,
            $response->getStatusCode(),
            'A valid token mirrored only into X-Authorization must reach the superadmin guard '
                . '(403 forbidden = authenticated, not authorized) rather than fail authentication (401).',
        );
    }

    /**
     * With NO credential in either header, the auth check reports 401 `unauthorized`.
     * This is the baseline / control — and also the response a mirror-only request
     * would get if the opt-in fallback were disabled (see class docblock: this
     * product cannot distinguish "invalid mirrored token" from "no credential at
     * all", so this baseline doubles as that boundary).
     */
    public function test_no_credential_yields_unauthorized(): void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', self::PROTECTED_PATH);

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertStringEndsWith('unauthorized', (string) $body['type']);
    }

    /**
     * An INVALID token in `X-Authorization` only is rejected 401 `unauthorized` — the
     * fallback receiver restores it into `Authorization`, but the token itself does
     * not verify, so authentication fails the same way it would for a missing
     * credential. The opt-in receiver does not weaken verification: it only changes
     * where the framework looks for a credential, not whether an invalid one passes.
     */
    public function test_invalid_token_in_mirror_only_is_rejected_as_unauthorized(): void
    {
        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertStringEndsWith('unauthorized', (string) $body['type']);
    }

    /**
     * The standard header still wins when both are present (byte-for-byte behaviour
     * unchanged on hosting that delivers `Authorization`): a valid standard token
     * reaches the superadmin guard (403, same as the mirror-only proof) even when an
     * invalid mirror is also sent. If the receiver wrongly preferred the mirror, the
     * invalid token would fail verification and the response would be 401
     * `unauthorized` instead; 403 proves standard-header precedence.
     */
    public function test_standard_authorization_header_takes_precedence_over_mirror(): void
    {
        $token = $this->issuer->issue([
            'sub' => '01J8XR0G7Q9V2H7K3N5M0B8TCA',
            'org_external_id' => null,
            'role' => null,
            'superadmin' => false,
            'exp' => time() + 3600,
        ]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame(403, $response->getStatusCode());
    }
}
