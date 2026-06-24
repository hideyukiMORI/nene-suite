<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * camelCase HTTP representation of an {@see OriginUpdateSignal} (docs/openapi/openapi.yaml
 * OriginUpdate schema).
 */
final readonly class OriginUpdateSignalView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(OriginUpdateSignal $signal): array
    {
        return [
            'product' => $signal->product,
            'channel' => $signal->channel,
            'installedVersion' => $signal->installedVersion,
            'status' => $signal->status->value,
            'latestVersion' => $signal->latestVersion,
            'minSupportedVersion' => $signal->minSupportedVersion,
            'changelogUrl' => $signal->changelogUrl,
            'releasedAt' => $signal->releasedAt,
            'freshness' => $signal->freshness?->value,
            'reason' => $signal->reason,
            'warnings' => $signal->warnings,
        ];
    }
}
