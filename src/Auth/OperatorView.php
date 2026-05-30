<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * camelCase Operator representation for HTTP (docs/openapi/openapi.yaml Operator
 * schema). Deliberately omits `passwordHash` — it must never leave the server.
 */
final readonly class OperatorView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Operator $operator): array
    {
        return [
            'id' => $operator->id,
            'email' => $operator->email,
            'displayName' => $operator->displayName,
        ];
    }
}
