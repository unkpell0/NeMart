-- NeMart v2 — skema database demo (SQLite)
-- Semua waktu disimpan sebagai TEXT ISO 8601 waktu lokal: YYYY-MM-DD HH:MM:SS

CREATE TABLE IF NOT EXISTS users (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    email      TEXT NOT NULL UNIQUE,
    role       TEXT NOT NULL CHECK (role IN ('buyer', 'seller', 'admin')),
    prodi      TEXT,
    initials   TEXT,
    status     TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'blocked')),
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS stores (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id        INTEGER NOT NULL REFERENCES users(id),
    name            TEXT NOT NULL,
    slug            TEXT NOT NULL UNIQUE,
    description     TEXT,
    department      TEXT,
    pickup_point    TEXT,
    icon            TEXT NOT NULL DEFAULT 'store',
    verified_status TEXT NOT NULL DEFAULT 'pending' CHECK (verified_status IN ('pending', 'verified', 'rejected')),
    plan            TEXT NOT NULL DEFAULT 'free' CHECK (plan IN ('free', 'pro')),
    pro_until       TEXT,
    joined_at       TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    icon TEXT NOT NULL DEFAULT 'tag'
);

CREATE TABLE IF NOT EXISTS products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    store_id    INTEGER NOT NULL REFERENCES stores(id),
    category_id INTEGER NOT NULL REFERENCES categories(id),
    name        TEXT NOT NULL,
    description TEXT,
    price       INTEGER NOT NULL,
    stock       INTEGER NOT NULL DEFAULT 0,
    variants    TEXT NOT NULL DEFAULT '[]',
    image       TEXT,
    status      TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    sold_count  INTEGER NOT NULL DEFAULT 0,
    view_count  INTEGER NOT NULL DEFAULT 0,
    boost_until TEXT,
    is_po       INTEGER NOT NULL DEFAULT 0,
    po_deadline TEXT,
    created_at  TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS cart_items (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    variant    TEXT NOT NULL DEFAULT '',
    note       TEXT NOT NULL DEFAULT '',
    qty        INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    UNIQUE (user_id, product_id, variant)
);

CREATE TABLE IF NOT EXISTS orders (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    code            TEXT NOT NULL UNIQUE,
    payment_ref     TEXT NOT NULL,
    buyer_id        INTEGER NOT NULL REFERENCES users(id),
    store_id        INTEGER NOT NULL REFERENCES stores(id),
    status          TEXT NOT NULL DEFAULT 'placed' CHECK (status IN ('placed', 'processing', 'ready', 'completed', 'cancelled')),
    escrow_status   TEXT NOT NULL DEFAULT 'held' CHECK (escrow_status IN ('held', 'released', 'refunded')),
    handover_method TEXT NOT NULL DEFAULT 'pickup' CHECK (handover_method IN ('pickup', 'delivery')),
    pickup_point    TEXT,
    delivery_notes  TEXT,
    subtotal        INTEGER NOT NULL DEFAULT 0,
    service_fee     INTEGER NOT NULL DEFAULT 0,
    shipping_fee    INTEGER NOT NULL DEFAULT 0,
    total           INTEGER NOT NULL DEFAULT 0,
    commission      INTEGER NOT NULL DEFAULT 0,
    payment_method  TEXT,
    paid_at         TEXT,
    completed_at    TEXT,
    cancel_reason   TEXT,
    created_at      TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id       INTEGER NOT NULL REFERENCES orders(id),
    product_id     INTEGER NOT NULL REFERENCES products(id),
    name_snapshot  TEXT NOT NULL,
    price_snapshot INTEGER NOT NULL,
    variant        TEXT NOT NULL DEFAULT '',
    note           TEXT NOT NULL DEFAULT '',
    qty            INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS order_events (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id   INTEGER NOT NULL REFERENCES orders(id),
    status     TEXT NOT NULL,
    title      TEXT NOT NULL,
    note       TEXT,
    actor_id   INTEGER REFERENCES users(id),
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id),
    type       TEXT NOT NULL DEFAULT 'system' CHECK (type IN ('order', 'review', 'system')),
    title      TEXT NOT NULL,
    body       TEXT,
    link       TEXT,
    is_read    INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS reviews (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id    INTEGER NOT NULL REFERENCES orders(id),
    product_id  INTEGER NOT NULL REFERENCES products(id),
    buyer_id    INTEGER NOT NULL REFERENCES users(id),
    store_id    INTEGER NOT NULL REFERENCES stores(id),
    rating      INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    seller_reply TEXT,
    created_at  TEXT NOT NULL,
    UNIQUE (order_id, product_id)
);

CREATE TABLE IF NOT EXISTS boosts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    store_id   INTEGER NOT NULL REFERENCES stores(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    starts_at  TEXT NOT NULL,
    ends_at    TEXT NOT NULL,
    price      INTEGER NOT NULL DEFAULT 0,
    source     TEXT NOT NULL DEFAULT 'paid' CHECK (source IN ('paid', 'pro_free')),
    created_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_products_store    ON products (store_id);
CREATE INDEX IF NOT EXISTS idx_products_category ON products (category_id);
CREATE INDEX IF NOT EXISTS idx_products_boost    ON products (boost_until);
CREATE INDEX IF NOT EXISTS idx_orders_buyer      ON orders (buyer_id);
CREATE INDEX IF NOT EXISTS idx_orders_store      ON orders (store_id);
CREATE INDEX IF NOT EXISTS idx_orders_status     ON orders (status);
CREATE INDEX IF NOT EXISTS idx_order_items_order ON order_items (order_id);
CREATE INDEX IF NOT EXISTS idx_events_order      ON order_events (order_id);
CREATE INDEX IF NOT EXISTS idx_notif_user        ON notifications (user_id, is_read);
CREATE INDEX IF NOT EXISTS idx_reviews_product   ON reviews (product_id);
CREATE INDEX IF NOT EXISTS idx_reviews_store     ON reviews (store_id);
CREATE INDEX IF NOT EXISTS idx_cart_user         ON cart_items (user_id);
