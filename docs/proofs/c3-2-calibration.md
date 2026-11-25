# C3-2 calibration result

Written by `node tools/calibrate.mjs` (AI-05, decisions A51 and A53). Do not edit by hand.

- **Anchor**: `random` is fixed at Elo 0 (random legal moves).
- **Games**: 5441, about 41.5 hours of engine time.
- **Range**: 95 %, from each player's own games; ratings far from the anchor are estimates (no human anchors, A51).

## The ladder

| Anchor | Elo | 95 % range | Games |
|---|---|---|---|
| random | 0 | 0 to 0 | 510 |
| capture-first | 21 | -25 to 67 | 731 |
| garbo-d1-m300 | 879 | 827 to 931 | 1040 |
| garbo-d1-m100 | 1171 | 1132 to 1209 | 1040 |
| garbo-d1 | 1645 | 1611 to 1679 | 960 |
| garbo-d2-m100 | 1490 | 1455 to 1525 | 920 |
| garbo-d2 | 1905 | 1874 to 1936 | 960 |
| garbo-d3 | 2099 | 2067 to 2131 | 880 |
| garbo-d4 | 2308 | 2276 to 2340 | 800 |
| garbo-d5 | 2477 | 2447 to 2508 | 800 |
| garbo-d6 | 2632 | 2599 to 2665 | 600 |
| garbo-d7 | 2783 | 2741 to 2824 | 400 |

## The computer players

Listed = the Elo in computer-players.json (never changed, A53); measured = this run.

| Player | Listed | Measured | 95 % range | Games | Listed inside range |
|---|---|---|---|---|---|
| #0 Wood Pusher | 0 | 23 | -84 to 129 | 41 | yes |
| #1 Toby Jenkins | 300 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #2 Camila Morales | 300 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #3 Gordon Whitaker | 300 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #4 Kofi Mensah | 300 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #5 Anja Lindqvist | 400 | 6448 | 6448 to 6448 | 20 | no |
| #6 Tariq Al-Mansoor | 400 | 647 | 159 to 1135 | 20 | yes |
| #7 Mei-Ling Zhou | 400 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #8 Mateo Silva | 400 | 6400 | -7225699172 to 7225711972 | 20 | yes |
| #9 Brenda Galloway | 500 | 989 | 765 to 1214 | 20 | no |
| #10 Declan Byrne | 500 | 1392 | 898 to 1886 | 20 | no |
| #11 Kavita Sharma | 500 | 1029 | 795 to 1263 | 20 | no |
| #12 Henrik Solberg | 500 | 1262 | 904 to 1621 | 20 | no |
| #13 Bernard Moreau | 600 | 1302 | 1103 to 1502 | 20 | no |
| #14 Zuri Ndiaye | 600 | 1407 | 1170 to 1644 | 20 | no |
| #15 Ronan Gallagher | 600 | 1221 | 1040 to 1403 | 20 | no |
| #16 Nadia Koury | 600 | 1333 | 1124 to 1542 | 20 | no |
| #17 Mireille Laurent | 700 | 1453 | 1193 to 1713 | 20 | no |
| #18 Devon Brooks | 700 | 1714 | 1224 to 2205 | 20 | no |
| #19 Kenji Takahashi | 700 | 1510 | 1216 to 1804 | 20 | no |
| #20 Sofia Novak | 700 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #21 Dennis Hoffman | 800 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #22 Valeria Ortiz | 800 | 1714 | 1224 to 2205 | 20 | no |
| #23 Callum Fraser | 800 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #24 Emeka Okafor | 800 | 1247 | 1060 to 1433 | 20 | no |
| #25 Ingrid Halvorsen | 950 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #26 Javier Mendoza | 950 | 1587 | 1234 to 1941 | 20 | no |
| #27 Claire Pelletier | 950 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #28 Arjun Nair | 950 | 1714 | 1224 to 2205 | 20 | no |
| #29 Alistair MacLeod | 1100 | 1587 | 1234 to 1941 | 20 | no |
| #30 Talia Ben-Ami | 1100 | 7302 | -5109338106 to 5109352711 | 20 | yes |
| #31 Lucas Kowalski | 1100 | 7362 | -7225698210 to 7225712934 | 20 | yes |
| #32 Yasmine Haddad | 1100 | 7356 | -5109338053 to 5109352765 | 20 | yes |
| #33 Martin Lindholm | 1250 | 7602 | -5109337806 to 5109353011 | 20 | yes |
| #34 Andrej Horvat | 1250 | 1767 | 1506 to 2028 | 20 | no |
| #35 Evelyn Mercer | 1250 | 1902 | 1548 to 2256 | 20 | no |
| #36 Santiago Reyes | 1250 | 7602 | -5109337806 to 5109353011 | 20 | yes |
| #37 Dorothy Carmichael | 1400 | 1919 | 1686 to 2151 | 20 | no |
| #38 Lorenzo Conti | 1400 | 1919 | 1686 to 2151 | 20 | no |
| #39 Joon-Ho Park | 1400 | 1848 | 1645 to 2051 | 20 | no |
| #40 Freja Holm | 1400 | 1767 | 1588 to 1946 | 20 | no |
| #41 Graham Holloway | 1550 | 1767 | 1588 to 1946 | 20 | no |
| #42 Esme Navarro | 1550 | 1963 | 1707 to 2219 | 20 | no |
| #43 Idris Boateng | 1550 | 2019 | 1728 to 2309 | 20 | no |
| #44 Tomasz Zielinski | 1550 | 2019 | 1728 to 2309 | 20 | no |
| #45 Patricia O'Sullivan | 1700 | 2110 | 1890 to 2329 | 20 | no |
| #46 Rafael Delgado | 1700 | 2194 | 1935 to 2453 | 20 | no |
| #47 Samuel Mensah-Bonsu | 1700 | 2110 | 1890 to 2329 | 20 | no |
| #48 Viktoras Kazlauskas | 1700 | 2251 | 1957 to 2544 | 20 | no |
| #49 Raymond Beauchamp | 1850 | 2251 | 1957 to 2544 | 20 | no |
| #50 Haruka Sato | 1850 | 2076 | 1868 to 2283 | 20 | no |
| #51 Ananya Deshmukh | 1850 | 1991 | 1806 to 2175 | 20 | yes |
| #52 Nikolai Volkov | 1850 | 2148 | 1912 to 2384 | 20 | no |
| #53 Willem Van Der Berg | 2000 | 2259 | 2064 to 2454 | 20 | no |
| #54 Astrid Nystrom | 2000 | 2232 | 2045 to 2419 | 20 | no |
| #55 Carlos Castellanos | 2000 | 2322 | 2105 to 2539 | 20 | no |
| #56 Darius Varga | 2000 | 2322 | 2105 to 2539 | 20 | no |
| #57 Reginald Pemberton | 2000 | 2232 | 2045 to 2419 | 20 | no |
| #58 Tamara Kovalenko | 2000 | 2461 | 2169 to 2753 | 20 | no |
| #59 Miriam Al-Jamil | 2000 | 2405 | 2148 to 2662 | 20 | no |
| #60 Ferenc Szabo | 2000 | 2289 | 2084 to 2493 | 20 | no |

Game endings: mate 4351, material 61, length limit 221, stalemate 32, repetition 726, 50 moves 50.
