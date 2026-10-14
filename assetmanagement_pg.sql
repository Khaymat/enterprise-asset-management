-- PostgreSQL schema + data for assetmanagement

BEGIN;

-- =========================================
-- TABLE: categories
-- =========================================
CREATE TABLE categories (
    id      SERIAL PRIMARY KEY,
    name    VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO categories (id, name) VALUES
(1, 'Elektronik'),
(2, 'Furniture');

-- =========================================
-- TABLE: company_settings
-- =========================================
CREATE TABLE company_settings (
    id        SERIAL PRIMARY KEY,
    name      VARCHAR(150) NOT NULL,
    logo_path VARCHAR(255)
);

INSERT INTO company_settings (id, name, logo_path) VALUES
(1, 'PT NHM', NULL);

-- =========================================
-- TABLE: roles
-- =========================================
CREATE TABLE roles (
    id   SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (id, name) VALUES
(2, 'admin_stok'),
(1, 'admin_utama'),
(3, 'atasan'),
(4, 'user');

-- =========================================
-- TABLE: users
-- =========================================
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id       INTEGER NOT NULL,
    username      VARCHAR(60) UNIQUE,
    phone         VARCHAR(30),
    job_title     VARCHAR(100),
    department    VARCHAR(100),
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
);

INSERT INTO users (id, name, email, password_hash, role_id, username, phone, job_title, department) VALUES
(1, 'Admin Utama', 'admin@demo.com', '$2y$10$K/cwo0dfZ3G24aL5vbc7iutTGFrNeU.dPwIMcyW3Ho0P5ZvihFZ1K', 1, NULL, NULL, NULL, NULL),
(2, 'Admin Stok', 'stok@demo.com', '$2y$10$o2hvsDljzdw63uI3DZcPd.5abluB.3rjGcdJGtPlWoBM34afXB/hO', 2, NULL, NULL, NULL, NULL),
(3, 'Atasan', 'atasan@demo.com', '$2y$10$.kZ/1rYou/h8FzkZEDAjaOUNgjL0bn05.RU.JRWBGn2xTGm/bd41C', 3, NULL, NULL, NULL, NULL),
(4, 'User', 'user@demo.com', '$2y$10$AgAsvPp9nPkNfpRSza7yNemPuuRhBkf8C8uJ5jTUr03yz5PHsJByC', 4, NULL, NULL, NULL, NULL);

-- =========================================
-- TABLE: suppliers
-- =========================================
CREATE TABLE suppliers (
    id             SERIAL PRIMARY KEY,
    name           VARCHAR(120) NOT NULL,
    contact        VARCHAR(120),
    email          VARCHAR(120),
    pic_name       VARCHAR(120),
    pic_title      VARCHAR(120),
    phone          VARCHAR(60),
    payment_method VARCHAR(30),
    status         VARCHAR(30),
    address        VARCHAR(255),
    supply_type    VARCHAR(120)
);

INSERT INTO suppliers (id, name, contact, email, pic_name, pic_title, phone, payment_method, status, address, supply_type) VALUES
(1, 'PT Sumber Teknologi', '0812-3456-7890', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- =========================================
-- TABLE: items
-- =========================================
CREATE TABLE items (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    sku         VARCHAR(60) UNIQUE,
    stock       INTEGER DEFAULT 0,
    category_id INTEGER,
    CONSTRAINT fk_items_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO items (id, name, sku, stock, category_id) VALUES
(1, 'Laptop', 'IT-001', 10041, NULL),
(2, 'Mouse', 'IT-002', 90, NULL),
(3, 'Keyboard', 'IT-003', 100020, NULL),
(4, 'Laptop Acer Aspire 5', 'LAP-ACER-A5', 10, 1),
(5, 'Printer Epson L3110', 'PRN-EP-L3110', 5, 1),
(6, 'Meja Kantor Kayu', 'MEJ-KAY-01', 15, 2),
(7, 'Kursi Putar Hitam', 'KRS-PTR-HTM', 20, 2),
(8, 'Proyektor BenQ MX532', 'PRJ-BQ-532', 3, 1);

-- =========================================
-- TABLE: purchases
-- =========================================
CREATE TABLE purchases (
    id             SERIAL PRIMARY KEY,
    admin_id       INTEGER NOT NULL,
    item_id        INTEGER NOT NULL,
    supplier_id    INTEGER NOT NULL,
    qty            INTEGER NOT NULL,
    price_total    NUMERIC(12,2) NOT NULL,
    note_file      VARCHAR(255),
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    purchase_code  VARCHAR(60),
    purchase_date  DATE,
    unit_price     NUMERIC(12,2),
    payment_method VARCHAR(30),
    status         VARCHAR(20),
    note_text      VARCHAR(255),
    CONSTRAINT fk_purchases_admin
        FOREIGN KEY (admin_id) REFERENCES users(id),
    CONSTRAINT fk_purchases_item
        FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_purchases_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- =========================================
-- TABLE: receivings
-- =========================================
CREATE TABLE receivings (
    id             SERIAL PRIMARY KEY,
    receiver_id    INTEGER NOT NULL,
    receiver_name  VARCHAR(120) NOT NULL,
    item_id        INTEGER NOT NULL,
    supplier_id    INTEGER NOT NULL,
    qty            INTEGER NOT NULL,
    unit_price     NUMERIC(12,2) DEFAULT 0.00,
    total_price    NUMERIC(12,2) DEFAULT 0.00,
    payment_method VARCHAR(30),
    status         VARCHAR(20),
    note_text      VARCHAR(255),
    receiving_date DATE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_receivings_receiver
        FOREIGN KEY (receiver_id) REFERENCES users(id),
    CONSTRAINT fk_receivings_item
        FOREIGN KEY (item_id) REFERENCES items(id),
    CONSTRAINT fk_receivings_supplier
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- =========================================
-- TABLE: reimbursements
-- MySQL enum('pending','approved','rejected')
-- =========================================
CREATE TABLE reimbursements (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL,
    description  VARCHAR(255) NOT NULL,
    amount       NUMERIC(12,2) NOT NULL,
    receipt_file VARCHAR(255),
    status       VARCHAR(10) DEFAULT 'pending'
                 CHECK (status IN ('pending','approved','rejected')),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reimbursements_user
        FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =========================================
-- TABLE: requests
-- MySQL enum('pending','approved','rejected')
-- =========================================
CREATE TABLE requests (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL,
    item_id      INTEGER NOT NULL,
    qty          INTEGER NOT NULL,
    note         VARCHAR(255),
    status       VARCHAR(10) DEFAULT 'pending'
                 CHECK (status IN ('pending','approved','rejected')),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    department   VARCHAR(120),
    request_date DATE,
    photo_file   VARCHAR(255),
    CONSTRAINT fk_requests_user
        FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_requests_item
        FOREIGN KEY (item_id) REFERENCES items(id)
);

-- =========================================
-- TABLE: stock_movements
-- MySQL enum('in','out')
-- =========================================
CREATE TABLE stock_movements (
    id         SERIAL PRIMARY KEY,
    item_id    INTEGER NOT NULL,
    type       VARCHAR(3) NOT NULL
               CHECK (type IN ('in','out')),
    qty        INTEGER NOT NULL,
    source     VARCHAR(50),
    ref_id     INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note_text  VARCHAR(255),
    CONSTRAINT fk_stock_movements_item
        FOREIGN KEY (item_id) REFERENCES items(id)
);

INSERT INTO stock_movements (id, item_id, type, qty, source, ref_id, created_at, note_text) VALUES
(1, 1, 'in', 12, 'manual', NULL, '2025-11-14 12:51:54', NULL),
(2, 1, 'in', 6, 'manual', NULL, '2025-11-14 12:55:15', NULL),
(3, 1, 'in', 6, 'manual', NULL, '2025-11-14 13:36:42', NULL),
(4, 3, 'in', 10, 'manual', NULL, '2025-11-14 13:36:56', NULL),
(5, 3, 'out', 10, 'request', 1, '2025-11-14 13:41:02', NULL),
(6, 3, 'in', 1, 'purchase', 1, '2025-11-14 13:57:28', NULL),
(7, 1, 'in', 1, 'purchase', 2, '2025-11-14 14:43:39', NULL),
(8, 1, 'out', 1, 'request', 2, '2025-11-14 14:45:11', NULL),
(9, 1, 'in', 1, 'purchase', 3, '2025-11-14 14:49:40', NULL),
(10, 1, 'out', 1, 'request', 3, '2025-11-14 14:51:29', NULL),
(11, 1, 'in', 10, 'manual', NULL, '2025-11-14 14:52:03', NULL),
(12, 2, 'in', 1, 'purchase', 4, '2025-11-14 14:54:27', NULL),
(13, 3, 'in', 10, 'manual', NULL, '2025-11-14 14:55:13', NULL),
(14, 3, 'out', 1, 'request', 4, '2025-11-14 14:56:32', NULL),
(15, 1, 'in', 10000, 'purchase', 5, '2025-11-14 15:44:36', NULL),
(16, 3, 'in', 100000, 'receiving', 1, '2025-11-14 15:52:44', NULL),
(17, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', ''),
(18, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', ''),
(19, 1, 'in', 2, 'manual', NULL, '2025-11-13 17:00:00', ''),
(20, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', '');

-- =========================================
-- TABLE: stock_movements_proofs
-- =========================================
CREATE TABLE stock_movements_proofs (
    id         SERIAL PRIMARY KEY,
    movement_id INTEGER NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movement_proof
        FOREIGN KEY (movement_id) REFERENCES stock_movements(id)
);

INSERT INTO stock_movements_proofs (id, movement_id, file_path, created_at) VALUES
(1, 13, 'uploads/20251114_145513_1028_WhatsApp_Image_2025-11-13_at_20.09.33.jpeg', '2025-11-14 14:55:13');

COMMIT;

SELECT setval('categories_id_seq', (SELECT MAX(id) FROM categories));
SELECT setval('company_settings_id_seq', (SELECT MAX(id) FROM company_settings));
SELECT setval('roles_id_seq', (SELECT MAX(id) FROM roles));
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));
SELECT setval('suppliers_id_seq', (SELECT MAX(id) FROM suppliers));
SELECT setval('items_id_seq', (SELECT MAX(id) FROM items));
SELECT setval('purchases_id_seq', COALESCE((SELECT MAX(id) FROM purchases), 1), true);
SELECT setval('receivings_id_seq', COALESCE((SELECT MAX(id) FROM receivings), 1), true);
SELECT setval('reimbursements_id_seq', COALESCE((SELECT MAX(id) FROM reimbursements), 1), true);
SELECT setval('requests_id_seq', COALESCE((SELECT MAX(id) FROM requests), 1), true);
SELECT setval('stock_movements_id_seq', (SELECT MAX(id) FROM stock_movements));
SELECT setval('stock_movements_proofs_id_seq', (SELECT MAX(id) FROM stock_movements_proofs));
