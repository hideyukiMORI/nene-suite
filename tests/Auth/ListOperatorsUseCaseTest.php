<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\ListOperatorsUseCase;
use NeNeSuite\Auth\Operator;
use PHPUnit\Framework\TestCase;

final class ListOperatorsUseCaseTest extends TestCase
{
    public function testReturnsOperatorsOldestFirst(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save(new Operator('01J8XR0G7Q9V2H7K3N5M0B8TCB', 'b@example.com', 'hash', 'B', '2026-01-02T00:00:00Z', '2026-01-02T00:00:00Z'));
        $operators->save(new Operator('01J8XR0G7Q9V2H7K3N5M0B8TCA', 'a@example.com', 'hash', 'A', '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z'));

        $output = (new ListOperatorsUseCase($operators))->execute();

        self::assertCount(2, $output->operators);
        self::assertSame('a@example.com', $output->operators[0]->email);
        self::assertSame('b@example.com', $output->operators[1]->email);
    }

    public function testReturnsEmptyListWhenNoOperators(): void
    {
        $output = (new ListOperatorsUseCase(new InMemoryOperatorRepository()))->execute();

        self::assertSame([], $output->operators);
    }
}
