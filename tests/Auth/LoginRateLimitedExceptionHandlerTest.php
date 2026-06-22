<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Error\ProblemDetailsResponseFactory;
use NeNeSuite\Auth\InvalidCredentialsException;
use NeNeSuite\Auth\LoginRateLimitedException;
use NeNeSuite\Auth\LoginRateLimitedExceptionHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class LoginRateLimitedExceptionHandlerTest extends TestCase
{
    public function testSupportsOnlyTheRateLimitedException(): void
    {
        $handler = $this->handler();

        self::assertTrue($handler->supports(new LoginRateLimitedException(900)));
        self::assertFalse($handler->supports(new InvalidCredentialsException()));
    }

    public function testRendersA429WithRetryAfterHeader(): void
    {
        $response = $this->handler()->handle(
            new LoginRateLimitedException(600),
            (new Psr17Factory())->createServerRequest('POST', '/api/v1/auth/session'),
        );

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('600', $response->getHeaderLine('Retry-After'));

        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame(429, $body['status'] ?? null);
        self::assertStringContainsString('rate-limited', (string) ($body['type'] ?? ''));
    }

    private function handler(): LoginRateLimitedExceptionHandler
    {
        $psr17 = new Psr17Factory();

        return new LoginRateLimitedExceptionHandler(
            new ProblemDetailsResponseFactory($psr17, $psr17, 'https://nene-suite.dev/problems/'),
        );
    }
}
