<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

/**
 * Derives a URL-safe organization slug from a free-text org name (`NENE_SUITE_ORG_NAME`,
 * commonly Japanese — so often with no ASCII alphanumerics).
 *
 * The result matches `CreateOrganizationUseCase`'s slug rule
 * (`^[a-z0-9]+(?:-[a-z0-9]+)*$`, ≤160 chars). When the name yields nothing usable, a
 * deterministic `org-<seed-hash>` fallback is returned so re-running the installer
 * derives the same slug and `findBySlug` recovers the same organization.
 */
final readonly class OrgSlugDeriver
{
    private const MAX_LENGTH = 160;

    public static function derive(string $orgName, string $seed): string
    {
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($orgName));
        $slug = trim($slug, '-');

        if (mb_strlen($slug) > self::MAX_LENGTH) {
            $slug = rtrim(mb_substr($slug, 0, self::MAX_LENGTH), '-');
        }

        if ($slug === '') {
            return 'org-' . substr(hash('sha256', $seed), 0, 12);
        }

        return $slug;
    }
}
