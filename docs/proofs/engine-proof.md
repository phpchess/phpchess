# Engine proof — GarboChess candidate-move scorer (C0-2)

**Question**: can GarboChess (BSD-3-Clause, 82 KB of plain JavaScript) give the computer opponent's selection layer (AI-02) an honest score for every legal move, at a fixed depth, fast enough in a phone's browser?

**Set-up**:
- the scorer `public/assets/js/engine/garbochess-scorer.worker.js`, running in a Web Worker;
- GarboChess unchanged in `public/assets/vendor/garbochess/`;
- the proof page `public/proof/engine/`, which is temporary and removed in C3-2;
- five test positions, each scored at depths 1 to 6. Two of them have a known right answer.

## Run 1 — desktop, 2026-11-22 [measured]
- **Browser**: Chrome 156 in the Claude desktop app's browser pane, on Windows 11.
- **Known answers found: 12 of 12.**
  - **Hanging queen**: winning the queen (Nf3xh4) scored best at every depth, about +12 pawns. The scorer also marked 1.Ke2?? as allowing mate in one, which is correct: 1...Qxe4 is mate.
  - **Back-rank mate in one**: Ra8# was found and marked "mate in 1" at every depth.
- **Speed** (scoring *all* moves of the position, slowest of the five positions):

  | Depth | Slowest position |
  |---|---|
  | 4 | 42 ms |
  | 5 | 163 ms |
  | 6 | 439 ms |

- **Bad moves stand out clearly**: in every position, moves that lose material score several pawns below the good ones (e.g. Bxe6 in the Italian, about -5.5). That separation is what the selection layer needs to avoid random blunders.
- **Scores move between odd and even depths.** For example, in the start position depth 4 gives 0.00 for several moves and depth 5 gives +0.36. The opponent must therefore use one fixed depth per character, never mix depths, so that its candidate scores are comparable.

## Still to measure
- **A real phone**: phones are typically several times slower than a desktop [unmeasured assumption]. If a slow phone is 10× slower, depth 4 would take about 0.4 s and depth 5 about 1.6 s per move. That would still be playable for a ~600 opponent, which is expected to need depth 3–5 [unmeasured assumption, to settle in C3-2's calibration].
- **The comparison against Stockfish** on the same positions: whether both agree on which moves are clearly bad. It is only needed if the phone measurement or the calibration shows GarboChess falling short.

## Conclusion so far
GarboChess fits the architecture: one readable file, no network access, no dependencies, an honest score for every candidate move, and mates and hanging pieces found at every depth tested. It stays the first engine (AI-01), behind the swappable interface. The phone measurement decides whether depth 5 or only depth 4 is the ceiling.
