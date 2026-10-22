CREATE TABLE IF NOT EXISTS app_sessions (
    session_id VARCHAR(128) NOT NULL,
    session_data TEXT NOT NULL,
    expires_at BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (session_id),
    KEY idx_app_sessions_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
