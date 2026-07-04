<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * Resolves {@see DeployAgentConfig} from the environment. Fail-closed like the edition flag:
 * the capability is enabled only when `NENE_SUITE_DEPLOY_AGENT_ENABLED` is the exact string
 * `1` AND `NENE_SUITE_DEPLOY_AGENT_KEY` carries at least {@see self::MIN_KEY_LENGTH} bytes
 * (the same posture as NENE_SUITE_JWT_SECRET — a weak pairing secret must not silently arm a
 * deployment surface). Anything else resolves to disabled; there is no grace mode.
 */
final readonly class DeployAgentConfigResolver
{
    private const ENV_ENABLED = 'NENE_SUITE_DEPLOY_AGENT_ENABLED';

    private const ENV_KEY = 'NENE_SUITE_DEPLOY_AGENT_KEY';

    private const MIN_KEY_LENGTH = 32;

    public function resolve(): DeployAgentConfig
    {
        if (self::env(self::ENV_ENABLED) !== '1') {
            return DeployAgentConfig::disabled();
        }

        $key = self::env(self::ENV_KEY) ?? '';

        if (strlen($key) < self::MIN_KEY_LENGTH) {
            return DeployAgentConfig::disabled();
        }

        return new DeployAgentConfig(true, $key);
    }

    private static function env(string $name): ?string
    {
        $value = $_SERVER[$name] ?? $_ENV[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
