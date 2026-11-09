# DriveDeal – COS30043 group project

A car marketplace where people browse, like and enquire about cars, and list their own cars for sale.

Vue 3 + Vite + Bootstrap 5 + Vue Router, with a PHP/MySQL API.
This is the **Step 0 skeleton**: all 12 routes, placeholder pages and components, shared services and
PHP stubs already exist, so each person only fills in their own files and nobody edits the same file.

## First-time setup (everyone, once)

```bash
git clone https://github.com/LynnMyatBhoneHtut/drivedeal.git
cd drivedeal
npm install
git checkout -b lai-browse        # or yati-accounts / lynn-selling
```

1. Copy `api/db.example.php` to `api/db.php` and put in your own MySQL password (`db.php` is git-ignored).
2. Import `database/car_marketplace_group.sql`, then `database/car_marketplace_sample_data.sql`, into your local MySQL.
3. VS Code will suggest the Vue, ESLint and Prettier extensions. Install them so formatting happens on save.

## Running it

Open two terminals in the project folder:

```bash
npm run api     # PHP server for api/ and uploads/ on localhost:8000
npm run dev     # Vue app - open the URL it prints
```

Vite passes every `/api/...` and `/uploads/...` request to the PHP server, so the Vue code doesn't change between local and Mercury.

Need to be logged in before Yati's login page is merged? Paste this into the browser console, then refresh:

```js
localStorage.setItem('user', JSON.stringify({user_id:2, full_name:'Ahmad Faizal', email:'ahmad@example.com', role:'user'}))
```

## Who owns which files

Only edit files you own. If you need a change in someone else's file, ask in the group chat.

| | Lai – Browse | Yati – Accounts | Lynn – Selling |
|---|---|---|---|
| Pages (`src/views/`) | `HomeView`, `CarListView`, `CarDetailView`, `AboutView`, `NotFoundView` | `RegisterView`, `LoginView`, `ProfileView`, `LikedCarsView` | `SellCarView`, `EditListingView`, `MyListingsView` |
| Components (`src/components/`) | `Navbar`, `Footer`, `CarCard`, `SearchBar`, `Pagination`, `ImageGallery` | `LoginForm`, `RegisterForm`, `LikeButton` | `CarForm`, `InquiryList` |
| Other | `src/assets/main.css` | `src/router/guards.js` | |
| PHP (`api/`) | `listings.php` **GET case**, `contact.php` | `register.php`, `login.php`, `likes.php` POST/DELETE, `users.php` PUT | `listings.php` **POST/PUT/DELETE cases**, `upload.php`, `inquiries.php` POST |

`listings.php` is split by method: every endpoint file has one `case` per method, so Lai and Lynn each
edit only their own cases and Git merges them cleanly.

**Shared files – change only after telling the group:** `src/router/index.js`, `src/App.vue`,
`src/main.js`, `src/services/api.js`, `src/services/auth.js`, `api/helpers.php`, `vite.config.js`, `package.json`.

## How the shared pieces work

**Calling the API** – always through `src/services/api.js`:

```js
import { apiGet, apiPost, apiPut, apiDelete, apiUpload, imageUrl } from '../services/api'

const { cars, total } = await apiGet('listings.php', { make: 'Toyota', sort: 'price_asc', page: 1 })
await apiPost('inquiries.php', { listing_id: 3, buyer_id: 2, message: 'Is it still available?' })
```

If PHP answers `{"error": "..."}`, the helper throws an `Error` with that message, so use `try / catch`.
PUT and DELETE are sent as POST plus an `X-HTTP-Method-Override` header (some servers block real PUT/DELETE);
`request_method()` in `api/helpers.php` turns them back, so PHP code just checks `case 'PUT':` as normal.

**Logged-in user** – `src/services/auth.js`: `getUser()`, `setUser(user)`, `logout()`, `isLoggedIn()` and a
reactive `currentUser` (use it in templates with `v-if`). Stored in `localStorage` under `user`:
`{ "user_id": 5, "full_name": "Sarah Lim", "email": "sarah@example.com", "role": "user" }`.

**Writing an endpoint** – replace the `not_built_yet(...)` line in your case:

```php
case 'POST':
  $in = json_input();
  if (empty($in['message'])) fail('Message is required.');
  $stmt = db()->prepare('INSERT INTO inquiries (listing_id, buyer_id, message) VALUES (?, ?, ?)');
  $stmt->bind_param('iis', $in['listing_id'], $in['buyer_id'], $in['message']);
  $stmt->execute();
  reply(array('inquiry_id' => db()->insert_id));
```

**Routes** – all 12 are in `src/router/index.js`. Login-only pages have `meta: { requiresAuth: true }`;
the check itself lives in `src/router/guards.js` (Yati). It only logs a message for now, so nobody gets locked out while building.

## Every work session

```bash
git pull origin main
# ... code, test in the browser ...
git add .
git commit -m "Add search and sort to car listing"
git push origin lai-browse
```

When a page works, open a Pull Request into `main` and post the link in WhatsApp. Lynn merges it, then everyone runs `git pull origin main`.

## Deploying (Lynn only)

1. In each `api/*.php` file, make sure the parts already live on Mercury (marked *already live*) contain the real code, not the stub.
2. `npm run build`
3. With WinSCP, upload the **contents** of `dist/`, plus the `api/` and `uploads/` folders, into the same Mercury folder,
   so `index.html`, `assets/`, `api/` and `uploads/` sit side by side. Put a Mercury `api/db.php` there too.

`base: './'` and hash URLs (`#/cars/3`) mean it works from any Mercury folder without server setup.
