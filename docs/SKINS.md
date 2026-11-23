# phpChess 6 — Skins

A skin is the look of a phpChess site: its colours, fonts, logo and layout. It is **only settings and images, never code** (A5, UI-01). It is designed so a simple editor in the admin area (backlog B3) can make and change skins by editing one file (A25).

## Where skins live

```
skins/
  default/
    skin.json      every setting, with plain names
    logo.svg       the logo (svg, png or webp)
```

The admin picks the site's skin under **Admin → Settings**. The `skins/` folder is not web-visible; phpChess serves the skin through two addresses only: `/skin/theme.css` (the CSS variables made from `skin.json`) and `/skin/logo`.

## The settings in skin.json

| Setting | What it is | Allowed values |
|---|---|---|
| `name`, `description` | Shown to the admin | text |
| `logo` | The logo file in the skin's folder | a file name ending in `.svg`, `.png` or `.webp`, or empty |
| `layout` | How the page is arranged | `top-bar` (more layouts later) |
| `fonts.text`, `fonts.headings` | Font lists | letters, digits, spaces, commas, quotes, `-` |
| `board`, `pieces` | The default board colours and piece set for new players | see `Preferences::BOARD_THEMES` and `PIECE_SETS` |
| `colours.light.*`, `colours.dark.*` | One colour per role, for the light and the dark look | `#rrggbb` |

The colour roles are the same in light and dark:

| Role | Used for |
|---|---|
| `text`, `muted_text` | Normal and quieter text |
| `page_background`, `surface` | Behind the page, and behind its content |
| `border` | Lines and frames |
| `accent`, `accent_text` | Links and buttons, and the text on a button |
| `error`, `error_background` | Error notices |
| `warning`, `warning_background` | Warnings (the admin banner) |
| `success`, `success_background` | Success notices |
| `focus` | The keyboard focus ring |

## How it works

1. `Skin::load()` (src/Domain/Look/Skin.php) reads `skin.json` and checks every value. A broken skin is refused with a message that names the wrong setting, and the site falls back to the default skin.
2. `Skin::themeCss()` turns the colours and fonts into CSS variables such as `--colour-accent`. Light is the default; dark is used when the device prefers it, and when the player chooses it (Preferences → Light or dark).
3. Every style in `public/assets/css/` uses only these variables. That is why a skin never needs CSS of its own.

## Rules a skin must keep

- **Contrast (UI-04)**: text must stand out from its background by at least 4.5 : 1 (WCAG AA). The test `LanguageAndLookTest::testTheDefaultSkinHasEnoughContrast` checks the default skin; a skin editor will check the same pairs.
- **Local only (A16, K9)**: a skin can't load anything from another site: fonts must be installed fonts or files served by phpChess itself.
