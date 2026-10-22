-- Fresh PostgreSQL version of the same EZONI data model.
-- Running this file again creates missing tables/indexes without deleting data.
-- Business records remain empty, with editable settings in seed_defaults.sql.

-- Preserve the previous MySQL case-insensitive lookups and unique identifiers.
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE IF NOT EXISTS users (
    id SERIAL NOT NULL,
    username CITEXT NOT NULL CHECK (char_length(username) <= 100),
    email CITEXT NOT NULL CHECK (char_length(email) <= 254),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email)
);
CREATE INDEX IF NOT EXISTS idx_users_status ON users (status);
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);

CREATE TABLE IF NOT EXISTS user_logs (
    id BIGSERIAL NOT NULL,
    user_id INTEGER NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    performed_by CITEXT NOT NULL,
    log_date TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id),
    CONSTRAINT fk_user_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_user_logs_user_date ON user_logs (user_id, log_date);

-- Settings remain editable through the original Admin > Settings pages.
CREATE TABLE IF NOT EXISTS system_options (
    id SERIAL NOT NULL,
    option_type VARCHAR(50) NOT NULL,
    option_value CITEXT NOT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_system_options_type_value ON system_options (option_type, option_value);

CREATE TABLE IF NOT EXISTS species_limits (
    species CITEXT NOT NULL,
    min_threshold INTEGER NOT NULL DEFAULT 5,
    PRIMARY KEY (species)
);

CREATE TABLE IF NOT EXISTS animals (
    id SERIAL NOT NULL,
    tag_id CITEXT NOT NULL,
    name CITEXT NOT NULL,
    species CITEXT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    breed VARCHAR(255) NOT NULL DEFAULT '',
    buying_price NUMERIC(15,2) NOT NULL DEFAULT 0.00,
    dob DATE NULL DEFAULT NULL,
    mother_id INTEGER NULL DEFAULT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id),
    CONSTRAINT uq_animals_tag UNIQUE (tag_id),
    CONSTRAINT fk_animals_mother FOREIGN KEY (mother_id) REFERENCES animals (id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_animals_species_status ON animals (species, status);
CREATE INDEX IF NOT EXISTS idx_animals_status ON animals (status);
CREATE INDEX IF NOT EXISTS idx_animals_mother ON animals (mother_id);

-- History stores snapshots after the original records are edited or deleted.
CREATE TABLE IF NOT EXISTS animal_logs (
    id BIGSERIAL NOT NULL,
    animal_tag CITEXT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    details TEXT NULL,
    performed_by CITEXT NOT NULL,
    log_date TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_animal_logs_tag_id ON animal_logs (animal_tag, id);
CREATE INDEX IF NOT EXISTS idx_animal_logs_date ON animal_logs (log_date);

CREATE TABLE IF NOT EXISTS stock (
    id SERIAL NOT NULL,
    name CITEXT NOT NULL,
    quantity NUMERIC(15,3) NOT NULL DEFAULT 0.000,
    unit CITEXT NOT NULL,
    category CITEXT NOT NULL,
    min_threshold NUMERIC(15,3) NOT NULL DEFAULT 10.000,
    created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_stock_category ON stock (category);

CREATE TABLE IF NOT EXISTS stock_logs (
    id BIGSERIAL NOT NULL,
    item_id INTEGER NULL DEFAULT NULL,
    item_name CITEXT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    quantity NUMERIC(15,3) NOT NULL DEFAULT 0.000,
    unit CITEXT NOT NULL,
    unit_cost NUMERIC(15,4) NOT NULL DEFAULT 0.0000,
    total_cost NUMERIC(15,2) NOT NULL DEFAULT 0.00,
    performed_by CITEXT NOT NULL,
    log_date TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id),
    CONSTRAINT fk_stock_logs_item FOREIGN KEY (item_id) REFERENCES stock (id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_stock_logs_item_id ON stock_logs (item_id, id);
CREATE INDEX IF NOT EXISTS idx_stock_logs_action_date ON stock_logs (action_type, log_date);
CREATE INDEX IF NOT EXISTS idx_stock_logs_date ON stock_logs (log_date);

CREATE TABLE IF NOT EXISTS produce (
    id SERIAL NOT NULL,
    name CITEXT NOT NULL,
    quantity NUMERIC(15,3) NOT NULL DEFAULT 0.000,
    unit CITEXT NOT NULL,
    category CITEXT NOT NULL,
    min_threshold NUMERIC(15,3) NOT NULL DEFAULT 10.000,
    created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id),
    CONSTRAINT uq_produce_name UNIQUE (name)
);
CREATE INDEX IF NOT EXISTS idx_produce_category ON produce (category);

-- Livestock sales also use this ledger, so product_name is a text snapshot.
CREATE TABLE IF NOT EXISTS sales_logs (
    id BIGSERIAL NOT NULL,
    product_name CITEXT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    quantity NUMERIC(15,3) NOT NULL DEFAULT 0.000,
    unit CITEXT NOT NULL,
    unit_price NUMERIC(15,4) NOT NULL DEFAULT 0.0000,
    total_revenue NUMERIC(15,2) NOT NULL DEFAULT 0.00,
    customer_name CITEXT NULL DEFAULT NULL,
    performed_by CITEXT NOT NULL,
    log_date TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Nairobi'),
    PRIMARY KEY (id)
);
CREATE INDEX IF NOT EXISTS idx_sales_logs_product_id ON sales_logs (product_name, id);
CREATE INDEX IF NOT EXISTS idx_sales_logs_action_date ON sales_logs (action_type, log_date);
CREATE INDEX IF NOT EXISTS idx_sales_logs_date ON sales_logs (log_date);

-- Session payloads are base64 text so the same handler works on both engines.
CREATE TABLE IF NOT EXISTS app_sessions (
    session_id VARCHAR(128) NOT NULL,
    session_data TEXT NOT NULL,
    expires_at BIGINT NOT NULL,
    PRIMARY KEY (session_id)
);
CREATE INDEX IF NOT EXISTS idx_app_sessions_expires_at ON app_sessions (expires_at);
