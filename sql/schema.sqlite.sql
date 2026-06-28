PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    phone TEXT,
    is_admin INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS listings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    price REAL,
    price_type TEXT NOT NULL DEFAULT 'fixed' CHECK (price_type IN ('fixed','wanted')),
    category TEXT NOT NULL,
    image TEXT,
    phone TEXT,
    isbn TEXT,
    author TEXT,
    edition TEXT,
    is_business INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'available' CHECK (status IN ('available','pending','completed')),
    qr_token TEXT NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS promotions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    image TEXT,
    link_url TEXT,
    active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    listing_id INTEGER NOT NULL,
    seller_id INTEGER NOT NULL,
    buyer_id INTEGER NOT NULL,
    qr_token TEXT NOT NULL,
    confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);
