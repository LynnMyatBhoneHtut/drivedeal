You are redesigning DriveDeal, our COS30043 group project: a car marketplace built with Vue 3 + Vite +
Bootstrap 5 + Vue Router, with a PHP/MySQL API. The whole site is finished and working. Your job is a
**visual redesign to a blue theme**, without breaking any feature. Work in phases and test after each one.

## 0. Setup (stop and tell me if anything fails)
1. `git checkout main`, `git pull origin main`, then `git checkout -b redesign-blue`
2. The design kit is already unzipped into the project root. Check these exist:
   design/DESIGN.md, design/references/ (4 screenshots), src/assets/brand/ (logo.svg, logo-white.svg,
   logo-mark.svg), public/favicon.svg, public/images/hero-car.jpg (now 1920px, about 170 KB) and
   public/images/hero-car-960.jpg.
3. Add `design/references/` to .gitignore. They're other companies' designs, used only as inspiration, so they
   must not go into our public repo. design/DESIGN.md and design/CLAUDE-CODE-PROMPT.md should be committed.
4. `npm install`, make sure api/db.php exists, then run `npm run api` (on Windows/XAMPP, if `php` isn't
   recognised: `C:\xampp\php\php.exe -S localhost:8000`) and `npm run dev`. Check the site loads cars before
   changing anything.
5. Run `npm run lint`, `npm run build` and `node --test tests/` once now, so you know the starting state is clean.

## 1. Read first
- design/DESIGN.md: the full design system and page-by-page layout. **This is the spec. Follow it closely.**
- Open and look at all 4 images in design/references/. 01-main-drivenest is the main reference; the others
  are for specific details, as DESIGN.md explains.
- Every file in src/views, src/components and src/assets/main.css, plus index.html and src/router/index.js.

## 2. The golden rule: change how it looks, not how it works
Do NOT change:
- API calls, data loading, computed logic, validation rules, auth/localStorage logic or route names/paths
- component props, emits, v-model names or element ids that forms depend on
- anything in api/, database/ or tests/, or the Smart Match algorithm in src/composables/
You MAY change templates (markup, classes, layout), styles, and text that's purely visual (headings, helper
text). The only logic changes allowed are:
- a) CarListView: on load, read route.query (make, minPrice, maxPrice, body_type, sort) into its existing
     `filters`, and add a body type filter (Any, Sedan, Hatchback, SUV, MPV, Pickup) to SearchBar and to the
     filtering. Keep the existing filter code working as it is.
- b) HomeView: the hero search card navigates to { name: 'cars', query: {...} } with only the filled-in fields.
     Featured cars and the live stats come from `apiGet('listings.php', ...)` (use `perPage: 100` once and
     compute the stats from that response; don't add API endpoints).
- c) Navbar: add a "scrolled" class for the border (passive scroll listener, removed on unmount) and the
     logged-in avatar dropdown. Keep using currentUser / logout from auth.js.
- d) New files: src/components/AppIcon.vue (inline SVG icons, as specified in DESIGN.md). You may also add small
     presentational components if they remove repetition (e.g. PageHeader.vue, StatusBadge.vue, EmptyState.vue).
- e) index.html: Google Fonts link for Plus Jakarta Sans (with preconnect), favicon → /favicon.svg,
     `<meta name="theme-color" content="#1D5BF0">`, and a meta description.
No new npm packages. No icon libraries. No CSS frameworks besides the Bootstrap we already have.

## 3. Content rules
- The brand is **DriveDeal**. Never use names, text, photos, brand logos, phone numbers, addresses or reviews
  from the reference images.
- No made-up numbers ("10,000+ cars"). Every stat must come from our API data.
- No customer reviews or testimonials, no financing section, and no links that go nowhere. Every button and link must
  go to a page we have.
- Car-brand names show as plain text pills, never brand logos.

## 4. Phases (commit after each; run lint + build + tests after each)
**Phase 1: Foundations.** Put all design tokens, the Bootstrap mapping, base typography, and utility classes
(.dd-eyebrow, .dd-section, .dd-card, .dd-feature-card, .dd-panel, .dd-btn-arrow, status badges, focus styles)
in src/assets/main.css. Then: index.html changes, AppIcon.vue, the new Navbar (logo.svg) and Footer (logo-white.svg).
Then remove every hard-coded colour and the old orange theme: `#c2410c`, `#9a3412`, `#ffb181`, `#fff0e6`,
`.btn-copper` and any other hex values in components. Replace them with `var(--dd-...)` or Bootstrap classes.
At the end of the phase, `grep -rnE "#[0-9a-fA-F]{3,6}\b" src --include=*.vue` should return only SVG
fill/stroke values inside AppIcon (or nothing). Chart colours in CarComparisonChart should use the blue palette;
read them from the CSS variables, or keep them as one small constant array of brand colours in that file.
→ **Stop after Phase 1 and tell me which pages to look at, so I can approve the new look before you continue.**
**Phase 2: Home**, following DESIGN.md section 6 "Home" exactly (hero with live stats, floating search card,
featured cars, browse by make / body type, how it works, why DriveDeal, stats band, CTA banner).
**Phase 3: Browse.** CarCard, SearchBar, Pagination, CarListView (sidebar filters, chips, sort, empty state),
SmartMatch + CarComparisonChart (restyle only), CarDetailView, ImageGallery, LikeButton.
**Phase 4: Accounts.** LoginView, LoginForm, RegisterView, RegisterForm (split layout), ProfileView, LikedCarsView.
**Phase 5: Selling.** SellCarView, EditListingView, CarForm (section cards, progress line, photo drop zone),
MyListingsView (stat tiles), InquiryList.
**Phase 6: About, 404 and polish.** AboutView, NotFoundView, then a consistency pass over every page:
spacing, heading sizes, buttons, empty and loading states (use Bootstrap spinners or simple skeleton boxes),
and error alerts.
After Phase 1 is approved, continue through Phases 2–6 without stopping unless something blocks you.

## 5. Test before you say it's done
Functionality (nothing may break). Use the fake login in the browser console, then refresh:
`localStorage.setItem('user', JSON.stringify({user_id:2, full_name:'Ahmad Faizal', email:'ahmad@example.com', role:'user'}))`
- Home: live stats show real numbers; hero search (e.g. make "Honda", max RM 90k) opens /cars already filtered;
  make pills and body type tiles open /cars filtered
- Browse: search, price filters, body type, sort, pagination and clear filters all work; Smart Match still works
  and `node --test tests/` passes
- Car detail: gallery, like/unlike (the count changes), and "Contact seller" sends an enquiry
- Register a new user, log out, log in, the route guard still redirects guests from /profile, and Profile saves
- Sell a car with 2 photos, edit it (price, status, add/remove a photo), and delete it; My listings shows enquiries
- About contact form sends; an unknown URL shows the 404 page
Look and quality:
- Check every page at 375px, 768px and 1440px wide: no horizontal scroll, nothing overlapping, the navbar menu works on mobile
- No console errors or warnings; every image has alt text; Tab works through the navbar, forms and buttons with
  a visible focus style
- The hero image loads from public/images (about 170 KB), not the old 2 MB file
- `npm run lint`, `npm run build` and `node --test tests/` are all clean

## 6. Git
Small commits with clear messages, one or more per phase (e.g. "Redesign: blue design tokens, navbar and footer").
Push to origin `redesign-blue` only. Never push to main. When done, tell me so I can open a Pull Request.

## Definition of done
Every page follows design/DESIGN.md; no old orange colours remain; every section 5 check passes; lint, build
and tests are clean; design/references/ is not committed; the work is pushed to redesign-blue. Finish with a
short summary: what changed per page, the screenshots/pages I should check, and anything my teammates need to know
(e.g. new shared classes they should use from now on).
