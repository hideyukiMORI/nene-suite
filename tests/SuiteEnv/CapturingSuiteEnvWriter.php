<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteEnv;

use NeNeSuite\SuiteEnv\SuiteEnvConfig;
use NeNeSuite\SuiteEnv\SuiteEnvWriterInterface;

/**
 * Test double that captures the last written SuiteEnvConfig for assertions.
 */
final class CapturingSuiteEnvWriter implements SuiteEnvWriterInterface
{
    public ?SuiteEnvConfig $written = null;

    public function write(SuiteEnvConfig $config): void
    {
        $this->written = $config;
    }
}
