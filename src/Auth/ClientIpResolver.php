<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Resolves the real client IP for rate-limiting (Phase B1.2). By default the socket address
 * (`REMOTE_ADDR`) is authoritative and `X-Forwarded-For` is ignored — a spoofable header must
 * never be trusted from an arbitrary peer. Only when the socket address is itself a configured
 * trusted proxy (`NENE_SUITE_TRUSTED_PROXIES`, CIDR list — e.g. the shared Caddy network) is the
 * forwarded chain consulted: the client is the rightmost `X-Forwarded-For` hop that is not itself
 * a trusted proxy.
 */
final readonly class ClientIpResolver
{
    /**
     * @param list<string> $trustedProxyCidrs
     */
    public function __construct(
        private array $trustedProxyCidrs,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function parseTrustedProxies(string $raw): array
    {
        $cidrs = [];

        foreach (explode(',', $raw) as $entry) {
            $trimmed = trim($entry);

            if ($trimmed !== '') {
                $cidrs[] = $trimmed;
            }
        }

        return $cidrs;
    }

    public function resolve(ServerRequestInterface $request): string
    {
        $params = $request->getServerParams();
        $remoteAddr = is_string($params['REMOTE_ADDR'] ?? null) ? $params['REMOTE_ADDR'] : '';

        if ($this->trustedProxyCidrs === [] || !$this->isTrusted($remoteAddr)) {
            return $remoteAddr;
        }

        $forwarded = $request->getHeaderLine('X-Forwarded-For');

        if ($forwarded === '') {
            return $remoteAddr;
        }

        $hops = array_map('trim', explode(',', $forwarded));

        for ($i = count($hops) - 1; $i >= 0; $i--) {
            $hop = $hops[$i];

            if ($hop !== '' && !$this->isTrusted($hop)) {
                return $hop;
            }
        }

        return $remoteAddr;
    }

    private function isTrusted(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        foreach ($this->trustedProxyCidrs as $cidr) {
            if ($this->matchesCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function matchesCidr(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bitsRaw] = explode('/', $cidr, 2);
        $bits = (int) $bitsRaw;

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;

        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $wholeBytes = intdiv($bits, 8);
        $remainderBits = $bits % 8;

        if ($wholeBytes > 0 && substr($ipBin, 0, $wholeBytes) !== substr($subnetBin, 0, $wholeBytes)) {
            return false;
        }

        if ($remainderBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainderBits)) & 0xFF;

        return (ord($ipBin[$wholeBytes]) & $mask) === (ord($subnetBin[$wholeBytes]) & $mask);
    }
}
