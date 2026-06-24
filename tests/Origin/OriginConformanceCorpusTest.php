<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginClientState;
use NeNeSuite\Origin\OriginReadModelVerifier;
use NeNeSuite\Origin\OriginTrustAnchor;
use NeNeSuite\Origin\OriginVerificationRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The cross-repo merge gate: Suite's {@see OriginReadModelVerifier} must reproduce every outcome in
 * the pinned Origin signed conformance corpus (tests/fixtures/origin-conformance — see PROVENANCE.md).
 * A divergence here is the exact risk the corpus removes.
 */
final class OriginConformanceCorpusTest extends TestCase
{
    private const string CORPUS_DIR = __DIR__ . '/../fixtures/origin-conformance';

    public function testCorpusIsVendoredAndComplete(): void
    {
        $expectations = self::readJson(self::CORPUS_DIR . '/expectations.json');
        $expectedCases = is_array($expectations['cases'] ?? null) ? $expectations['cases'] : [];

        self::assertGreaterThanOrEqual(15, count(self::caseDirectories()), 'expected the full vendored corpus');
        self::assertGreaterThanOrEqual(15, count($expectedCases), 'expected the expectations index');
    }

    #[DataProvider('cases')]
    public function testCaseReproducesExpectation(string $caseDirectory): void
    {
        $anchor = OriginTrustAnchor::fromArray(self::readJson(self::CORPUS_DIR . '/trust-anchor.json'));
        $case = self::readJson($caseDirectory . '/case.json');

        $expect = is_array($case['expect'] ?? null) ? $case['expect'] : [];
        $client = is_array($case['client'] ?? null) ? $case['client'] : [];

        $outcome = (new OriginReadModelVerifier())->verify(
            new FilesystemOriginObjectStore($caseDirectory),
            $anchor,
            OriginClientState::fromArray($client),
            OriginVerificationRequest::fromArray($case),
        );

        $id = basename($caseDirectory);
        $context = sprintf('%s — got reason=%s stage=%s (%s)', $id, $outcome->reason->value, $outcome->stage, $outcome->message);

        self::assertSame((bool) ($expect['accepted'] ?? null), $outcome->accepted, $context);
        self::assertSame((string) ($expect['reason'] ?? ''), $outcome->reason->value, $context);

        if (isset($expect['stage'])) {
            self::assertSame((string) $expect['stage'], $outcome->stage, $context);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function cases(): iterable
    {
        foreach (self::caseDirectories() as $directory) {
            yield basename($directory) => [$directory];
        }
    }

    /**
     * @return list<string>
     */
    private static function caseDirectories(): array
    {
        $directories = glob(self::CORPUS_DIR . '/cases/*', GLOB_ONLYDIR);
        if ($directories === false) {
            return [];
        }
        sort($directories);

        return $directories;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        $bytes = file_get_contents($path);
        self::assertIsString($bytes, sprintf('corpus file not readable: %s', $path));

        $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded, sprintf('corpus file is not a JSON object: %s', $path));

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
