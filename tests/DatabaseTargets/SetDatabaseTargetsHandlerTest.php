<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseTargets;

use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationException;
use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseTargets\SetDatabaseTargetsHandler;
use NeNeSuite\DatabaseTargets\SetDatabaseTargetsUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class SetDatabaseTargetsHandlerTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';

    public function testConfiguresTargetsAndReturnsSession(): void
    {
        $response = $this->handler()->handle($this->request(self::SESSION_ID, [
            'targets' => [
                ['catalogId' => 'nene-invoice', 'mode' => 'adopt', 'server' => 'legacy-db.internal', 'name' => 'invoice_prod'],
            ],
        ]));

        self::assertSame(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertIsArray($body['databaseTargets']);
        self::assertCount(1, $body['databaseTargets']);
        self::assertSame('nene-invoice', $body['databaseTargets'][0]['catalogId']);
        self::assertSame('adopt', $body['databaseTargets'][0]['mode']);
        self::assertSame('legacy-db.internal', $body['databaseTargets'][0]['server']);
        self::assertSame('invoice_prod', $body['databaseTargets'][0]['name']);
    }

    public function testRejectsMalformedIdWithNotFound(): void
    {
        $this->expectException(InstallSessionNotFoundException::class);

        $this->handler()->handle($this->request('not-a-ulid', ['targets' => []]));
    }

    public function testRejectsNonListTargets(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler()->handle($this->request(self::SESSION_ID, ['targets' => ['nope' => 1]]));
    }

    public function testRejectsEntryMissingCatalogId(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler()->handle($this->request(self::SESSION_ID, ['targets' => [['mode' => 'adopt']]]));
    }

    public function testRejectsUnknownMode(): void
    {
        $this->expectException(ValidationException::class);

        $this->handler()->handle($this->request(self::SESSION_ID, [
            'targets' => [['catalogId' => 'nene-invoice', 'mode' => 'mirror']],
        ]));
    }

    private function handler(): SetDatabaseTargetsHandler
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save(new InstallSession(
            id: self::SESSION_ID,
            suiteId: '01J8XRDEV000000000000000ZA',
            status: InstallSessionStatus::InProgress,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice'],
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: null,
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
        ));

        $psr17 = new Psr17Factory();

        return new SetDatabaseTargetsHandler(
            new SetDatabaseTargetsUseCase(
                $sessions,
                new DatabaseTargetFactory(new AppDatabaseNamer()),
                new RecordingSuiteAuditRecorder(),
            ),
            new JsonResponseFactory($psr17, $psr17),
            new RequestIdHolder(),
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $id, array $body): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        return $factory->createServerRequest('PUT', "/api/v1/install-sessions/{$id}/database-targets")
            ->withAttribute(Router::PARAMETERS_ATTRIBUTE, ['installSessionId' => $id])
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream((string) json_encode($body)));
    }
}
