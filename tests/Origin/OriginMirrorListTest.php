<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginMirrorList;
use PHPUnit\Framework\TestCase;

/**
 * The embedded default list is a contract with Origin (`nene-origin/docs/spec/mirrors.md` §2):
 * ordered, HETEML primary then ConoHa secondary. Changing it here without the Origin-side registry
 * change is the failure this test exists to catch.
 */
final class OriginMirrorListTest extends TestCase
{
    public function testEmbeddedDefaultIsThePublishedOrderedProductionList(): void
    {
        $mirrors = OriginMirrorList::embeddedDefault();

        self::assertSame([
            'https://nene-origin.dev',
            'https://m2.nene-origin.dev',
        ], $mirrors->baseUrls);
        self::assertFalse($mirrors->isEmpty());
    }

    public function testExclusiveKeepsExactlyOneBaseAndTrimsTheTrailingSlash(): void
    {
        $mirrors = OriginMirrorList::exclusive('https://origin.example.com/');

        self::assertSame(['https://origin.example.com'], $mirrors->baseUrls);
    }

    public function testExclusiveOfABlankValueIsEmpty(): void
    {
        self::assertTrue(OriginMirrorList::exclusive('  ')->isEmpty());
    }

    public function testNoneIsEmpty(): void
    {
        self::assertSame([], OriginMirrorList::none()->baseUrls);
        self::assertTrue(OriginMirrorList::none()->isEmpty());
    }
}
