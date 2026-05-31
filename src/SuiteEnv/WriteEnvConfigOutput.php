<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

final readonly class WriteEnvConfigOutput
{
    /**
     * @param array<string, string> $sanitizedEnvMap NENE_SUITE_* map with secrets redacted
     */
    public function __construct(
        public array $sanitizedEnvMap,
    ) {
    }
}
