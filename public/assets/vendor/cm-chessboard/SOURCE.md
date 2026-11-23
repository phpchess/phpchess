# cm-chessboard (vendored, unchanged)

- **What**: the chessboard in the browser (G-04): SVG board and pieces, drag-and-drop and tap-tap moves, promotion dialog, markers. By Stefan Haack (shaack).
- **Source**: npm package `cm-chessboard` 8.15.8, https://registry.npmjs.org/cm-chessboard/-/cm-chessboard-8.15.8.tgz (SHA-256 of the archive: `e619700115432c7343a0115a76e548524d0a94f44afc16ad9af269cac2779213`). Kept: `src/` (ES modules, readable), `assets/` (CSS and SVG sprites). Left out: the `.scss` sources and READMEs.
- **Licence**: MIT, see `LICENSE` beside this file.
- **Changes**: none. phpChess's own board code lives in `public/assets/js/board/`.
- **Network calls**: only one: `src/view/ChessboardView.js` loads the piece sprite with `XMLHttpRequest` from `assetsUrl`, which phpChess sets to this folder's `assets/`. No `fetch`, `WebSocket`, `sendBeacon` or outside address (A16). The `http://www.w3.org/...` texts in `src/lib/Svg.js` are SVG namespace names, not requests.
