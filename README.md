# Books, Bits & Co

Online bookstore — React single-page app on an Express JSON API, backed by Supabase Postgres.

## Stack

| Layer     | Technology                                        |
| --------- | ------------------------------------------------- |
| Front end | React 18, React Router, Tailwind CSS, Vite        |
| API       | Node 18+, Express 4                                |
| Database  | Supabase (PostgreSQL) via `pg`                     |
| Sessions  | `express-session` + `connect-pg-simple`            |
| Email     | Resend                                             |
| Payments  | Paystack, Klump, Korapay (each shown only when its keys are set) |

## Layout

A single Vite React app at the root, with the Express API beside it.

```
├── index.html            SPA shell
├── vite.config.js
├── vercel.json
├── src/                  React app
│   ├── pages/            Route components (storefront, auth, account, admin)
│   ├── components/       Shared UI
│   ├── context/          Global state (session, cart, store details)
│   └── lib/              API client, formatting, data-fetching hook
├── public/               Static assets — logo, hero images, uploaded covers
├── api/
│   └── [...path].js      Vercel serverless entry (catch-all, exports the app)
├── server/               Express API
│   ├── routes/           JSON endpoints, payment callbacks, gateway webhooks
│   ├── lib/              Cart, orders, payments, mail, auth, storage
│   ├── content/          Legal page copy
│   ├── app.js            App assembly
│   └── index.js          Local entry point (listens on :3000)
├── db/schema.sql         PostgreSQL schema
└── scripts/              Schema push, data migration, storage + mail setup
```

Everything the server owns is namespaced under `/api`; every other path belongs
to React. That split is what keeps the Vercel routing to one rule.

## Getting started

```bash
npm install
cp .env.example .env        # then fill in the values
npm run db:push             # create the tables in Supabase
npm run dev                 # API on :3000 + React on :5173
```

Open **http://localhost:5173** — that is the one to use while developing. Vite
serves the React app and proxies `/api` to Express, so everything is same-origin
and session cookies behave exactly as they will in production. Run
`npm run dev:api` or `npm run dev:web` to start just one half.

### Production

```bash
npm run build               # emits dist/
npm start                   # one Node process: API + the built SPA
```

## Deploying to Vercel

Import the repository; `vercel.json` supplies the rest. Vercel builds the React
app to `dist/` and serves it from the CDN, and turns the Express app into a
single function via `api/[...path].js`.

Set every variable from `.env.example` in **Project Settings → Environment
Variables**, with two deployment-specific notes:

- `BASE_URL` is your deployed URL, used for email links and as the fallback
  for everything else. On a production deployment, the project's production
  domain (`VERCEL_PROJECT_PRODUCTION_URL`) takes precedence. Payment return
  links and link previews use the domain the visitor is actually on, so a
  stale `BASE_URL` can't send customers to a dead host.
- `SUPABASE_SERVICE_KEY` is **required** in production. Vercel's filesystem is
  read-only, so admin cover uploads must go to object storage — see below.

Two things to know about how Vercel runs Express:

- `express.static()` is ignored. Static files are served from the build output,
  which is why `public/` is copied into `dist/` at build time.
- The API becomes one function on a pool of short-lived instances, so
  `DATABASE_URL` must be the Supabase **transaction pooler** (port 6543) and the
  Postgres pool is capped at one connection per instance.

If you change the deployment URL, update the webhook endpoint in each payment
gateway's dashboard to `<BASE_URL>/api/payment/webhook?gateway=<name>`.

Checkout only lists gateways whose keys are present, and rejects any other
choice server-side. Adding Klump or Korapay keys makes them appear with no code
change; removing keys hides them again. With no gateway configured at all,
checkout says so plainly instead of failing after the order is created.

## Environment

The required variables are `DATABASE_URL` and `SESSION_SECRET`; everything else
degrades gracefully when unset. See `.env.example` for the full list.

`DATABASE_URL` should be the Supabase **transaction pooler** string (port 6543),
found under Project Settings → Database → Connection string.

Do not set `NODE_ENV` in `.env`. Vite reads that file, and `development` there
makes `npm run build` ship React's development bundle.

## Google sign-in (suspended)

Signing up with Google is implemented and tested, but **switched off**. The
button does not render, and `POST /api/auth/google` refuses every request, so
email and password is the only way in. To enable it later:

1. In the [Google Cloud Console](https://console.cloud.google.com/apis/credentials),
   create an **OAuth client ID** of type *Web application*.
2. Under **Authorised JavaScript origins**, add every origin you sign in from —
   `http://localhost:5173` for development, plus your deployed URL.
3. Set `GOOGLE_CLIENT_ID` to that client id and `GOOGLE_SIGNIN_ENABLED=true`.
   There is no client secret: the browser receives an ID token, and the server
   verifies it. Both variables are required, so a stray client id cannot switch
   the feature on by itself.

The server verifies each ID token's signature, issuer, expiry, and audience
before trusting a single claim in it, so a token minted for another application
is rejected.

Account handling once enabled:

- A returning Google user is matched on their Google subject id, so changing
  their name or email at Google still resolves to the same account.
- If the address already has a password account, the two are linked rather than
  duplicated — but only when Google reports the address as verified, otherwise
  an unverified Google account could claim someone else's email.
- Accounts created through Google have no password and are treated as
  email-verified. They can still use "forgot password" to add one later.

## Cover image uploads

Admin product covers go to Supabase Storage when it is configured, and to
`public/uploads/covers/` otherwise. Local disk is fine for development but does
not work on Vercel, where the filesystem is read-only and ephemeral.

```bash
# .env: SUPABASE_URL + SUPABASE_SERVICE_KEY (the service_role JWT)
npm run storage:init        # creates the public "covers" bucket
```

Covers carried over from the old PHP site are stored as `uploads/covers/...`
paths and keep working, since they ship as static assets. New uploads are stored
as absolute Storage URLs. Both forms are resolved by `bookCoverUrl()`.

## Migrating data from the old MySQL database

With `DATABASE_URL` pointing at Supabase and MySQL still running:

```bash
npm run db:push        # schema first
npm run db:migrate     # then the data
```

The migration is idempotent — rerunning it will not duplicate rows — and it
resyncs identity sequences afterwards so new inserts don't collide. Existing
bcrypt password hashes carry over unchanged, so customers keep their passwords.

## Notes on the port from PHP

Behaviour that changed deliberately:

- **Webhook signatures are verified.** The PHP version accepted unauthenticated
  webhook calls, which meant anyone who knew an order id could mark it paid.
- **Order fulfilment is idempotent.** A callback and a webhook arriving for the
  same payment no longer decrement stock twice.
- **Sessions are regenerated on sign-in** to close off session fixation.
- **CSRF tokens are required** on every state-changing request.
- **Checkout enforces a complete address** — name, phone, street, city, state,
  postal code and country — rather than accepting partial ones.
- **Passwords hashed by PHP still work.** PHP writes `$2y$` bcrypt hashes;
  `bcryptjs` expects `$2a$`. The two are the same algorithm, so the prefix is
  rewritten at comparison time.

SQL was translated from MySQL to Postgres throughout: `AUTO_INCREMENT` became
identity columns, `TINYINT(1)` became `BOOLEAN`, `ENUM` columns became declared
enum types, `ON DUPLICATE KEY UPDATE` became `ON CONFLICT`, and `LIKE` became
`ILIKE` to preserve MySQL's case-insensitive matching.

## Admin

The dashboard lives at `/admin`, behind the credentials in `ADMIN_EMAIL` and
`ADMIN_PASSWORD`. `ADMIN_EMAIL` is only a sign-in name; no email is sent to it.
The dashboard covers:

- **Orders**, with status changes that email the customer automatically.
- **Products**, **categories** and the **blog**.
- **Pages**: content pages such as About us or FAQ, served at
  `/pages/<address>`. They're written in a rich-text editor with headings,
  lists, links and uploaded images, and there's a raw-HTML mode. The server
  sanitises every body before storing it (`server/lib/sanitize.js`). Ticking
  "Show in navigation bar" adds the page to the menu. Renaming a page's address
  updates its menu link, and deleting the page removes the link.
- **Navigation**: the links in the storefront header, in order. Each link can
  point at a store page, a custom page, a category, or any URL.
- **Settings**:
  - The **delivery fee** charged at checkout. Each order keeps the fee it was
    charged.
  - The **notification inbox(es)** that receive new-order and contact-form
    emails. The page warns when an address's domain has no mail server, and can
    send a test email.

Lists are paginated five rows at a time. Settings live in the `site_settings`
table, and a missing row falls back to the built-in default (₦1,500 delivery,
the original menu, and `STORE_EMAIL` for notifications).

## Order emails

When a payment is verified, by the browser callback or the gateway webhook,
whichever arrives first, two emails go out:

- **The customer** gets an order confirmation with the items, subtotal,
  delivery fee and total. Replies go to the shop inbox.
- **The shop** gets a new-order notification at every address under
  Settings → Order notifications. Replies go to the customer.

Both are awaited before the response is sent. Vercel freezes a function as
soon as it responds, so anything left running in the background is lost.
The webhook replies to the gateway immediately and uses `waitUntil` to finish
the work.

## Link previews

WhatsApp, Facebook, X, Telegram and other link-preview bots don't run
JavaScript, so the SPA shell alone gives every link the same preview. For
`/book/:id`, `/blog/:slug` and `/pages/:slug`, the server fills in the page's
own title, description, image, product price and structured data
(`server/routes/share.js`). Everything below `<head>` is the normal app.

On Vercel, `vercel.json` routes only bot and crawler user agents to that
function, so shoppers still get the SPA from the CDN. Every other route uses
the site-wide defaults in `index.html`. Their absolute image URL comes from
`BASE_URL` at build time. Covers over 300 KB, which WhatsApp would drop, are
served through Supabase's image resizer.
