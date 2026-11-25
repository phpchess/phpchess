# C3-2 human profile: people and our computer players

Written by `node tools/human-profile.mjs report` (AI-05, decision A55). Do not edit by hand.

- **Judge**: GarboChess depth 5 for both; the first 8 half-moves are left out.
- **People**: Lichess blitz games, December 2022 (CC0).
- **Each cell**: average loss per move (centipawns) · mistakes (≥ 1 pawn) · blunders (≥ 3 pawns) · giveaways.

- **Plays like**: the rating of the people who lose as much per move as our players of that band
  (from the smoothed people's curve; "–" when it is outside 600–2100).

| Band | People | Our players | Our players play like |
|---|---|---|---|
| 300 | – | 95 · 15.9 % · 9.9 % · 6.3 % | – |
| 400 | – | 95 · 16.9 % · 10.2 % · 6.3 % | – |
| 500 | – | 102 · 18 % · 10.8 % · 6.3 % | – |
| 600 | 97 · 21.2 % · 11.4 % · 6 % | 91 · 16.2 % · 10.5 % · 5.6 % | 731 |
| 700 | 89 · 19.4 % · 10.5 % · 3.8 % | 76 · 13.1 % · 7.8 % · 4.5 % | 950 |
| 800 | 95 · 21.6 % · 11.4 % · 3.8 % | 49 · 10.4 % · 4.7 % · 3.8 % | 1670 |
| 900 | 71 · 16.8 % · 7.6 % · 2.2 % | 47 · 10.4 % · 4.8 % · 2.7 % | 1733 |
| 1000 | 74 · 16 % · 8.3 % · 2.6 % | – | – |
| 1100 | 71 · 15.1 % · 7.7 % · 2.2 % | 44 · 7.9 % · 4.2 % · 3 % | 1827 |
| 1200 | 56 · 13 % · 5.4 % · 1.8 % | 39 · 8 % · 3.8 % · 3 % | 2100 |
| 1300 | 62 · 14 % · 6.5 % · 1.5 % | – | – |
| 1400 | 54 · 11.9 % · 5.1 % · 1.4 % | 31 · 5.7 % · 3.2 % · 2.6 % | – |
| 1500 | 52 · 13.2 % · 4.9 % · 1.5 % | 28 · 4.7 % · 2.7 % · 2.7 % | – |
| 1600 | 52 · 11.7 % · 4.5 % · 1.3 % | – | – |
| 1700 | 50 · 10.9 % · 3.8 % · 1.1 % | 22 · 3.8 % · 2.4 % · 2.5 % | – |
| 1800 | 42 · 9.2 % · 3.5 % · 0.8 % | 19 · 3 % · 1.7 % · 1.8 % | – |
| 1900 | 43 · 9.3 % · 3.7 % · 0.9 % | – | – |
| 2000 | 39 · 8 % · 2.9 % · 0.8 % | 19 · 2.8 % · 1.7 % · 1.7 % | – |
| 2100 | 39 · 8.6 % · 2.8 % · 0.6 % | – | – |

## Upsets in the tuning games (information only)

None.
