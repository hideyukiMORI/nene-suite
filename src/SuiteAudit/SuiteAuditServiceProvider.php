<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use LogicException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\DependencyInjection\ContainerBuilder;
use Nene2\DependencyInjection\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

final readonly class SuiteAuditServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerBuilder $builder): void
    {
        $builder
            ->set(SuiteAuditSanitizer::class, static fn (ContainerInterface $container): SuiteAuditSanitizer => new SuiteAuditSanitizer())
            ->set(
                SuiteAuditRecorderInterface::class,
                static function (ContainerInterface $container): SuiteAuditRecorderInterface {
                    $query = $container->get(DatabaseQueryExecutorInterface::class);
                    $sanitizer = $container->get(SuiteAuditSanitizer::class);

                    if (!$query instanceof DatabaseQueryExecutorInterface) {
                        throw new LogicException('Database query executor service is invalid.');
                    }

                    if (!$sanitizer instanceof SuiteAuditSanitizer) {
                        throw new LogicException('Suite audit sanitizer service is invalid.');
                    }

                    return new PdoSuiteAuditRecorder($query, $sanitizer);
                },
            );
    }
}
