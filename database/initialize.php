<?php
declare(strict_types=1);

// Never expose a setup or password-reset endpoint on the deployed website.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** Read a secret/configuration value without printing it. */
function requiredEnvironment(string $name): string
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new RuntimeException('Missing environment variable: ' . $name);
    }
    return $value;
}

/** Execute this repository's simple SQL files without enabling multi-statements. */
function executeSqlFile(PDO $pdo, string $filename): void
{
    $sql = file_get_contents(__DIR__ . '/' . $filename);
    if ($sql === false) {
        throw new RuntimeException('Could not read database/' . $filename . '.');
    }
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
}

try {
    // Validate all bootstrap inputs before creating tables or accounts.
    $adminUsername = trim(requiredEnvironment('INITIAL_ADMIN_USERNAME'));
    $adminEmail = trim(requiredEnvironment('INITIAL_ADMIN_EMAIL'));
    $adminPassword = requiredEnvironment('INITIAL_ADMIN_PASSWORD');
    if ($adminUsername === '' || strlen($adminUsername) > 100) {
        throw new RuntimeException('INITIAL_ADMIN_USERNAME must contain 1 to 100 bytes.');
    }
    if (strlen($adminEmail) > 254 || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('INITIAL_ADMIN_EMAIL must be a valid email address.');
    }
    if (strlen($adminPassword) < 12 || strlen($adminPassword) > 72 || trim($adminPassword) !== $adminPassword) {
        throw new RuntimeException('INITIAL_ADMIN_PASSWORD must contain 12 to 72 bytes without surrounding whitespace.');
    }

    require_once dirname(__DIR__) . '/includes/database_connection.php';
    $pdo = ezoniCreateDatabaseConnection();

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $schemaFile = match ($driver) {
        'pgsql' => 'schema_postgres.sql',
        'mysql' => 'schema.sql',
        default => throw new RuntimeException('Unsupported database driver: ' . $driver),
    };
    executeSqlFile($pdo, $schemaFile);

    $pdo->beginTransaction();
    executeSqlFile($pdo, 'seed_defaults.sql');
    $existing = $pdo->prepare('SELECT username, email, role, status FROM users WHERE username = ? OR email = ?');
    $existing->execute([$adminUsername, $adminEmail]);
    $accounts = $existing->fetchAll();
    if ($accounts !== []) {
        $account = count($accounts) === 1 ? $accounts[0] : null;
        if ($account === null || $account['username'] !== $adminUsername || $account['email'] !== $adminEmail
            || $account['role'] !== 'admin' || $account['status'] !== 'active') {
            throw new RuntimeException('The initial administrator conflicts with an existing account. No account was changed.');
        }
        $pdo->commit();
        fwrite(STDOUT, "Schema is ready. The matching active administrator already exists. Its password was not changed.\n");
        exit(0);
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) {
        throw new RuntimeException('Users already exist. Create additional administrators through the existing admin interface.');
    }

    $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $adminId = ezoniInsertAndGetId(
        $pdo,
        "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')",
        [$adminUsername, $adminEmail, $passwordHash]
    );
    $log = $pdo->prepare("INSERT INTO user_logs (user_id, action_type, performed_by) VALUES (?, 'Account Created', 'CLI Setup')");
    $log->execute([$adminId]);
    $pdo->commit();
    fwrite(STDOUT, "Schema is ready and the initial administrator was created. No passwords were printed.\n");
    fwrite(STDOUT, "Editable starter choices are ready. Use Admin > Settings to configure categories and species.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Database initialization failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
