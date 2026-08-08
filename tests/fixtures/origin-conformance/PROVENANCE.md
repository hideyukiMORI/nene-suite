# Origin conformance corpus (vendored, pinned)

This is a **pinned, verbatim copy** of the NeNe Origin signed conformance corpus — the cross-repo
merge gate for the profiled-TUF read contract (Origin ADR 0006 §8; consumed by Suite per
[ADR 0017](../../../docs/adr/0017-origin-client-consumption-contract.md)).

- **Source**: `nene-origin` `docs/spec/conformance/` (`trust-anchor.json`, `expectations.json`, `cases/`).
- **Pinned at**: `nene-origin@d68bd9740e971b1698a4d7b566068a918a159536` (PR #615, "feat(spec): feed の負ケースを corpus に足し、update の gen を product 単位採番へ直す" — closes Origin #556/#608). Compare the **full** SHA when re-syncing: the previous pin `d5882cff20084bc260572a427626d08087cd0282` shares its first four characters with an unrelated candidate commit.
- **Case count**: 19. Previous pin `d5882cff…` (PR #114, "feat(spec): signed conformance corpus + reference verifier"; Origin huddle `origin-0009`) carried 15; this pin adds `neg-feed-body-hash-mismatch`, `neg-feed-disallowed-alg`, `valid-feed-en`, and `valid-update-channel-switch`. The first of those is the corpus's only **non-`unreachable` feed failure** — before it existed, every `neg-*` case lacked a feeds path, so a feed query read 404 and no feed-body defect could be expressed at all (Origin #556). The 15 pre-existing cases are byte-identical across the re-sync.
- **Keys**: dev/test Ed25519 only, derived from public labels. **No production key material.**

`tests/Origin/OriginConformanceCorpusTest.php` runs every case through Suite's
`OriginReadModelVerifier` and asserts the declared `accepted` / `reason` / `stage`. Suite's verifier
**must reproduce every outcome here** — a divergence is the exact risk the gate removes.

## Re-syncing

When Origin's contract changes, re-copy the three paths above from the new pinned commit and update
the pin line. Do **not** hand-edit fixtures — they are signed bytes and must stay byte-identical to
Origin's `php bin/conformance generate` output.
