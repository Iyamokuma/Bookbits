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
| Payments  | Paystack, Klump, Korapay                           |

## Layout

```
bookbits-node/
├── src/                  Express API
│   ├── api/              JSON endpoints (mounted at /api)
│   ├── lib/              Business logic: cart, orders, payments, mail, auth
│   ├── routes/           Payment callbacks and gateway webhooks
│   ├── content/          Legal page copy
│   ├── app.js            App assembly
│   └── server.js         Entry point
├── client/               React SPA
│   ├── src/pages/        Route components (storefront, auth, account, admin)
│   ├── src/components/   Shared UI
│   └── src/lib/          API client, formatting, data-fetching hook
├── db/schema.sql         PostgreSQL schema
├── scripts/              Schema push, MySQL migration, mail test
└── public/               Static assets and uploaded covers
```

## Getting started

```bash
npm install
cd client && npm install && cd ..
cp .env.example .env        # then fill in the values
npm run db:push             # create the tables in Supabase
```

Run the API and the SPA dev server in two terminals:

```bash
npm run dev                 # API on :3000
cd client && npm run dev    # SPA on :5173 — open this one
```

Vite proxies `/api`, `/payment`, `/img`, and `/uploads` through to the API, so
the app runs on a single origin and session cookies behave exactly as they will
in production.

### Production

```bash
cd client && npm run build   # emits client/dist
npm start                    # Express serves the API and the built SPA
```

Express serves `client/dist` and falls back to `index.html` for any non-API
path, so client-side routes resolve on a hard refresh.

## Environment

The required variables are `DATABASE_URL` and `SESSION_SECRET`; everything else
degrades gracefully when unset. See `.env.example` for the full list.

`DATABASE_URL` should be the Supabase **transaction pooler** string (port 6543),
found under Project Settings → Database → Connection string.

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

The dashboard lives at `/admin`, behind the credentials in `BOOKBITS_ADMIN_EMAIL`
and `BOOKBITS_ADMIN_PASSWORD`. It covers orders (with status changes that email
the customer automatically), products, categories, and the blog, paginated five
rows at a time.
