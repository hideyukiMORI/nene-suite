<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Raised by {@see FederationKeyPreflight} at hosted-edition boot when the federation signing key is
 * missing, unloadable, or inconsistent with the published active key. The entrypoint preflight maps
 * it to a non-zero exit so the container fails closed instead of serving with a broken IdP.
 */
final class FederationKeyPreflightException extends RuntimeException
{
}
