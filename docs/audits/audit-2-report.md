# LifeRuss: visual QA re-audit #2 (after the theme 1.10.0 / core 1.8.x release)

**Date:** 2026-09-29, about 23:00 Tehran time · Same crawler and checks as audit #1 (`crawl2.py` + `checks.js`) · **Viewports:** mobile 390×844 @2x and 360×780 @2x (iPhone UA, touch), desktop 1440×900 and 1280×800 · 33 URLs × 4 viewports. Also captured: the mobile menu (open, and scrolled to the bottom), the header search overlay (open and with text typed), and the desktop dropdown on hover. Read-only: I did not log in or submit anything.
Previous audit: `/workspace/liferuss-audit/report.md`.

## 0. Versions / deployment check

| Item | Result |
|---|---|
| Theme | ✅ **1.10.0**: `style.css` `Version: 1.10.0`; `theme.css`, `theme.js`, `finder.js` all `?ver=1.10.0` |
| Core plugin | ✅ **Updated** (probably 1.8.x). I can't read the exact number anymore because `/wp-content/plugins/liferuss-core/README.md`, `composer.json`, `uninstall.php` and `readme.txt` now all return **403** (the earlier README said 1.5.0). The new landing slugs and 301s below confirm the new core is active. |
| Old slugs | ✅ 301: `/study/` → `/study-russia/`, `/podfak/` → `/padfak/`, `/direct-admission/` → `/direct-course/`, `/immigration/` → `/migration-russia/` (in all 4 languages). `/exchange/` is now 200. |
| Catalog API | ❌ Still empty: `/wp-json/liferuss/v1/universities` and `/cities` return `{"items":[],"total":0}` |
| WordPress | 7.1.2 |

⚠️ **Deployment/cache note:** in my first pass (~22:55) about 7 pages per desktop viewport were served with **stale 1.9-era header markup** (no `.header-cta` / `.header-search-toggle`) alongside the new CSS. On desktop that pushed the gold CTA 120–200px off-screen (`body.scrollWidth` 1481–1612). A few minutes later every page returned the new markup (checked 4× with curl and 2× in a browser), so I re-ran the whole crawl. All screenshots here come from the clean second run; the first-run data is in `run1/`. If the owner still sees a broken header on some pages, purge any page/object cache (the WCDN responses are `NOCACHE`, so this cache is on the origin).

---

## 1. FIXED since audit #1 ✅

| Old ID | Issue | Evidence now |
|---|---|---|
| P0-1 | Mobile header overflowing (461px row, search cut off, page 473px wide) | **Fixed.** No horizontal overflow on any page at 390 or 360 (`scrollWidth` = viewport). Header is one 64px row: logo · search icon · "ورود" · hamburger. Search is a toggle overlay (`.header-search.is-open`): full width, input auto-focused, closes with Esc. → `mobile/home-viewport.png`, `mobile/home-search-typed.png` |
| P0-2 | Desktop nav overlapping / logo squeezed | **Fixed at 1440.** The menu is now 7 items with dropdowns (تحصیل ▾, خدمات ▾, زبان ▾, مهاجرت ▾, مجله, درباره ما, تماس) and the logo text is readable. → `desktop/home-dropdown.png` (1280 has a new bug, see N1) |
| P0-3 | 5 menu links returning 404 | **Fixed** (301s plus new pages). The only remaining 404 is the sample page, see R4. |
| P0-4 | Invisible `.service-card` titles on /services/ and /about/ | **Fixed** (`.service-card h3{color:#fff}`); contrast check passes. |
| P0-5 | White-on-white `.cost-card` on /costs/ | **Fixed.** Cost cards are now white cards with navy text on every page, and the homepage costs section uses the same style. |
| P1-1 | Mobile menu overlapping header / cut off by bottom nav / FAB on top | **Fixed.** `.site-nav` is `top:var(--header); bottom:0; overflow-y:auto; z-index:80`, and the FAB and bottom nav are hidden while `body.nav-open`. The whole list scrolls, with the language pills at the bottom. → `mobile/home-menu-open.png`, `mobile/home-menu-scrolled.png` |
| P1-2 | Mobile menu missing home/services/about/contact/language | **Fixed.** All present. |
| P1-3 | "ورود" invisible | **Fixed** (`.header-account{color:#fff}`, 44×44). |
| P1-4 | Tiny tap targets (lang 18px, toggle 24px) | **Mostly fixed.** Header buttons are 44×44 and the language pills are larger. Remaining small targets: footer tel/email links (29px tall), breadcrumb "خانه" (28px wide), TOC links in posts (25px). |
| P1-5 | FAB overlapping content on mobile | **Fixed.** No `.lr-float` on mobile (the bottom nav has "مشاوره"). The FAB only shows on desktop. |
| P1-8 | Empty testimonial avatars on mobile | **Fixed.** Photos load. |
| P1-9 | Unstyled forms | **Mostly fixed.** The account OTP form, blog search and search page are now styled (48px fields, gold button). Still raw: the scholarships filter, see R5. |
| §3.3 | English taglines on the Persian home page | **Fixed on the FA home page** ("تحصیل عالی · آینده‌ای روشن"; the footer tagline is now Persian). |
| §3.1/3.2 | Homepage card/background chaos | **Much improved.** "مسیرهای تحصیل" is now a 2-column icon-tile grid (`.path-tile`), costs moved from a navy band to white cards, and backgrounds now alternate grey/white with a navy hero only. |
| §4 | Footer duplicate links; LTR phone numbers | **Fixed.** 15 unique links, and phones read `+98 21 9100 2450` correctly. |
| §4 | Lesson breadcrumb only "خانه" | **Fixed** (خانه / آموزش زبان روسی / درس). |
| – | Plugin README/composer publicly readable | **Fixed** (403). |

## 2. NEW regressions / new issues 🆕

### N1 (P0) · Desktop 1280 (861–1299px): search bar stuck open under the header
- **Pages:** all · **Viewports:** 1100 to 1299px (checked at 1100, 1180, 1240 and 1280; fine at 1300 and above)
- **What's wrong:** `.header-search` renders as a full-width 69px white search strip under the header, **permanently open**, and it covers the top of the hero (the hero photo card is cut off). The search toggle button is hidden (`display:none`), so users can't close it.
- **Cause:** a cascade-order bug in `theme.css`. The block `@media (max-width:1280px) and (min-width:861px) { .header-search{display:none;position:absolute…} .header-search-toggle{display:inline-flex} }` sits at around char 6100, **before** the base rules `.header-search-toggle, .header-search-close { display:none … }` and the base `.header-search` display rule (around 6700–7300). The later base rules win.
- **Fix:** move that media block below the base header-search rules, or raise its specificity (`.site-header .header-search`).
- **Screenshot:** `desktop1280/home-viewport.png`

### N2 (P0) · New landing pages are published with placeholder/demo content
- **Pages:** `/study-russia/`, `/padfak/`, `/direct-course/`, `/migration-russia/`, `/exchange/` (these are the main menu destinations)
- **What's wrong:** the H1s read "تحصیل در روسیه (نمونه)", "پادفک (نمونه)", "پذیرش مستقیم (نمونه)", "مهاجرت (نمونه)", with the eyebrow "نمونه". The hero text is "متن نمونه. تا وقتی این پیش‌نویس منتشر نشود در سایت دیده نمی‌شود.", followed by sections "این بخش نمونه است / مدیر محتوا عنوان و متن هر بخش را عوض می‌کند…", an FAQ "این متن نمونه است؟", and a testimonial "مسیر پذیرش برای ما روشن بود. این نظر نمونه است. — نمونه نظر". The "دانشگاه‌ها، مدت و شهریه" block shows "موردی در کاتالوگ منتشر نشده است.". Several empty white gaps sit between the blocks.
- **Cause:** the core's seeded landing content, published as-is (the seed text itself says it should stay a draft). This is a content/admin fix: fill in the content or set the pages back to draft. It would also help if the theme hid sections whose content still equals the seed/default text.
- **Screenshots:** `mobile/direct-course.png`, `mobile/study-russia.png`, `mobile/padfak.png`, `desktop/migration-russia.png`

### N3 (P1) · Placement quiz: radio buttons stretched into huge circles
- **Page:** `/russian-language/a1/placement/` (and any `.lr-quiz`) · **Viewport:** mobile and desktop
- **What's wrong:** the radio inputs render at 283×48px (giant ring circles in the middle of each row) with the label pushed to the far edge. The "Привет یعنی…" legend is misaligned too.
- **Cause:** the new form rule `.lr-quiz input, .lr-quiz select, … { width:100%; min-height:48px; border…; padding… }` also hits `input[type=radio]`.
- **Fix:** scope it with `.lr-quiz input:not([type=radio]):not([type=checkbox])`, and add `.lr-quiz input[type=radio]{width:20px;height:20px;min-height:0;flex:0 0 20px}`.
- **Screenshot:** `mobile/rl-lesson-placement.png`

### N4 (P1) · /russia-guide/: white box with invisible text in the hero
- **What's wrong:** `.path-children > a.path-child` is a white pill (`background:#fff`) containing `strong` "شهرها" in white (contrast 1:1), so the hero shows an empty white bar.
- **Fix:** `.page-hero .path-child{background:rgba(255,255,255,.1);color:#fff}` or `.path-child strong{color:var(--navy)}`.
- **Screenshot:** `mobile/russia-guide.png`

### N5 (P2) · Homepage "دانشگاه‌های برتر روسیه" section removed
The universities slider no longer renders (probably hidden because the catalog is empty). That's an acceptable empty-state choice, but the homepage now has no university showcase at all. It will come back once the catalog is imported (R1).

### N6 (P2) · Desktop 1440: search field crowds "ورود"
There is only about 8px between the search box (`.header-search` 581–741) and "ورود" (529–573), so the link looks clipped against the search pill. Add `gap:12px` or shrink `.lr-finder` to about 10rem at 1300–1500px. → `desktop/home-viewport.png`

### N7 (P2) · Mobile: white strip between the footer and the bottom nav
`body{padding-bottom:64px}` on a white body, while the bottom nav is 57px with rounded top corners, so a white band shows under the dark footer. Put the padding on `.site-footer` or give `body.has-bottom-nav` a navy background. → `mobile/home-viewport-bottom.png`

## 3. REMAINING from audit #1 ⏳

| ID | Pri | Issue | Where / selector | Screenshot |
|---|---|---|---|---|
| R1 | P0 | **Catalog still empty.** The API returns 0 universities and 0 cities. `/universities/` shows "موردی در این فهرست نیست", and /cities/, /fields/, /compare/, /scholarships/ and /russia-guide/ show "…منتشر نشده است" empty states. These are now nicely styled empty-state cards with a CTA (an improvement), but the core sections of the site are still empty. Import the catalog data. | `mobile/universities.png`, `mobile/cities.png` |
| R2 | P1 | **"درخواست پذیرش" (menu + footer) → `/admission/` returns a 301 to the blog post `/blog/admission-visa-documents/`** instead of an admission form/landing page. It's in all 4 languages. | Core redirects table (`lr_redirects`) or the menu item | `mobile/admission.png` |
| R3 | P1 | **English strings on Persian pages:** the cargo hero badge "Iran — Russia · Cargo & Delivery" with the text "a stronger tomorrow", and the trade badge "Iran — Russia · Trade & Sourcing" with "Global connections". On `/ar/` and `/ru/` the hero badge is still the English "Higher Education · A Brighter Tomorrow" (not translated). | `inc/landing-i18n.php` / `i18n-defaults.php` | `mobile/cargo.png`, `mobile/ar-home.png` |
| R4 | P2 | `/برگه-نمونه/` (sample page) is still listed in `wp-sitemap-posts-page-1.xml` but returns 404. Delete it permanently or exclude it from the sitemap. | – | `mobile/sample-page.png` |
| R5 | P2 | **Scholarships filter unstyled:** raw native `<select>`s in a cramped 2×2 grid, and the button is labelled **"صافی"** (a machine translation of "Filter"; should be "اعمال فیلتر", like /universities/). The hero also says "…سهمیه‌های نمونه… مدیر محتوا به‌روز می‌کند" (placeholder copy). | `.lr-filters` on the scholarships template (the universities filter *is* styled) | `mobile/scholarships.png` |
| R6 | P2 | **Blog post author box:** "تیم محتوای لایف روس" with the default grey gravatar. The post nav shows a lone "نوشته قبلی"/"نوشته بعدی" and a floating "ثبت درخواست" button. | `single.php` | `mobile/post-padfak.png` |
| R7 | P2 | Gold `.eyebrow` on light grey fails contrast (#c99710 on #f5f7fb = 2.47:1). It's slightly darker now on some sections, but not everywhere. | `.eyebrow` | – |
| R8 | P2 | Small tap targets in the footer contact list (29px) and post TOC links (25px). | `.footer-contact a`, `.toc a` | – |

## 4. Mobile homepage: visual inspection (390 and 360)

Current order: hero (navy, photo) → services (grey, **navy cards 2-col**) → routes (white, **white tiles 2-col with navy icon discs**) → cargo/trade (grey, white cards 1-col) → majors (white, **white cards 2-col with large 80px navy discs**) → costs (grey, white cards 2-col) → roadmap (white, vertical timeline) → testimonials (grey, white cards + 1 navy stat card) → consult (cream) → footer.

Overall it's **much more consistent than before**: section spacing is uniform (52px top and bottom on mobile, 72px on desktop), the grids are 2-column and the page doesn't overflow. What's still inconsistent:
1. **Three icon treatments side by side.** Services use gold-outline rings on navy, routes use small filled navy discs (~44px), and majors use huge filled navy discs (~80px) that nearly fill the card. Pick one icon size/style (for example, the 44px disc for both routes and majors). Selectors: `.service-card .icon-circle`, `.path-tile .icon`, `.major-card .icon-circle`.
2. **Services are still the only dark card grid** in an otherwise light page. That's acceptable as an accent, but it reads as a different design system from the routes and majors tiles right below it.
3. **Costs grid has an orphan card:** 5 cards in 2 columns leaves "زبان تحصیل" alone in the right column with an empty left slot. Values wrap unevenly ("سالانه ۲۰۰۰ تا ۷۰۰۰ / دلار", "دیپلم، ریزنمرات و / گذرنامه"). Make the last card span 2 columns (`.cost-grid > :last-child:nth-child(odd){grid-column:1/-1}`) or use 1 column on mobile.
4. **Hero is very tall (~780px on a 844px screen).** The badge, H1, text, 2 CTAs and now the **student photo card on mobile** push the stats band below the fold. The photo (`.hero-media`) adds about 260px with little value on mobile. Hide it or shrink it at ≤640px. At 360px the hero stats text wraps into 3 lines ("از اولین قدم تا / استقرار").
5. **Roadmap:** the vertical gold line sits at the far right edge while the step circles and text are centred, so the line isn't connected to the circles. Align the line to the circles' centre or left-align the steps. `.roadmap` / `.step`
6. **Testimonials → consult:** the navy "۵۰۰+" stat card at the end of the white testimonial stack looks detached. Consider moving it into the hero stats or turning it into a full-width band.
7. **Footer is long on mobile (~1,400px):** "دسترسی سریع" has 15 links at about 40px spacing in a single column, and "خدمات" repeats پادفک. Use a 2-column link grid or an accordion.
8. Full-page PNGs show the sticky header in the middle of the hero. That's a Playwright full-page capture artifact; the viewport shots taken at scroll 0, 3000 and the bottom confirm the header stays pinned at top 0.

## 5. Automated check summary (clean run)
- **Horizontal overflow:** 0 elements on all 33 pages × 4 viewports (only the intentional `.hp` honeypot at −9999px).
- **Broken images:** 0 · **JS console errors:** 0 (apart from the 404 document on the two 404 test URLs).
- **Internal links:** 152 checked. Remaining non-200: the sample page (404 ×4 languages), and `/admission/` → 301 to a blog post. The old slugs are all 301 to the new pages (`broken_links.json`).
- **Fixed elements (mobile):** header 64px + bottom nav 57px (was 98 + 64 + a 58px FAB).
- **Mobile menu:** 780px panel, scrolls (content 1407px). The FAB and bottom nav are hidden while it's open. Submenus are always expanded (no accordion), so parent/child labels repeat: "خدمات/خدمات", "مهاجرت/مهاجرت", "تحصیل/تحصیل در روسیه". Consider collapsible `.sub-menu` sections with a toggle button (P2).
- **Search overlay (mobile):** opens under the header (0–390 × 64–133), input focused and 306px wide, close button present, Esc closes it. With the query "مسکو" no suggestion list appears (empty catalog), and there's no "no results" hint either (P2).

## 6. Suggested next fixes (order)
1. N1: CSS cascade fix for the 861–1299px search bar (a one-line move).
2. N2: replace or unpublish the "(نمونه)" landing content; R1: import the catalog.
3. N3: quiz radio styling; N4: russia-guide path-child colours.
4. R2: point "درخواست پذیرش" at a real admission page; R3: translate the remaining English badges.
5. Homepage polish (§4.1–4.5) and the P2 items.

## 7. Page matrix (clean run)

| viewport | page | HTTP | bodyScrollW/innerW | overflow els | broken img | JS errors | tiny targets | screenshot |
|---|---|---|---|---|---|---|---|---|
| mobile | home | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/home.png |
| mobile | study-russia | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/study-russia.png |
| mobile | admission | 200 | 390/390 | 0 | 0 | 0 | 6 | mobile/admission.png |
| mobile | universities | 200 | 390/390 | 0 | 0 | 0 | 5 | mobile/universities.png |
| mobile | fields | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/fields.png |
| mobile | cities | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/cities.png |
| mobile | costs | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/costs.png |
| mobile | scholarships | 200 | 390/390 | 0 | 0 | 0 | 8 | mobile/scholarships.png |
| mobile | padfak | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/padfak.png |
| mobile | direct-course | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/direct-course.png |
| mobile | russian-language | 200 | 390/390 | 0 | 0 | 0 | 9 | mobile/russian-language.png |
| mobile | rl-a1 | 200 | 390/390 | 0 | 0 | 0 | 6 | mobile/rl-a1.png |
| mobile | rl-lesson-alphabet | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/rl-lesson-alphabet.png |
| mobile | rl-lesson-placement | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/rl-lesson-placement.png |
| mobile | migration-russia | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/migration-russia.png |
| mobile | exchange | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/exchange.png |
| mobile | cargo | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/cargo.png |
| mobile | trade | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/trade.png |
| mobile | russia-guide | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/russia-guide.png |
| mobile | blog | 200 | 390/390 | 0 | 0 | 0 | 7 | mobile/blog.png |
| mobile | post-padfak | 200 | 390/390 | 0 | 0 | 0 | 6 | mobile/post-padfak.png |
| mobile | post-medicine | 200 | 390/390 | 0 | 0 | 0 | 6 | mobile/post-medicine.png |
| mobile | services | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/services.png |
| mobile | about | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/about.png |
| mobile | contact | 200 | 390/390 | 0 | 0 | 0 | 4 | mobile/contact.png |
| mobile | compare | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/compare.png |
| mobile | account | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/account.png |
| mobile | search | 200 | 390/390 | 0 | 0 | 0 | 6 | mobile/search.png |
| mobile | 404 | 404 | 390/390 | 0 | 0 | 0 | 3 | mobile/404.png |
| mobile | sample-page | 404 | 390/390 | 0 | 0 | 0 | 3 | mobile/sample-page.png |
| mobile | en-home | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/en-home.png |
| mobile | ru-home | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/ru-home.png |
| mobile | ar-home | 200 | 390/390 | 0 | 0 | 0 | 3 | mobile/ar-home.png |
| mobile360 | home | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/home.png |
| mobile360 | study-russia | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/study-russia.png |
| mobile360 | admission | 200 | 360/360 | 0 | 0 | 0 | 6 | mobile360/admission.png |
| mobile360 | universities | 200 | 360/360 | 0 | 0 | 0 | 5 | mobile360/universities.png |
| mobile360 | fields | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/fields.png |
| mobile360 | cities | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/cities.png |
| mobile360 | costs | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/costs.png |
| mobile360 | scholarships | 200 | 360/360 | 0 | 0 | 0 | 8 | mobile360/scholarships.png |
| mobile360 | padfak | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/padfak.png |
| mobile360 | direct-course | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/direct-course.png |
| mobile360 | russian-language | 200 | 360/360 | 0 | 0 | 0 | 9 | mobile360/russian-language.png |
| mobile360 | rl-a1 | 200 | 360/360 | 0 | 0 | 0 | 6 | mobile360/rl-a1.png |
| mobile360 | rl-lesson-alphabet | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/rl-lesson-alphabet.png |
| mobile360 | rl-lesson-placement | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/rl-lesson-placement.png |
| mobile360 | migration-russia | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/migration-russia.png |
| mobile360 | exchange | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/exchange.png |
| mobile360 | cargo | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/cargo.png |
| mobile360 | trade | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/trade.png |
| mobile360 | russia-guide | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/russia-guide.png |
| mobile360 | blog | 200 | 360/360 | 0 | 0 | 0 | 7 | mobile360/blog.png |
| mobile360 | post-padfak | 200 | 360/360 | 0 | 0 | 0 | 6 | mobile360/post-padfak.png |
| mobile360 | post-medicine | 200 | 360/360 | 0 | 0 | 0 | 6 | mobile360/post-medicine.png |
| mobile360 | services | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/services.png |
| mobile360 | about | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/about.png |
| mobile360 | contact | 200 | 360/360 | 0 | 0 | 0 | 4 | mobile360/contact.png |
| mobile360 | compare | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/compare.png |
| mobile360 | account | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/account.png |
| mobile360 | search | 200 | 360/360 | 0 | 0 | 0 | 6 | mobile360/search.png |
| mobile360 | 404 | 404 | 360/360 | 0 | 0 | 0 | 3 | mobile360/404.png |
| mobile360 | sample-page | 404 | 360/360 | 0 | 0 | 0 | 3 | mobile360/sample-page.png |
| mobile360 | en-home | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/en-home.png |
| mobile360 | ru-home | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/ru-home.png |
| mobile360 | ar-home | 200 | 360/360 | 0 | 0 | 0 | 3 | mobile360/ar-home.png |
| desktop | home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/home.png |
| desktop | study-russia | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/study-russia.png |
| desktop | admission | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/admission.png |
| desktop | universities | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/universities.png |
| desktop | fields | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/fields.png |
| desktop | cities | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/cities.png |
| desktop | costs | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/costs.png |
| desktop | scholarships | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/scholarships.png |
| desktop | padfak | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/padfak.png |
| desktop | direct-course | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/direct-course.png |
| desktop | russian-language | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/russian-language.png |
| desktop | rl-a1 | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-a1.png |
| desktop | rl-lesson-alphabet | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-lesson-alphabet.png |
| desktop | rl-lesson-placement | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/rl-lesson-placement.png |
| desktop | migration-russia | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/migration-russia.png |
| desktop | exchange | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/exchange.png |
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
| desktop | 404 | 404 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/404.png |
| desktop | sample-page | 404 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/sample-page.png |
| desktop | en-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/en-home.png |
| desktop | ru-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/ru-home.png |
| desktop | ar-home | 200 | 1440/1440 | 0 | 0 | 0 | 0 | desktop/ar-home.png |
| desktop1280 | home | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/home.png |
| desktop1280 | study-russia | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/study-russia.png |
| desktop1280 | admission | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/admission.png |
| desktop1280 | universities | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/universities.png |
| desktop1280 | fields | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/fields.png |
| desktop1280 | cities | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/cities.png |
| desktop1280 | costs | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/costs.png |
| desktop1280 | scholarships | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/scholarships.png |
| desktop1280 | padfak | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/padfak.png |
| desktop1280 | direct-course | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/direct-course.png |
| desktop1280 | russian-language | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/russian-language.png |
| desktop1280 | rl-a1 | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/rl-a1.png |
| desktop1280 | rl-lesson-alphabet | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/rl-lesson-alphabet.png |
| desktop1280 | rl-lesson-placement | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/rl-lesson-placement.png |
| desktop1280 | migration-russia | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/migration-russia.png |
| desktop1280 | exchange | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/exchange.png |
| desktop1280 | cargo | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/cargo.png |
| desktop1280 | trade | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/trade.png |
| desktop1280 | russia-guide | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/russia-guide.png |
| desktop1280 | blog | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/blog.png |
| desktop1280 | post-padfak | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/post-padfak.png |
| desktop1280 | post-medicine | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/post-medicine.png |
| desktop1280 | services | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/services.png |
| desktop1280 | about | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/about.png |
| desktop1280 | contact | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/contact.png |
| desktop1280 | compare | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/compare.png |
| desktop1280 | account | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/account.png |
| desktop1280 | search | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/search.png |
| desktop1280 | 404 | 404 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/404.png |
| desktop1280 | sample-page | 404 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/sample-page.png |
| desktop1280 | en-home | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/en-home.png |
| desktop1280 | ru-home | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/ru-home.png |
| desktop1280 | ar-home | 200 | 1280/1280 | 0 | 0 | 0 | 0 | desktop1280/ar-home.png |
### Files
- `mobile/`, `mobile360/`, `desktop/`, `desktop1280/`: `<page>.png` (full page) and `<page>-viewport.png` (first screen at load)
- Interaction shots: `*/home-menu-open.png`, `*/home-menu-scrolled.png`, `*/home-search-open.png`, `*/home-search-typed.png`, `desktop*/home-dropdown.png` (also for universities and services); `mobile/home-viewport-mid.png`, `home-viewport-backtop.png`, `home-viewport-bottom.png`
- `results-<viewport>.json` (raw metrics), `broken_links.json`, `theme.css` (live 1.10.0 CSS snapshot), `crawl2.py` + `checks.js`, `run1/` (first pass affected by stale markup)
