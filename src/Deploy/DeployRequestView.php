<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * Maps {@see DeployRequest} to the wire shape of `#/components/schemas/DeployRequest`
 * (camelCase; `updatedAt` is internal only and not part of the contract).
 */
final readonly class DeployRequestView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(DeployRequest $request): array
    {
        return [
            'id' => $request->id,
            'service' => $request->service,
            'imageDigest' => $request->imageDigest,
            'status' => $request->status->value,
            'detail' => $request->detail,
            'requestedBy' => $request->requestedBy,
            'createdAt' => $request->createdAt,
            'completedAt' => $request->completedAt,
        ];
    }
}
