# nutrimente — Visual Style Guide

A calm, botanical visual language for the **psychology & nutrition studio dashboard**.
Soft watercolour washes, hand-drawn foliage, an elegant serif paired with a humanist sans.

**Open `Nutrimente Style Guide.dc.html`** for the living reference (palette swatches,
type scale, the full botanical set and every UI component rendered).

This package:

```
Nutrimente Style Guide.dc.html   ← visual reference (open in a browser)
assets/tokens.css                ← drop-in CSS custom properties
assets/botanicals.svg            ← reusable line-art sprite (<use href=…>)
STYLE-GUIDE.md                   ← this file
```

---

## 1. Principles

- **Quiet, not loud.** Low saturation everywhere; nothing competes for attention.
- **Warm ground.** The page is ivory (`#F7F4EF`), never pure white. Cards are white and float on it.
- **Soft geometry.** Big radii, hairline ivory borders, whisper-soft shadows. No hard lines.
- **Botanical breathing room.** Foliage and washes live in corners, behind greetings, in
  empty states — decorative, small, and sparse. They frame; they never crowd.

## 2. Colour

| Role | Token | Hex |
|---|---|---|
| Page ground | `--bg` | `#F7F4EF` |
| Card | `--card` | `#FFFFFF` |
| Input / inset | `--card-warm` | `#FBF9F5` |
| Ink (headings) | `--ink` | `#3E3A37` |
| Body text | `--ink-soft` | `#6B6661` |
| Meta / labels | `--ink-muted` | `#9A938C` |
| Borders | `--line` | `#ECE7E0` |
| **Sage (primary action)** | `--sage` | `#7C8A66` |
| Sage hover | `--sage-deep` | `#6B7A57` |
| Sage tint | `--sage-tint` | `#E8ECE0` |
| **Lavender (accent)** | `--lav` | `#9B85C4` |
| Lavender soft | `--lav-soft` | `#B9A8D0` |
| Lavender tint | `--lav-tint` | `#ECE6F4` |
| Blush | `--blush` | `#E6B89C` |
| Blush tint | `--blush-tint` | `#F7E9DE` |
| Success | `--ok` / `--ok-bg` | `#8FA572` / `#E4EDD9` |
| Waiting | `--warn` / `--warn-bg` | `#D9A06B` / `#F6E7D6` |
| Info / online | `--info` / `--info-bg` | `#9B85C4` / `#ECE6F4` |

**Usage:** sage is the *only* primary-action colour (buttons, active nav, positive status).
Lavender is the accent (eyebrows, secondary pills, decorative foliage). Blush adds warmth in
icon wells and one or two status pills. Keep any single screen to ~2 accent hues.

## 3. Watercolour washes

Blurred radial blooms — pure CSS, no image assets. Place them in a `position:relative;
overflow:hidden` container, low opacity, always blurred. Layer two hues behind the header;
drop a single bloom into an otherwise empty card corner.

```css
.wash { position:absolute; border-radius:50%; filter:blur(13px); pointer-events:none; }
.wash--lavender { background:radial-gradient(circle at 45% 40%, rgba(201,182,222,.65), rgba(201,182,222,0) 68%); }
.wash--blush    { background:radial-gradient(circle at 50% 50%, rgba(240,211,190,.55), rgba(240,211,190,0) 68%); }
.wash--sage     { background:radial-gradient(circle at 50% 50%, rgba(214,222,198,.85), rgba(214,222,198,0) 68%); }
```

## 4. Typography

Two families only.

- **Cormorant Garamond** — greetings, section & card titles, and big stat numerals.
  Weight 600. Elegant, high-contrast serif; the personality of the brand.
- **Mulish** — body, labels, buttons, table text, everything else. Weights 400/500/600.

| Style | Font | Size | Notes |
|---|---|---|---|
| Display / greeting | Cormorant 600 | 52px | “Buon pomeriggio, Sara” |
| Section / card title | Cormorant 600 | 28–30px | “Agenda di oggi” |
| Big numeral | Cormorant 600 | 40–44px | stat values |
| Body | Mulish 400 | 15–17px | |
| UI / link | Mulish 600 | 14px | links use sage or lavender |
| Overline / eyebrow | Mulish 600 | 12px | uppercase, `letter-spacing:.16em` |

```
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,500&family=Mulish:wght@300;400;500;600;700&display=swap');
```

## 5. Botanical motifs

Single-stroke SVG line-art in `assets/botanicals.svg`. Each motif strokes/fills with
`currentColor`, so it recolours to sage / lavender / blush by setting `color`.

| `#id` | Motif | Where to use |
|---|---|---|
| `bot-sprig` | Leaf sprig | beside greetings & titles |
| `bot-branch` | Leafy branch | header & corner decoration |
| `bot-lavender` | Lavender sprig | empty corners, warmth |
| `bot-sprout` | Sprout mark | logo / brand glyph |
| `bot-dots` | Dot spray | scattered accent |
| `bot-heart` | Heart flourish | quotes & footers |

```html
<svg style="width:92px;height:34px;color:#8FA572">
  <use href="assets/botanicals.svg#bot-sprig"></use>
</svg>
```

Keep them small and few — one or two per screen region. They are atmosphere, not content.

## 6. Components

- **Cards / panels** — `background:#fff; border:1px solid #ECE7E0; border-radius:22px;
  box-shadow:0 6px 28px rgba(74,68,60,.05); padding:26px`.
- **Primary button** — sage fill, white text, radius 12px, `box-shadow:0 4px 14px rgba(124,138,102,.28)`.
- **Secondary button** — white fill, sage text, `1px solid #D8DFC9`.
- **Tertiary / link** — lavender-tint pill or plain sage text with a trailing `→`.
- **Badges / pills** — full radius, tinted background + matching darker text
  (sage = confirmed, blush = waiting, lavender = info/neutral).
- **Sidebar nav** — icon + label rows, radius 13px; active row = sage-tint fill, `#5E6E48` text.
- **Stat tiles** — round tinted icon well + label, big serif numeral, sage delta line.
- **Tables** — uppercase muted column heads, hairline `#F6F2EC` row dividers, status pill in last cell.
- **Inputs** — `--card-warm` fill, `1px solid #E6E0D7`, radius 11px; label is Mulish 600 12px `--ink-soft`.

**Shape tokens:** cards `22px`, controls `11–12px`, pills `999px`.
**Icons:** thin line icons, `stroke-width:1.6`, `currentColor`.
