<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

/**
 * camelCase HTTP representation (docs/openapi/openapi.yaml InstalledApp schema).
 */
final readonly class InstalledAppView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(InstalledApp $app): array
    {
        return [
            'catalogId' => $app->catalogId,
            'name' => $app->name,
            'publicUrl' => $app->publicUrl,
            'databaseName' => $app->databaseName,
            'ssotRole' => $app->ssotRole->value,
        ];
    }
}
