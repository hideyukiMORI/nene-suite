<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use RuntimeException;

/**
 * Raised when the catalog source cannot be read or is structurally invalid.
 * This is an operator/deployment fault (missing or corrupt catalog), surfaced
 * as a 500 — not a client error.
 */
final class CatalogReadException extends RuntimeException
{
}
