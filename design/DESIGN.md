# DriveDeal design system (blue theme)

The look is based on the reference screenshots in `design/references/`:
1. **01-main-drivenest** is the main reference: the overall layout, navbar, hero with a floating search card, car cards, "browse by" panels, CTA banner and navy footer.
2. **02-skykey** for section headings (small uppercase "eyebrow" label above a bold title), "How it works" numbered steps, and the icon feature row.
3. **03-logamax** for soft grey feature cards with thin line icons, the stats band with dividers, pill chips, and the vertical timeline with dots.
4. **04-uthao** for the rounded hero image frame, frosted-glass panels, and buttons with an arrow box.

Use the references for **layout, spacing and style only**. Never copy their names, text, photos, brand logos,
phone numbers, addresses or customer reviews.

---

## 1. Brand

| File | Use |
|---|---|
| `src/assets/brand/logo.svg` | Navbar and any light background (icon + "Deal" blue, "Drive" navy) |
| `src/assets/brand/logo-white.svg` | Footer, blue panels, auth-page side panel |
| `src/assets/brand/logo-mark.svg` | Icon only (small spaces, 404 page, loading states) |
| `public/favicon.svg` | Browser tab icon |
| `src/assets/brand/logo-original-red.svg` | Original file, kept for reference (not used) |

Logo height: 28px in the navbar (24px on mobile), 30px in the footer. Always `alt="DriveDeal"`.
To change the theme colour later, change the `--dd-*` variables below and the hex values in the four logo files.

## 2. Colours (CSS custom properties in `src/assets/main.css`)

```css
:root {
  --dd-primary: #1D5BF0;      /* buttons, links, prices, active nav, icons */
  --dd-primary-600: #1449D1;  /* hover / pressed */
  --dd-primary-700: #0F3AA8;
  --dd-primary-50: #EEF3FE;   /* tinted backgrounds, icon circles */
  --dd-primary-100: #DCE6FD;  /* borders on tinted areas, focus ring */
  --dd-navy: #0B1B3D;         /* footer, headings on light, dark cards */
  --dd-ink: #0F172A;          /* headings */
  --dd-body: #334155;         /* paragraph text */
  --dd-muted: #5B6B82;        /* secondary text, labels */
  --dd-line: #E3E8F0;         /* borders, dividers */
  --dd-surface: #F5F7FB;      /* page sections, feature cards */
  --dd-white: #FFFFFF;
  --dd-success: #16A34A;      /* "Available" */
  --dd-warning: #D97706;      /* "Reserved" */
  --dd-danger: #DC2626;       /* delete, errors, liked heart */
  --dd-gradient: linear-gradient(135deg, #1D5BF0 0%, #0F3AA8 100%);  /* blue panels, CTA banner */
  --dd-hero-bg: linear-gradient(180deg, #EEF3FE 0%, #FFFFFF 100%);
}
```
All text/background pairs above pass WCAG AA (primary on white 5.5:1, muted on white 5.4:1).
**Never hard-code a hex colour in a component. Always use `var(--dd-...)`.**

Map Bootstrap onto the brand so existing classes still work:
```css
:root {
  --bs-primary: #1D5BF0; --bs-primary-rgb: 29, 91, 240;
  --bs-link-color: #1D5BF0; --bs-link-hover-color: #1449D1; --bs-link-color-rgb: 29, 91, 240;
  --bs-body-font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  --bs-body-color: #334155; --bs-border-color: #E3E8F0; --bs-border-radius: 10px;
}
.btn-primary {
  --bs-btn-bg: #1D5BF0; --bs-btn-border-color: #1D5BF0;
  --bs-btn-hover-bg: #1449D1; --bs-btn-hover-border-color: #1449D1;
  --bs-btn-active-bg: #0F3AA8; --bs-btn-active-border-color: #0F3AA8;
  --bs-btn-disabled-bg: #1D5BF0; --bs-btn-disabled-border-color: #1D5BF0;
}
.btn-outline-primary {
  --bs-btn-color: #1D5BF0; --bs-btn-border-color: #1D5BF0;
  --bs-btn-hover-bg: #1D5BF0; --bs-btn-hover-border-color: #1D5BF0;
  --bs-btn-active-bg: #1449D1; --bs-btn-active-border-color: #1449D1;
}
```

## 3. Typography

- Font: **Plus Jakarta Sans** (Google Fonts, weights 400, 500, 600, 700, 800), loaded in `index.html` with
  `preconnect` + `display=swap`. Fallback: `system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif`.
- Headings use `--dd-ink`, weight 800 (h1) or 700 (h2–h4), `letter-spacing: -0.02em`, `line-height: 1.15`.
- Sizes: h1 `clamp(2.25rem, 1.4rem + 3.2vw, 3.6rem)`, h2 `clamp(1.6rem, 1.2rem + 1.4vw, 2.2rem)`,
  h3 `1.25rem`, body `1rem / 1.6`, small `0.875rem`.
- **Eyebrow** (SkyKey): `.dd-eyebrow`, 0.75rem, uppercase, letter-spacing 0.12em, weight 700, `--dd-primary`,
  placed just above section titles. Centred sections can add a short 40×3px blue bar under the title.
- Highlight one key phrase in hero headings with `<span class="text-primary">` (e.g. "fair deal").
- Prices: weight 800, `--dd-primary`, formatted `RM 48,000`.

## 4. Spacing, radius, shadows

- 8px grid. Section padding `clamp(3rem, 2rem + 4vw, 6rem)` top and bottom. Container max-width 1200px.
- Radius: buttons and inputs 10px, cards 16px, large panels and the hero image 24px, pills and chips 999px.
- Shadows:
  `--dd-shadow-sm: 0 1px 2px rgba(15,23,42,.06);`
  `--dd-shadow: 0 1px 2px rgba(15,23,42,.05), 0 8px 24px rgba(15,23,42,.07);`
  `--dd-shadow-lg: 0 12px 40px rgba(15,23,42,.12);` (floating search card, modals)
- Hover on clickable cards: `transform: translateY(-3px)` + `--dd-shadow-lg`, 200ms ease.
  Wrap all motion in `@media (prefers-reduced-motion: no-preference)`.

## 5. Components

**Buttons:** primary solid blue, secondary outline blue, ghost text button. Padding `0.75rem 1.25rem`, weight 600.
An optional "arrow box" variant (uthao): `.dd-btn-arrow` adds a small square with →, which shifts 3px on hover.
Focus: `outline: 3px solid var(--dd-primary-100); outline-offset: 2px`. Never remove focus outlines.

**Inputs:** 48px tall, 10px radius, `--dd-line` border, labels 0.875rem weight 600 `--dd-ink`; on focus, the
border turns `--dd-primary` with a 4px `--dd-primary-100` ring. Keep Bootstrap validation states, recoloured with
`--dd-danger` / `--dd-success`.

**Car card (DriveNest "Featured listings"):** white, 16px radius, `--dd-line` border, `--dd-shadow-sm`.
- Photo: 4:3 ratio with `object-fit: cover` and rounded top corners. The like button sits top-right in a 36px white circle.
  A "Reserved"/"Sold" badge sits top-left when the status isn't available.
- Body: title `2023 Perodua Myvi` (weight 700), sub-line `Hatchback · Red` (`--dd-muted`), price (blue, 800).
  Then a spec row with three small icons: mileage (`18,000 km`), transmission and fuel type. Last, a location line with a pin icon.
- The whole card is a link to the detail page; the like button must not trigger the link.

**Status badges:** pill, 0.75rem, weight 700. Available: green text on `#E8F7EE`; Reserved: amber text on `#FEF3E2`; Sold: `--dd-muted` on `--dd-surface`.

**Feature card (Logamax):** `--dd-surface` background, no border, 16px radius, 24px padding. A thin line icon (28px)
in `--dd-primary` sits on top, then a short title and one line of text.

**Stats band (Logamax bottom row):** white panel, 16px radius. 3–4 items, each an icon + big number (800) + label,
separated by vertical dividers on desktop and wrapping into a 2×2 grid on mobile. **Only real numbers from the API.**

**Steps (SkyKey "Renting a car is easy"):** 3–4 steps. Each has a 56px blue circle with a white icon, a "Step 1" eyebrow,
a title and one line of text. A dotted connector line runs between the circles on desktop; they stack vertically on mobile.

**Pill chips (Logamax "Additional services"):** outline pills used for "Browse by make" and active filters.

**Panels:** "Browse by make" is a white panel; "Browse by body type" is a `--dd-gradient` panel with white line-icon
tiles (DriveNest middle row). The CTA banner is a full-width `--dd-gradient` rounded panel, optionally with a soft
decorative circle shape.

**Glass panel (uthao, optional):** `background: rgba(255,255,255,.72); backdrop-filter: blur(14px);
border: 1px solid rgba(255,255,255,.6)`. Only use it on top of photos.

**Icons:** one small inline-SVG icon component (`src/components/AppIcon.vue`). Stroke icons, 24×24 viewBox,
`stroke="currentColor"`, `stroke-width="1.75"`, round caps/joins, `aria-hidden="true"` unless used alone
(then give it an `aria-label`). Needed: search, heart, heart-filled, car, gauge (mileage), gear (transmission),
fuel, pin, user, users, shield-check, message, tag, sparkles (Smart Match), arrow-right, chevron-down, plus,
upload, trash, edit, eye, check, x, menu, plus one simple side-view icon per body type (sedan, hatchback, suv, mpv, pickup).
Draw them yourself; no icon packages.

## 6. Layout of each page

**Navbar (all pages):** white, sticky top, 72px tall. A 1px `--dd-line` bottom border appears once you scroll down.
- Left: logo. Centre: Home, Browse cars, Sell a car, About.
- Right (guest): heart icon to /liked, "Sign in" text link, and a primary "Sell your car" button.
- Right (logged in): heart icon, then an avatar circle with the user's initials. Its dropdown holds My listings, Liked cars, Profile and Log out.
- The active link is blue, with a 4px dot below it. On mobile, the links collapse into the Bootstrap collapse menu with full-width links.

**Footer (all pages):** `--dd-navy` background, white/70% text, 64px top padding.
- Columns: logo-white with a one-line tagline, then Browse (All cars, Sell a car, Smart Match), Account (Sign in, Register, My listings, Liked cars), and DriveDeal (About, Contact).
- Bottom bar with a top divider: "© 2026 DriveDeal · COS30043 group project, Swinburne University of Technology".
- No social icons, phone numbers or addresses that don't exist.

**Home** (DriveNest structure):
1. **Hero** on `--dd-hero-bg`. Left column:
   - eyebrow "Malaysia's friendly car marketplace"
   - h1 "Find your next car, make a *fair deal*" (the italic part in blue)
   - one-line subtitle
   - two buttons: Browse cars (primary) and Sell your car (outline)
   - a row of 3 mini stats with icons. These are **live**: total cars for sale and number of makes (from `listings.php`), plus "Direct chat with sellers".

   Right column: `/images/hero-car.jpg` in a 24px-radius frame (uthao). Use the 960px version on small screens with `srcset`, `width`/`height` attributes and `fetchpriority="high"`.
2. **Floating search card** overlapping the bottom of the hero by about 48px (DriveNest/SkyKey).
   - Fields: Make or model (text), Body type (select), Min price and Max price (selects: Any, RM 30k, 50k, 80k, 120k, 200k), and a big primary Search button.
   - Searching goes to `/cars` with those filters as URL query parameters.
3. **Featured cars:** eyebrow + h2 "Fresh on DriveDeal" + "View all cars →" link. 4 newest cars in the new car cards (4 columns on desktop, 2 on tablet, 1 on phone).
4. **Browse by** row, two panels side by side: "Browse by make" (white panel, a pill per make from the API `makes` list → `/cars?make=X`) and "Browse by body type" (blue gradient panel, 5 icon tiles → `/cars?body_type=X`).
5. **How DriveDeal works:** SkyKey steps. Browse & filter → Like & compare (Smart Match) → Contact the seller → Agree the deal.
6. **Why DriveDeal:** 4 Logamax feature cards (Real sellers, real contact · Free to list your car · Smart Match recommendations · Save cars you like).
7. **Stats band** with live numbers: cars for sale, makes, average price, cars added this week (all computed from API data).
8. **CTA banner** (blue gradient): "Selling your car? List it in minutes." + "Sell your car" white button.
No financing section and no customer reviews (we don't have real ones).

**Browse cars** (`/cars`):
- A slim page header on `--dd-surface` with breadcrumb, h1 "Browse cars" and a "{n} cars found" count.
- Desktop: a filter sidebar (280px, white card, sticky) beside a results grid with 3 columns.
- Mobile: a "Filters" button opens the filters (Bootstrap offcanvas or collapse).
- The sort select sits top-right of the results. Active filters show as removable pill chips.
- Smart Match keeps its place, restyled as a highlighted card (blue tint background, sparkles icon).
- Pagination becomes rounded square buttons, with the active one blue.
- Empty state: a car icon, "No cars match your filters" and a "Clear filters" button.

**Car detail:**
- Breadcrumb, then a 2-column layout.
- Left: the gallery, a big 16px-radius image with clickable thumbnails below. Under it, the specs grid (year, mileage, transmission, fuel, body, colour), each with an icon in a `--dd-primary-50` circle, then the description.
- Right, sticky: a price card with title, price and like button, a seller card (initials avatar + name), and the "Contact seller" form.

**Sign in / Register:** a split layout. On the left, the form card (max 440px). On the right, a `--dd-gradient` panel with logo-white, a short headline and 3 benefit lines with check icons. On mobile, only the form card shows, centred.

**Profile, Liked cars:** a slim page header like Browse. Profile shows details in cards. Liked cars uses the car card grid, with an empty state (heart icon, "No liked cars yet", "Browse cars" button).

**Sell a car / Edit listing:**
- Page header with a 3-step progress line (Logamax "Storage — Picking — Delivery" style): Details → Photos → Publish. It's purely visual; the step lights up as sections become valid.
- The form is split into cards: "Car details", "Price & condition", "Location & description", "Photos".
- The photo area is a dashed `--dd-primary-100` drop zone with an upload icon. Previews show in a rounded grid, with a "Cover" badge on the first photo.
- Edit adds a "Danger zone" card with the Delete button.

**My listings:**
- A stats row of 4 tiles (Logamax style: total, available, reserved, sold).
- Car cards with View / Edit / Delete.
- "Enquiries from buyers" as a list of cards: car name link, buyer initials avatar, name, email link, phone, message and date. A subtle vertical timeline line on the left is optional (Logamax "End-to-End Process").

**About:** a small hero (eyebrow + h1 + text), mission feature cards, and the contact form in a card beside a short "What happens next" timeline (Logamax vertical dots).

**404:** centred logo-mark, a big "404", "This road doesn't go anywhere", and buttons for Home and Browse cars.

## 7. Responsive & accessibility checklist

- Breakpoints: Bootstrap defaults. Test at 375px, 768px, 1024px and 1440px. No horizontal scrolling anywhere.
- Touch targets at least 44px. Every image has meaningful `alt` text (decorative images: `alt=""`).
- Colour is never the only signal: status badges also show text, and errors also show a message.
- Keyboard: every interactive element is reachable with Tab and has a visible focus style; the avatar dropdown works with the keyboard.
- Images: `loading="lazy"` except the hero; car photos use `object-fit: cover` inside fixed-ratio boxes, so nothing jumps while loading.
