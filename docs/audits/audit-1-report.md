# LifeRuss (liferuss.com): visual QA audit

**Date:** 2026-09-29 (Tehran time) · **Tool:** Playwright/Chromium · **Viewports:** mobile 390×844 @2x (iPhone UA, isMobile, touch), desktop 1440×900 (+ header checks at 1280/1024/768)
**Scope:** 34 URLs × 2 viewports (home, landings, catalog, language/lessons, blog + posts, static pages, account, search, 404, EN/RU/AR homes). Read-only: I did not log in or submit any forms.

## 0. Live versions

| Component | Live | Latest built | Evidence |
|---|---|---|---|
| Theme `liferuss` | **1.9.0** ✅ | 1.9.0 | `style.css` `Version: 1.9.0`; `theme.css`, `theme.js`, `finder.js`, `catalog.js` all `?ver=1.9.0` |
| Core plugin `liferuss-core` | **Most likely 1.5.0** ❌ (older) | 1.8.0 | `/wp-content/plugins/liferuss-core/README.md` says "Version 1.5.0" (schema 1.1.0). The plugin loads no versioned front-end assets, and the PHP header can't be read over HTTP, so the README is the only public signal. Confirm under wp-admin → Plugins. |
| WordPress | 7.1.2 | – | `wp-emoji-release.min.js?ver=7.1.2` |

Consequences that match the old plugin and/or empty catalog tables:
- `/wp-json/liferuss/v1/universities`, `/fields` and `/cities` all return `{"items":[],"total":0}`. The universities archive, cities, fields, compare, scholarships and russia-guide pages are empty on the live site.
- The landing slugs `/study/`, `/podfak/`, `/direct-admission/`, `/immigration/` and `/exchange/` return **404**, yet the header menu, mobile menu, footer and homepage "routes" block all link to them. `/cargo/`, `/trade/` and `/russia-guide/` do work. The likely fix is to update core to 1.8.0 (or create the pages) and then flush permalinks (Settings → Permalinks → Save).
- Side note: the plugin's `README.md`, `composer.json` and `uninstall.php` are publicly reachable. Block `*.md` and `composer.json` in `wp-content/plugins` (minor information leak).

---

## 1. P0: breaks the site (fix first)

### P0-1 · Mobile header overflows the viewport (root cause of the broken-looking mobile site)
- **Pages:** every page · **Viewport:** mobile (anything ≤860px)
- **What's wrong:** the second header row (`.header-actions`) is **461px wide inside a 390px viewport** (left edge at −83px). The search field `.lr-finder` is cut off on the left, the "ورود" link is pushed to the right edge, and `body.scrollWidth` = 473. `html/body {overflow-x: clip}` hides the scrollbar, but the page still lays out at 473px, so in the screenshots the whole page renders zoomed out/shifted with an empty strip on the right. The header is also 98px tall on mobile (two rows), which uses about 12% of the screen and stays sticky.
- **Cause (theme.css):** `.header-actions { flex-wrap: nowrap; flex-shrink: 0; }` is never overridden in the mobile media query. The mobile rule `.lr-finder { flex: 1 1 100%; order: 5; min-width: 0 }` expects the row to wrap, but it can't.
- **Fix:** inside `@media (max-width:860px)`: `.header-inner{flex-wrap:wrap}` `.header-actions{flex-wrap:wrap; flex-shrink:1; min-width:0; width:100%}` `.lr-finder{flex:1 1 100%}`. Better still, move search into the mobile menu or behind a search icon so the header is a single ~60px row. Add `.nav-toggle{flex-shrink:0}` (it currently shrinks to **24px** wide).
- **Screenshots:** `mobile/home-viewport-0.png`, `mobile/home.png`, and any `mobile/*.png`

### P0-2 · Desktop header: nav items overlap each other, the search box and the logo
- **Pages:** every page · **Viewport:** desktop 1440, 1280, 1024
- **What's wrong:** there are 13 top-level menu items. `nav#site-nav` is 520px wide but its `<ul>` spans x=176…1233px, so items render on top of `.header-actions` (search, language switcher, login, CTA). "استعلام نرخ", "کارگو", "درخواست پذیرش" and FA/RU/AR/EN are unreadable. `.brand-text` is squeezed to **9px** wide, so "لایف روس" overlaps the first menu item.
- **Cause:** `.site-nav { margin-inline:auto; min-width:0 }` with a nowrap menu, and `.brand-text { min-width:0 }`. The hamburger only kicks in at ≤860px.
- **Fix:** trim the primary menu to 5–6 items and put the rest under a "خدمات ▾" / "بیشتر ▾" dropdown, *or* switch to the hamburger layout at ≤1280px. Add `.brand{flex-shrink:0}` `.brand-text{min-width:max-content}`. Consider moving the search into an icon button on desktop.
- **Screenshots:** `desktop/home-header-1440.png`, `desktop/home-header-1024.png`, `desktop/home-viewport.png`

### P0-3 · Main menu links go to 404 pages
- **Pages:** header menu, mobile menu, footer "دسترسی سریع", homepage "مسیرهای تحصیل و مهاجرت", bottom of every page (also under /en/, /ru/, /ar/)
- **404 URLs:** `/study/`, `/podfak/`, `/direct-admission/`, `/immigration/`, `/exchange/` (the "استعلام نرخ" item), plus `/برگه-نمونه/`, which is listed in `wp-sitemap-posts-page-1.xml` but returns 404. Full list: `broken_links.json`.
- **Fix:** see §0 (update the plugin, or create the landing pages, then flush rewrites). Remove the sample page from the sitemap or delete it permanently.
- **Screenshots:** `mobile/study.png`, `mobile/exchange.png`, `desktop/podfak.png`

### P0-4 · Invisible card titles on /services/ and /about/ (navy text on navy cards)
- **Pages:** `https://liferuss.com/services/`, `https://liferuss.com/about/` · **Viewport:** both
- **What's wrong:** `.service-card` has a navy background (`#0b2341`) but its `h3` computes to the same `#0b2341` (contrast 1:1), and the `p` is `#5b6b7c` on navy. The cards show only an icon and faint text; the titles ("پذیرش تحصیلی", "پادفک", "ویزا", "شفافیت هزینه", …) are invisible.
- **Cause:** white text is only set for `.services-section .service-card h3/p`. These templates (`templates/services.php`, `templates/about.php`) don't wrap the cards in `.services-section`.
- **Fix:** move the colours onto the component: `.service-card h3{color:#fff}` `.service-card p{color:rgba(213,222,234,.9)}` (and drop the section-scoped rules).
- **Screenshots:** `mobile/services-viewport-cards.png`, `mobile/about.png`, `desktop/services.png`

### P0-5 · /costs/ page: white text on a white background, and an empty table
- **Page:** `https://liferuss.com/costs/` · **Viewport:** both
- **What's wrong:** `.cost-card` is `background:transparent` and `h3{color:#fff}`, `.cost-note{color:rgba(213,222,234,.8)}`. It was designed for the dark `.costs-section` on the homepage, but on /costs/ it sits on white, so titles are invisible and only the gold values show. The "شهریه (دلار)" table shows "دانشگاه 0" with no rows (empty catalog).
- **Fix:** wrap the costs grid in `.costs-section` (dark), or give `.cost-card` a light variant (`.page-template-costs .cost-card{background:#fff;border:1px solid var(--line)} … h3{color:var(--navy)}`). Hide the tuition table when there are 0 rows.
- **Screenshots:** `mobile/costs.png`, `desktop/costs.png`

### P0-6 · Catalog pages are empty (universities / cities / fields / compare / scholarships / russia-guide)
- **Pages:** `/universities/` ("0 دانشگاه · موردی با این فیلتر پیدا نشد"), `/cities/` and `/fields/` (hero only, no content), `/compare/` (one line of text), `/scholarships/` and `/russia-guide/` ("موردی در کاتالوگ منتشر نشده است"; russia-guide also shows 3 empty white boxes). There are no university single pages (`/universities/msu/` → 404).
- **Contradiction:** the homepage "دانشگاه‌های برتر روسیه" slider shows 4–6 hard-coded universities (MSU, SPBU, HSE, Sechenov…) as `<article class="uni-card">` with **no links**, while the database behind them is empty. The homepage "شهرها" route also points to an empty page.
- **Fix:** import/seed the catalog (plugin 1.8.0). Until then, hide empty sections or menu items and show a friendly empty state with a CTA instead of blank pages. Make homepage uni cards link to single pages once they exist.
- **Screenshots:** `mobile/universities.png`, `mobile/cities.png`, `mobile/russia-guide.png`, `desktop/compare.png`

---

## 2. P1: mobile UX problems

| # | Page / viewport | Problem | Likely cause / fix | Screenshot |
|---|---|---|---|---|
| P1-1 | all · mobile | **Mobile menu overlaps the header and is cut off by the bottom nav.** The panel opens at `top:72px`, but the header is 98px tall, so the search row stays visible above the menu and the close ✕ sits over the search field. The panel is 743px tall (72→815) while `.lr-bottom-nav` starts at 780, so the last item "کارگو" is hidden under the bottom nav. The chat FAB (z-index 90) floats over the menu items. | `.site-nav{inset:72px 0 auto 0}`: use `top:var(--header-h)`, `max-height:calc(100dvh - var(--header-h) - 64px); overflow-y:auto`. Hide `.lr-float` and raise the menu's z-index above the bottom nav while `body.nav-open`. | `mobile/home-menu-open-viewport.png`, `mobile/home-menu-open.png` |
| P1-2 | all · mobile | **Mobile menu is missing key pages:** home, services, about, contact, Russian language (آموزش زبان روسی), fields, cities, compare. Meanwhile 5 of its 13 items are 404s. | Build the menu in wp-admin → Menus (or the fallback in `header.php`). Group items (تحصیل / خدمات / تجارت / زبان). | same |
| P1-3 | all · mobile | **"ورود" (login) link is invisible:** `rgb(27,42,58)` on the navy header. | `.header-account{color:#fff}` | `mobile/home-viewport-0.png` |
| P1-4 | all · mobile | **Tiny tap targets:** language switcher FA/RU/AR/EN is 28×18px, `.nav-toggle` 24px wide, "ورود" 28×29, footer links 25px tall, breadcrumb links 29px, university filter checkbox 20px. Bottom-nav labels are 9.9px. | `.lang-switch--header a{padding:10px 6px;min-height:40px}`, `.footer-links a{display:block;padding:8px 0}`, bottom-nav label ≥11px. Move the header language switcher into the menu on mobile. | `results.json` → `tiny` |
| P1-5 | all · mobile | **Floating chat button overlaps content:** `.lr-float` (58×58, bottom 76px, left 16) sits on top of card text, form fields, the footer copyright and menu items. There are two CTAs (FAB + gold "مشاوره" in the bottom nav) doing the same thing. | Remove the FAB on mobile when `has-bottom-nav` (the bottom nav already has "مشاوره"), or add `padding-bottom` so it doesn't cover content. | `mobile/home-viewport-bottom.png`, `mobile/services-viewport-cards.png` |
| P1-6 | home · mobile | **Hero stats block misaligned:** the 2×2 "۵۰۰+ دانشجوی موفق / پشتیبانی کامل…" grid has uneven text wrapping, and its first row starts right under the CTA with no breathing room. | `.hero-stats` on mobile: use 1 column, or equal-height cells with `align-items:start` | `mobile/home-viewport-0.png` |
| P1-7 | home · mobile | **"مسیرهای تحصیل و مهاجرت" is a wall of 16 plain white pills** (no icons, no descriptions). Very long on mobile (≈1,000px of scrolling) and visually unlike every other card section. | Turn it into a 2-column icon grid on mobile, or cap it at 6 items with a "همه مسیرها" link. Remove the duplicates of the header menu. | `mobile/home.png` (lower part of segment 2) |
| P1-8 | home · mobile | **Testimonial avatars render as empty grey circles on mobile** (they load on desktop). | Lazy-load/`srcset` issue in the `.story-card` avatar (check `loading="lazy"` + `display` inside the slider/hidden container on mobile) | `mobile/home.png` vs `desktop/home.png` |
| P1-9 | account, blog, search, lesson pages · mobile | **Unstyled native form controls:** the account OTP form (select "روش", inputs), the blog sidebar search (`input` + grey "جستجو" button), the search-page form and the lesson quiz radios/select look like raw browser defaults, unlike the styled consult form. | Apply the `.consult-form` field styles to `.lr-otp-form`, `.search-form`, `.lr-quiz` (inputs 48px tall, rounded, full-width button). | `mobile/account.png`, `mobile/blog.png`, `mobile/search.png`, `mobile/rl-lesson-placement.png` |
| P1-10 | desktop hero | Hero photo card overlaps the "Knowledge Bridge" caption box, and a gold Latin badge sits above the Persian H1. | – | `desktop/home-viewport.png` |

## 3. P1/P2: homepage visual inconsistency ("ناهماهنگ")

Section order on the homepage: hero (navy) → services (grey bg, **navy cards**) → routes (white bg, **plain white pills**) → cargo/trade (grey bg, **white cards with gold buttons**) → universities (**navy** bg, photo cards) → majors (white, **navy circles**) → costs (**navy**, icon-only columns) → roadmap (white, numbered timeline) → testimonials (grey, white cards + one **navy** stat card) → consult (cream).

1. **Six different card languages in one page:** navy filled cards, white bordered cards, pill links, round icon bubbles, borderless icon columns and photo cards. Pick 2 at most (e.g. white card + navy feature card) and reuse them. *Selectors:* `.service-card`, `.path-home a`, `.home-landings-section .landing-card`, `.uni-card`, `.major-card`, `.cost-card`.
2. **Irregular background rhythm:** grey → white → grey → navy → white → navy → white → grey → cream. Three navy blocks in the page's second half make it look heavy and patchy. Aim for a regular alternation (white / light grey) with navy used once (hero) plus one accent band.
3. **Mixed languages on a Persian page:** "Higher Education · A Brighter Tomorrow" (hero badge), "Knowledge · Opportunity" (universities eyebrow), "Knowledge Bridge" (hero card), and the footer tagline "Knowledge Bridge · Higher Education · A Brighter Tomorrow". Other sections use Persian eyebrows ("از پذیرش تا استقرار"). Translate these or remove them (`inc/i18n-defaults.php` / theme options).
4. **Services duplicated three times** on the home page: "خدمات ما" cards, the "مسیرهای تحصیل" pills and the footer "خدمات" list all repeat پادفک / پذیرش / ویزا. "Cargo/trade" also appears both as pills and as cards.
5. **Card heights:** on desktop the services cards (6 per row) have text of different lengths, so the content isn't vertically aligned (headings sit at different y). Use `display:flex;flex-direction:column` with a fixed line clamp. Cargo/trade cards: the icon is right-aligned while the button position floats.
6. **Heading scale inconsistent across pages:** home h2 = 35.2px desktop / 25.6px mobile, costs page h2 = 24px, footer h2 16.8/18.4px. Define `--h2` once.
7. **Eyebrow contrast:** gold eyebrows (`p.eyebrow` `#c99710`) on light grey `#f5f7fb` have a contrast of 2.47:1 (fails WCAG).
8. **Mobile universities slider** shows a single card with prev/next arrows placed above the heading area ("مشاهده همه دانشگاه‌ها" link + arrows) and a large empty navy area below it.
9. **Costs section on mobile** is five huge centred icon blocks (≈1,300px of scroll). Use a 2-column compact grid.
10. **Roadmap on mobile** is a centred vertical list with big circles and no connecting line. It's fine functionally, but the style differs from the desktop horizontal timeline.

## 4. P2: footer, content and polish

- **Footer "دسترسی سریع" has duplicate links:** "هزینه‌ها" ×2 and "تماس با ما" ×3; 19 links in one column. The label "مجله" is used for /blog/ while the header says "وبلاگ". Clean up the footer menu (wp-admin → Menus) or de-duplicate in `footer.php` (likely a hard-coded list plus a menu merge). Split it into 2–3 columns on desktop, or use an accordion on mobile. (`mobile/home.png` bottom, `desktop/home.png` bottom)
- **Footer phone numbers** are rendered LTR inside RTL ("2450 9100 21 98+", "+" on the wrong side). Wrap them in `<span dir="ltr">` or `<bdi>`.
- **Inner-page hero eyebrow is always "لایف روس"** (about, universities, cities, fields, account, lessons). Make it contextual or remove it.
- **Blog posts:** the author box shows "amin" with the default grey gravatar. The post navigation has only "نوشته قبلی" floating alone. The "ثبت درخواست" button floats without context. (`mobile/post-padfak.png`)
- **Lesson pages:** "این درس در فهرست دوره نیست." shows on `/russian-language/a1/placement/`, the breadcrumb shows only "خانه", and the quiz uses raw inputs. The lesson isn't attached to its course/level. (`mobile/rl-lesson-placement.png`)
- **/russian-language/** is reachable only from the sitemap; it isn't in the header or mobile menu.
- **The 404 page** is fine, but it's what 5 menu items land on.
- **Cargo/trade landings** have a `p.screen-reader-text` ("اعتماد دانشجویان") that computes as visible dark text on navy (contrast 1.08). Check that `.screen-reader-text` is really clipped inside `.landing-trust`.

## 5. Programmatic check summary

- **Horizontal overflow:** all 34 mobile pages have `.header-actions` / `.lr-finder` / `#lr-finder-input` overflowing (left −83px). The honeypot `.hp` at −9999px is intentional. Desktop has no overflow except the header overlap (P0-2).
- **Broken images:** none detected.
- **Console errors:** none apart from the document 404s on the 404 pages. No JS errors.
- **404 internal links:** see `broken_links.json` (5 landing slugs × 4 languages, plus the sample page).
- **Fixed elements (mobile):** sticky header 98px, bottom nav 64px, FAB 58px. About 26% of the viewport is permanently covered.
- **Per-page raw data:** `results.json` (overflow list, fixed elements, tiny targets, text overflow, section metrics, Latin text found).

## 6. Suggested fix order
1. Update `liferuss-core` to 1.8.0, flush permalinks, import the catalog → fixes the 404 landings and empty catalog pages (P0-3, P0-6).
2. Header CSS: mobile wrap/one-row layout plus desktop menu reduction or dropdown (P0-1, P0-2, P1-1, P1-3, P1-4).
3. Component colour scoping: `.service-card` and `.cost-card` (P0-4, P0-5).
4. Remove the FAB on mobile and clean up the footer and mobile menus (P1-2, P1-5, P4).
5. Homepage design pass: unify card styles and backgrounds, translate the English taglines (§3).

## 7. Page matrix

| viewport | page | HTTP | scrollW/innerW | broken img | console | failed req | tiny targets | screenshot |
|---|---|---|---|---|---|---|---|---|
| mobile | home | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/home.png |
| mobile | study | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/study.png |
| mobile | admission | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/admission.png |
| mobile | universities | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/universities.png |
| mobile | university-single | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/university-single.png |
| mobile | fields | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/fields.png |
| mobile | cities | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/cities.png |
| mobile | costs | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/costs.png |
| mobile | scholarships | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/scholarships.png |
| mobile | podfak | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/podfak.png |
| mobile | direct-admission | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/direct-admission.png |
| mobile | russian-language | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/russian-language.png |
| mobile | rl-a1 | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/rl-a1.png |
| mobile | rl-lesson-alphabet | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/rl-lesson-alphabet.png |
| mobile | rl-lesson-placement | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/rl-lesson-placement.png |
| mobile | immigration | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/immigration.png |
| mobile | exchange | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/exchange.png |
| mobile | cargo | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/cargo.png |
| mobile | trade | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/trade.png |
| mobile | russia-guide | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/russia-guide.png |
| mobile | blog | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/blog.png |
| mobile | post-padfak | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/post-padfak.png |
| mobile | post-medicine | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/post-medicine.png |
| mobile | services | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/services.png |
| mobile | about | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/about.png |
| mobile | contact | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/contact.png |
| mobile | compare | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/compare.png |
| mobile | account | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/account.png |
| mobile | search | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/search.png |
| mobile | 404 | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/404.png |
| mobile | sample-page | 404 | 390/390 | 0 | 1 | 1 | 30 | mobile/sample-page.png |
| mobile | en-home | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/en-home.png |
| mobile | ru-home | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/ru-home.png |
| mobile | ar-home | 200 | 390/390 | 0 | 0 | 0 | 30 | mobile/ar-home.png |
| desktop | home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/home.png |
| desktop | study | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/study.png |
| desktop | admission | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/admission.png |
| desktop | universities | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/universities.png |
| desktop | university-single | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/university-single.png |
| desktop | fields | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/fields.png |
| desktop | cities | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/cities.png |
| desktop | costs | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/costs.png |
| desktop | scholarships | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/scholarships.png |
| desktop | podfak | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/podfak.png |
| desktop | direct-admission | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/direct-admission.png |
| desktop | russian-language | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/russian-language.png |
| desktop | rl-a1 | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-a1.png |
| desktop | rl-lesson-alphabet | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-lesson-alphabet.png |
| desktop | rl-lesson-placement | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-lesson-placement.png |
| desktop | immigration | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/immigration.png |
| desktop | exchange | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/exchange.png |
| desktop | cargo | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/cargo.png |
| desktop | trade | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/trade.png |
| desktop | russia-guide | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/russia-guide.png |
| desktop | blog | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/blog.png |
| desktop | post-padfak | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/post-padfak.png |
| desktop | post-medicine | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/post-medicine.png |
| desktop | services | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/services.png |
| desktop | about | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/about.png |
| desktop | contact | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/contact.png |
| desktop | compare | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/compare.png |
| desktop | account | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/account.png |
| desktop | search | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/search.png |
| desktop | 404 | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/404.png |
| desktop | sample-page | 404 | 1440/1440 | 0 | 1 | 1 | 0 | desktop/sample-page.png |
| desktop | en-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/en-home.png |
| desktop | ru-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/ru-home.png |
| desktop | ar-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/ar-home.png |
_Note: in the mobile full-page PNGs the sticky header and bottom nav appear in odd places (a Playwright full-page capture artifact), and the pages render 473px wide because of P0-1. Use the `*-viewport*.png` shots to see how fixed elements really look._

### Files
- `mobile/*.png`, `desktop/*.png`: full-page screenshots; `*-viewport-*.png` show real viewport states; `home-menu-open*.png` shows the open mobile menu; `desktop/home-header-{1440,1280,1024,768}.png`
- `results.json`: raw per-page metrics · `broken_links.json` · `crawl.py` + `checks.js`: rerunnable audit script · `theme.css`: the live CSS snapshot used for selector analysis
