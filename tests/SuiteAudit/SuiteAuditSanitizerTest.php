<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use PHPUnit\Framework\TestCase;

final class SuiteAuditSanitizerTest extends TestCase
{
    public function testRedactsSecretBearingKeysCaseInsensitively(): void
    {
        $sanitizer = new SuiteAuditSanitizer();

        $result = $sanitizer->sanitize([
            'DB_NAME' => 'nene_suite',
            'DB_PASSWORD' => 'hunter2',
            'NENE_SUITE_JWT_SECRET' => 'top-secret',
            'Authorization' => 'Bearer abc',
            'service_token' => 'xyz',
            'public_url' => 'https://example.com/',
        ]);

        self::assertSame('nene_suite', $result['DB_NAME']);
        self::assertSame('https://example.com/', $result['public_url']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $result['DB_PASSWORD']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $result['NENE_SUITE_JWT_SECRET']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $result['Authorization']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $result['service_token']);
    }

    public function testRedactsNestedSecrets(): void
    {
        $sanitizer = new SuiteAuditSanitizer();

        $result = $sanitizer->sanitize([
            'env' => [
                'DB_HOST' => 'db',
                'DB_PASSWORD' => 'secret',
            ],
        ]);

        self::assertIsArray($result['env']);
        self::assertSame('db', $result['env']['DB_HOST']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $result['env']['DB_PASSWORD']);
    }
}
