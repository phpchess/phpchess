# bbpPairings reference tournaments (test fixtures only)

- Source: https://github.com/BieremaBoyzProgramming/bbpPairings, folder `test/tests/` (branch master, fetched 2026-11-26).
- Licence: Apache License 2.0 (`LICENSE-Apache-2.0.txt`), Copyright 2016-2026 Jeremy Bierema.
- Files: `*.input` are tournaments in FIDE's TRF format; `*.output.expected` are the next round's
  pairings by bbpPairings (an FIDE-endorsed Dutch engine): first the number of boards, then
  "white black" per board, "player 0" for the bye.
- issue_15 was left out: its expected file is a run log, not a pairing.
- Used by `tests/Tournament/PublishedSwissTest.php` (A99). Never shipped: `tests/` is not in the release (K10).
