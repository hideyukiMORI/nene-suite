<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

/**
 * Writes a `SuiteEnvConfig` to a persistent store.
 * The Tier B implementation writes dotenv format to a file path.
 */
interface SuiteEnvWriterInterface
{
    public function write(SuiteEnvConfig $config): void;
}
