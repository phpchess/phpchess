# C3-1 load test and shared-host estimate

Date: 2026-11-23. Tool: `php tools/load-test.php --games=30 --seconds=60 --control=10+0`
(test database from the `PHPCHESS_TEST_DB_*` variables; tables `phpc_load_*`, removed afterwards).

## Setup
- Local XAMPP on Windows 11, PHP 8.3.37 (no OPcache in the CLI), MariaDB on 127.0.0.1.
- 30 timed games at once, one worker process per game, both players simulated through the real Kernel
  (routing, session, CSRF, controllers, database). Players poll at the interval the server asks for
  (1 s while the opponent moves, 3 s on their own move) and think 1 to 4 seconds per move, which is
  faster than real 5+3 play, so the load is on the high side.
- Each worker keeps one database connection. Opening a new one per request (as a web server does)
  used up the Windows local ports (about 16 000 sockets left in TIME_WAIT) and cost about 13 ms per
  request on this machine. On Linux a local connection costs about 1 ms.

## Results (30 games, 60 s)
| Measure | Value |
|---|---|
| Requests | 2 643, **44 per second** for the site (about 1.5 per game per second) |
| Database statements | 9.5 per request on average (a short poll: about 5) |
| Poll, nothing new (`unchanged`) | median **2.9 ms**, p95 4.1 ms |
| Poll with news | median 4.4 ms, p95 5.9 ms |
| Move | median 5.6 ms, p95 6.6 ms |
| Move delivery (answer to mover → opponent's page shows it) | median **502 ms**, p95 965 ms |
| Time lost to polling (clock loss minus real thinking time) | median **−296 ms**, worst −287 ms |

Time lost is below zero for every move: the delivery rule (the clock starts when the page receives the
move) removes the polling wait completely, and the 300 ms network allowance gives back a little more.
**No time is lost to polling.**

## Shared-host estimate
Cost per request on a shared host ≈ measured 3–6 ms + PHP start with OPcache ≈ 5 ms + database
connection ≈ 1 ms, so about **10–15 ms of one CPU core**.

| Live timed games | Requests per second | CPU busy (one core) |
|---|---|---|
| 10 | ~15 | ~0.2 |
| 30 (default limit) | ~45 | ~0.5–0.7 |
| 60 | ~90 | ~1–1.4 |

Shared hosts also cap simultaneous PHP processes ("entry processes", often 20–30). With 10–15 ms per
request and 45 requests per second, about one request runs at any moment, far below that cap.

**Conclusion**: the default of 30 live timed games fits a normal shared plan. On the cheapest plans
(a fraction of a core), the admin should lower it to 10–15, or raise the fast poll interval
(play.poll_fast_ms) to 2 000 ms, which halves the load and adds about 0.5 s to move delivery.
Correspondence games and watchers poll every 15 s and hardly count.

## F1 addition: the site-wide news poll (B21, 2026-11-25)

Same run (30 games of 10+0, 30 s, local XAMPP, PHP 8.3), each player also asking `GET /news` every 15 s:
`site_news` n=120, median 2.5 ms, p95 7.3 ms, max 10.2 ms. One online member costs about one 2.5 ms request every 15 s
(0.07 requests per second), far below a live game's polls. Other numbers unchanged (poll 3.0 ms, move 5.4 ms, delivery median 512 ms).
