<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\ListOperatorsHandler;
use NeNeSuite\Auth\ListOperatorsUseCase;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\UnauthorizedException;
use NeNeSuite\Tenancy\ForbiddenException;
use NeNeSuite\Tests\Tenancy\OrganizationHttpTestSupport;
use PHPUnit\Framework\TestCase;

final class ListOperatorsHandlerTest extends TestCase
{
    use OrganizationHttpTestSupport;

    private const PATH = '/api/v1/operators';
    private const OP_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const NOW = '2026-01-01T00:00:00Z';

    public function testListsOperatorsForSuperadmin(): void
    {
        $response = $this->handler()->handle($this->request('GET', self::PATH, $this->superadminToken()));

        self::assertSame(200, $response->getStatusCode());
        $body = $this->decode($response);
        $operators = $body['operators'];
        self::assertIsArray($operators);
        self::assertCount(1, $operators);
        $operator = $operators[0];
        self::assertIsArray($operator);
        self::assertSame(self::OP_ID, $operator['id']);
        self::assertSame('operator@example.com', $operator['email']);
        self::assertArrayNotHasKey('passwordHash', $operator);
    }

    public function testRejectsNonSuperadminWithForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->handler()->handle($this->request('GET', self::PATH, $this->nonSuperadminToken()));
    }

    public function testRejectsUnauthenticated(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->handler()->handle($this->request('GET', self::PATH, null));
    }

    private function handler(): ListOperatorsHandler
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator(self::OP_ID, 'operator@example.com', 'hash', 'Example Operator', self::NOW, self::NOW));

        return new ListOperatorsHandler(
            $this->guard(),
            new ListOperatorsUseCase($operators),
            $this->jsonResponseFactory(),
        );
    }
}
