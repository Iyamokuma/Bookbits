-- ============================================================
-- Bookbits — Supabase / PostgreSQL schema
--
-- Run once in Supabase: Dashboard -> SQL Editor -> paste -> Run
-- Or:  npm run db:push
--
-- Safe to re-run: every statement is idempotent.
-- ============================================================

-- ------------------------------------------------------------
-- ENUM types (Postgres equivalent of MySQL ENUM columns)
-- ------------------------------------------------------------
DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('customer', 'admin');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE cover_type AS ENUM ('paperback', 'hardcover');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE order_status AS ENUM ('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE payment_status AS ENUM ('pending', 'completed', 'failed', 'refunded');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE payment_method AS ENUM ('card', 'paypal', 'bank_transfer', 'cash_on_delivery', 'paystack', 'klump', 'korapayment');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

-- ------------------------------------------------------------
-- updated_at trigger (replaces MySQL ON UPDATE CURRENT_TIMESTAMP)
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ------------------------------------------------------------
-- 1. USERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name              VARCHAR(120) NOT NULL,
    email             VARCHAR(191) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,
    role              user_role    NOT NULL DEFAULT 'customer',
    email_verified_at TIMESTAMPTZ  NULL,
    remember_token    VARCHAR(100) NULL,
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT now()
);

DROP TRIGGER IF EXISTS users_updated_at ON users;
CREATE TRIGGER users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 2. CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT         NULL,
    icon        TEXT         NULL,
    bg_color    VARCHAR(60)  NULL,
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    is_active   BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);

DROP TRIGGER IF EXISTS categories_updated_at ON categories;
CREATE TRIGGER categories_updated_at BEFORE UPDATE ON categories
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 3. BOOKS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
    id                INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category_id       INTEGER      NOT NULL REFERENCES categories (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    title             VARCHAR(255) NOT NULL,
    author            VARCHAR(160) NOT NULL,
    isbn              VARCHAR(20)  NULL UNIQUE,
    description       TEXT         NULL,
    price             NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    sale_price        NUMERIC(10,2) NULL,
    has_cover_options BOOLEAN      NOT NULL DEFAULT FALSE,
    paperback_price   NUMERIC(10,2) NULL,
    hardcover_price   NUMERIC(10,2) NULL,
    stock_qty         INTEGER      NOT NULL DEFAULT 0,
    cover_image       VARCHAR(255) NULL,
    pages             INTEGER      NULL,
    publisher         VARCHAR(160) NULL,
    published_year    SMALLINT     NULL,
    language          VARCHAR(60)  NULL DEFAULT 'English',
    is_featured       BOOLEAN      NOT NULL DEFAULT FALSE,
    is_deal           BOOLEAN      NOT NULL DEFAULT FALSE,
    is_new_arrival    BOOLEAN      NOT NULL DEFAULT FALSE,
    is_book_bundle    BOOLEAN      NOT NULL DEFAULT FALSE,
    is_active         BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_books_category   ON books (category_id);
CREATE INDEX IF NOT EXISTS idx_books_active     ON books (is_active);
CREATE INDEX IF NOT EXISTS idx_books_featured   ON books (is_featured);
CREATE INDEX IF NOT EXISTS idx_books_deal       ON books (is_deal);
CREATE INDEX IF NOT EXISTS idx_books_new        ON books (is_new_arrival);
CREATE INDEX IF NOT EXISTS idx_books_bundle     ON books (is_book_bundle);

DROP TRIGGER IF EXISTS books_updated_at ON books;
CREATE TRIGGER books_updated_at BEFORE UPDATE ON books
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 4. CART ITEMS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id    INTEGER      NULL REFERENCES users (id) ON DELETE CASCADE,
    session_id VARCHAR(128) NULL,
    book_id    INTEGER      NOT NULL REFERENCES books (id) ON DELETE CASCADE,
    cover_type cover_type   NULL,
    qty        INTEGER      NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_cart_user ON cart_items (user_id);

DROP TRIGGER IF EXISTS cart_items_updated_at ON cart_items;
CREATE TRIGGER cart_items_updated_at BEFORE UPDATE ON cart_items
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 5. ORDERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id                   INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id              INTEGER      NOT NULL REFERENCES users (id) ON DELETE RESTRICT,
    status               order_status NOT NULL DEFAULT 'pending',
    subtotal             NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    discount             NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    shipping_fee         NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    total                NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    shipping_name        VARCHAR(160) NOT NULL,
    shipping_phone       VARCHAR(40)  NULL,
    shipping_address     VARCHAR(255) NOT NULL,
    shipping_city        VARCHAR(100) NOT NULL,
    shipping_state       VARCHAR(100) NOT NULL,
    shipping_zip         VARCHAR(20)  NOT NULL,
    shipping_country     VARCHAR(100) NOT NULL,
    notes                TEXT         NULL,
    tracking_number      VARCHAR(120) NULL,
    payment_confirmed_at TIMESTAMPTZ  NULL,
    checkout_snapshot    JSONB        NULL,
    created_at           TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at           TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_orders_user    ON orders (user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status  ON orders (status);
CREATE INDEX IF NOT EXISTS idx_orders_created ON orders (created_at);

DROP TRIGGER IF EXISTS orders_updated_at ON orders;
CREATE TRIGGER orders_updated_at BEFORE UPDATE ON orders
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 6. ORDER ITEMS (price snapshot at purchase time)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id   INTEGER      NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    book_id    INTEGER      NULL REFERENCES books (id) ON DELETE SET NULL,
    title      VARCHAR(255) NOT NULL,
    author     VARCHAR(160) NOT NULL,
    cover_type cover_type   NULL,
    qty        INTEGER      NOT NULL,
    unit_price NUMERIC(10,2) NOT NULL,
    subtotal   NUMERIC(10,2) NOT NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id);

-- ------------------------------------------------------------
-- 7. PAYMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id               INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    order_id         INTEGER        NOT NULL REFERENCES orders (id) ON DELETE CASCADE,
    method           payment_method NOT NULL,
    status           payment_status NOT NULL DEFAULT 'pending',
    amount           NUMERIC(10,2)  NOT NULL,
    currency         VARCHAR(10)    NOT NULL DEFAULT 'NGN',
    transaction_ref  VARCHAR(120)   NULL,
    gateway_response JSONB          NULL,
    paid_at          TIMESTAMPTZ    NULL,
    created_at       TIMESTAMPTZ    NOT NULL DEFAULT now(),
    updated_at       TIMESTAMPTZ    NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_payments_order  ON payments (order_id);
CREATE UNIQUE INDEX IF NOT EXISTS uq_payments_ref ON payments (transaction_ref) WHERE transaction_ref IS NOT NULL;

DROP TRIGGER IF EXISTS payments_updated_at ON payments;
CREATE TRIGGER payments_updated_at BEFORE UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 8. BLOGS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blogs (
    id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    title        VARCHAR(200) NOT NULL,
    slug         VARCHAR(200) NOT NULL UNIQUE,
    excerpt      TEXT         NULL,
    body         TEXT         NOT NULL,
    cover_image  VARCHAR(255) NULL,
    is_active    BOOLEAN      NOT NULL DEFAULT TRUE,
    published_at TIMESTAMPTZ  NULL,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_blogs_active_published ON blogs (is_active, published_at);

DROP TRIGGER IF EXISTS blogs_updated_at ON blogs;
CREATE TRIGGER blogs_updated_at BEFORE UPDATE ON blogs
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ------------------------------------------------------------
-- 9. WISHLIST
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wishlist (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id    INTEGER     NOT NULL REFERENCES users (id) ON DELETE CASCADE,
    book_id    INTEGER     NOT NULL REFERENCES books (id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    UNIQUE (user_id, book_id)
);

-- ------------------------------------------------------------
-- 10. PASSWORD RESETS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id         INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email      VARCHAR(191) NOT NULL,
    token      VARCHAR(64)  NOT NULL UNIQUE,
    expires_at TIMESTAMPTZ  NOT NULL,
    created_at TIMESTAMPTZ  NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_password_resets_email ON password_resets (email);

-- ------------------------------------------------------------
-- 11. SESSIONS (connect-pg-simple)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_sessions (
    sid    VARCHAR      NOT NULL COLLATE "default" PRIMARY KEY,
    sess   JSON         NOT NULL,
    expire TIMESTAMP(6) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_user_sessions_expire ON user_sessions (expire);

-- ------------------------------------------------------------
-- 12. MIGRATIONS
-- Idempotent alterations for databases created by an earlier version of this
-- file. Safe to re-run; `npm run db:push` applies them.
-- ------------------------------------------------------------

-- Social sign-in: accounts created through Google have no password, and are
-- matched on the Google subject id so a changed display name or email still
-- resolves to the same account.
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id  VARCHAR(64) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url TEXT        NULL;
ALTER TABLE users ALTER COLUMN password DROP NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS idx_users_google_id ON users (google_id)
    WHERE google_id IS NOT NULL;

-- Newsletter sign-ups from the footer form. Unsubscribing keeps the row so a
-- later re-subscribe does not silently re-send the welcome email.
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id              INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    email           VARCHAR(191) NOT NULL UNIQUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),
    unsubscribed_at TIMESTAMPTZ  NULL
);

-- ------------------------------------------------------------
-- SEED — default categories
-- ------------------------------------------------------------
INSERT INTO categories (name, slug, description, sort_order) VALUES
    ('Fiction',        'fiction',        'Novels and literary fiction', 1),
    ('Self Help',      'self-help',      'Personal growth and habits',  2),
    ('Business',       'business',       'Business, money and careers', 3),
    ('Children',       'children',       'Books for young readers',     4),
    ('Christian',      'christian',      'Bibles, devotionals and faith', 5),
    ('Stationery',     'stationery',     'Journals, pens and supplies', 6)
ON CONFLICT (slug) DO NOTHING;
