<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\FilesystemOriginObjectStore;
use NeNeSuite\Origin\OriginObjectStore;
use NeNeSuite\Origin\OriginObjectStoreProvider;

/**
 * A mirror list backed by corpus directories: each entry is one "mirror" serving the case directory
 * it points at (a nonexistent path models an unreachable mirror). Records which mirrors were
 * actually asked for, so a test can prove the walk stopped at the first one that verified.
 */
final class FakeMirrorObjectStoreProvider implements OriginObjectStoreProvider
{
    /** @var list<string> */
    public array $requested = [];

    /**
     * @param array<string, string> $mirrors base URL (label) => corpus case directory
     */
    public function __construct(private readonly array $mirrors)
    {
    }

    public function stores(): iterable
    {
        foreach ($this->mirrors as $baseUrl => $directory) {
            $this->requested[] = $baseUrl;

            yield $baseUrl => $this->store($directory);
        }
    }

    private function store(string $directory): OriginObjectStore
    {
        return new FilesystemOriginObjectStore($directory);
    }
}
