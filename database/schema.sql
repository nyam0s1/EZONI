-- Fresh EZONI schema, reconstructed from the application's existing PHP queries.
-- Running this file again creates missing tables without deleting existing data.
-- Business records remain empty, with editable settings in seed_defaults.sql.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_status (status),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_logs_user_date (user_id, log_date),
    CONSTRAINT fk_user_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories and species are edited by the existing Admin > Settings pages.
-- Text values on records remain valid when an administrator removes an option.
CREATE TABLE IF NOT EXISTS system_options (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    option_type VARCHAR(50) NOT NULL,
    option_value VARCHAR(191) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_system_options_type_value (option_type, option_value)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS species_limits (
    species VARCHAR(191) NOT NULL,
    min_threshold INT NOT NULL DEFAULT 5,
    PRIMARY KEY (species)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS animals (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tag_id VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    species VARCHAR(191) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    breed VARCHAR(255) NOT NULL DEFAULT '',
    buying_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    dob DATE NULL DEFAULT NULL,
    mother_id INT UNSIGNED NULL DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_animals_tag (tag_id),
    KEY idx_animals_species_status (species, status),
    KEY idx_animals_status (status),
    KEY idx_animals_mother (mother_id),
    CONSTRAINT fk_animals_mother FOREIGN KEY (mother_id) REFERENCES animals (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- History retains the original tag/name/user text after edits or deletions.
CREATE TABLE IF NOT EXISTS animal_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    animal_tag VARCHAR(100) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    details TEXT NULL,
    performed_by VARCHAR(100) NOT NULL,
    log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_animal_logs_tag_id (animal_tag, id),
    KEY idx_animal_logs_date (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(50) NOT NULL,
    category VARCHAR(191) NOT NULL,
    min_threshold DECIMAL(15,3) NOT NULL DEFAULT 10.000,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_id INT UNSIGNED NULL DEFAULT NULL,
    item_name VARCHAR(255) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(50) NOT NULL,
    unit_cost DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    total_cost DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    performed_by VARCHAR(100) NOT NULL,
    log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_logs_item_id (item_id, id),
    KEY idx_stock_logs_action_date (action_type, log_date),
    KEY idx_stock_logs_date (log_date),
    CONSTRAINT fk_stock_logs_item FOREIGN KEY (item_id) REFERENCES stock (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produce (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(50) NOT NULL,
    category VARCHAR(191) NOT NULL,
    min_threshold DECIMAL(15,3) NOT NULL DEFAULT 10.000,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produce_name (name),
    KEY idx_produce_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Livestock sales also use this ledger, so product_name is a text snapshot.
CREATE TABLE IF NOT EXISTS sales_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(50) NOT NULL,
    unit_price DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    total_revenue DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    customer_name VARCHAR(255) NULL DEFAULT NULL,
    performed_by VARCHAR(100) NOT NULL,
    log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sales_logs_product_id (product_name, id),
    KEY idx_sales_logs_action_date (action_type, log_date),
    KEY idx_sales_logs_date (log_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_sessions (
    session_id VARCHAR(128) NOT NULL,
    session_data TEXT NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (session_id),
    KEY idx_app_sessions_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
