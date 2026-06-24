# Origin conformance corpus (vendored, pinned)

This is a **pinned, verbatim copy** of the NeNe Origin signed conformance corpus — the cross-repo
merge gate for the profiled-TUF read contract (Origin ADR 0006 §8; consumed by Suite per
[ADR 0017](../../../docs/adr/0017-origin-client-consumption-contract.md)).

- **Source**: `nene-origin` `docs/spec/conformance/` (`trust-anchor.json`, `expectations.json`, `cases/`).
- **Pinned at**: `nene-origin@9812c2b` ("feat(spec): signed conformance corpus + reference verifier (PR-4)").
- **Keys**: dev/test Ed25519 only, derived from public labels. **No production key material.**

`tests/Origin/OriginConformanceCorpusTest.php` runs every case through Suite's
`OriginReadModelVerifier` and asserts the declared `accepted` / `reason` / `stage`. Suite's verifier
**must reproduce every outcome here** — a divergence is the exact risk the gate removes.

## Re-syncing

When Origin's contract changes, re-copy the three paths above from the new pinned commit and update
the pin line. Do **not** hand-edit fixtures — they are signed bytes and must stay byte-identical to
Origin's `php bin/conformance generate` output.
