<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

use Dotenv\Dotenv;
use Nene2\DependencyInjection\ContainerBuilder;
use Psr\Container\ContainerInterface;

final readonly class RuntimeContainerFactory
{
    public function __construct(
        private ?string $projectRoot = null,
    ) {
    }

    public function create(): ContainerInterface
    {
        $projectRoot = $this->projectRoot ?? dirname(__DIR__, 2);

        // Load .env once (immutable, into $_SERVER/$_ENV) so every env read —
        // framework config (ConfigLoader) and suite ids / problem base — is
        // consistent and order-independent.
        if (is_file($projectRoot . '/.env')) {
            Dotenv::createImmutable($projectRoot)->safeLoad();
        }

        return (new ContainerBuilder())
            ->value(RuntimeServiceProvider::PROJECT_ROOT, $projectRoot)
            ->addProvider(new RuntimeServiceProvider())
            ->build();
    }
}
