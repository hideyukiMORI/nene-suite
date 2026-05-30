<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testHashVerifiesCorrectPasswordAndRejectsWrongOne(): void
    {
        $hasher = new PasswordHasher();
        $hash = $hasher->hash('correct horse battery staple');

        self::assertNotSame('correct horse battery staple', $hash);
        self::assertTrue($hasher->verify('correct horse battery staple', $hash));
        self::assertFalse($hasher->verify('wrong password', $hash));
    }

    public function testVerifyDummyDoesNotThrow(): void
    {
        $this->expectNotToPerformAssertions();

        (new PasswordHasher())->verifyDummy('anything');
    }
}
