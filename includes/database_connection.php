<?php
declare(strict_types=1);

/** Create the same verified database connection for the app and CLI setup. */
function ezoniCreateDatabaseConnection(): PDO
{
    $databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL');
    if (is_string($databaseUrl) && $databaseUrl !== '') {
        return ezoniCreatePostgresConnection($databaseUrl);
    }
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('The PHP pdo_mysql extension is required.');
    }

    $settings = [];
    foreach ([
        'DB_HOST' => 'TIDB_HOST',
        'DB_NAME' => 'TIDB_DATABASE',
        'DB_USER' => 'TIDB_USER',
        'DB_PASSWORD' => 'TIDB_PASSWORD',
        'DB_PORT' => 'TIDB_PORT',
    ] as $name => $alias) {
        $value = getenv($name);
        $settings[$name] = $value === false ? getenv($alias) : $value;
    }
    foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $name) {
        if ($settings[$name] === false || ($name !== 'DB_PASSWORD' && $settings[$name] === '')) {
            throw new RuntimeException('Missing database configuration: ' . $name);
        }
    }

    $host = $settings['DB_HOST'];
    $database = $settings['DB_NAME'];
    $port = $settings['DB_PORT'] ?: '3306';
    if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535
        || strpbrk($host . $database, ";\r\n\0") !== false) {
        throw new RuntimeException('Invalid database hostname, database name or port.');
    }

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ];
    $sslSetting = getenv('DB_SSL_REQUIRED');
    $sslRequired = $sslSetting === false || $sslSetting === ''
        ? !in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true)
        : filter_var($sslSetting, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($sslRequired === null) {
        throw new RuntimeException('DB_SSL_REQUIRED must be true or false.');
    }

    $sslCa = getenv('DB_SSL_CA');
    if ($sslCa !== false && $sslCa !== '') {
        $sslRequired = true;
        if (!is_file($sslCa) || !is_readable($sslCa)) {
            throw new RuntimeException('DB_SSL_CA must be a readable trusted CA certificate file.');
        }
    } elseif ($sslRequired) {
        $locations = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        $candidates = [
            ini_get('openssl.cafile'),
            getenv('SSL_CERT_FILE'),
            $locations['default_cert_file'] ?? '',
            __DIR__ . '/../database/certs/cacert.pem',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/cert.pem',
        ];
        $sslCa = false;
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate) && is_readable($candidate)) {
                $sslCa = $candidate;
                break;
            }
        }
        if ($sslCa === false) {
            throw new RuntimeException('Set DB_SSL_CA to the database provider\'s trusted CA certificate file.');
        }
    }
    if ($sslRequired) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $settings['DB_USER'],
        $settings['DB_PASSWORD'],
        $options
    );
    if ($sslRequired) {
        $status = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_ASSOC);
        if (!$status || (string) $status['Value'] === '') {
            throw new RuntimeException('The database connection must use TLS.');
        }
    }
    date_default_timezone_set('Africa/Nairobi');
    $pdo->exec("SET time_zone = '+03:00'");
    return $pdo;
}

/** Neon/Vercel PostgreSQL connection; never trust a URL's weaker TLS setting. */
function ezoniCreatePostgresConnection(string $url): PDO
{
    if (!extension_loaded('pdo_pgsql')) {
        throw new RuntimeException('The PHP pdo_pgsql extension is required.');
    }
    $parts = parse_url($url);
    if ($parts === false || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)
        || empty($parts['host']) || empty($parts['path']) || !isset($parts['user'], $parts['pass'])) {
        throw new RuntimeException('DATABASE_URL must be a complete PostgreSQL connection URL.');
    }
    $host = $parts['host'];
    $database = rawurldecode(ltrim($parts['path'], '/'));
    $port = $parts['port'] ?? 5432;
    if ($database === '' || strpbrk($host . $database, ";\r\n\0") !== false) {
        throw new RuntimeException('Invalid PostgreSQL hostname or database name.');
    }
    $isLocal = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    $sslMode = $isLocal ? 'disable' : 'verify-full';
    $dsn = 'pgsql:host=' . ezoniPostgresDsnValue($host)
        . ';port=' . $port . ';dbname=' . ezoniPostgresDsnValue($database)
        . ';connect_timeout=10;client_encoding=UTF8;sslmode=' . $sslMode;
    // Explicit routing also supports PHP packages bundled with libpq before SNI.
    if (preg_match('/^(ep-[a-z0-9-]+)\.[a-z0-9.-]+\.neon\.(tech|build)$/i', $host, $matches)) {
        $endpoint = preg_replace('/-pooler$/i', '', $matches[1]);
        $dsn .= ';options=' . ezoniPostgresDsnValue('endpoint=' . $endpoint);
    }
    if (!$isLocal) {
        $sslCa = getenv('DB_SSL_CA');
        $locations = function_exists('openssl_get_cert_locations') ? openssl_get_cert_locations() : [];
        $candidates = $sslCa !== false && $sslCa !== '' ? [$sslCa] : [
            ini_get('openssl.cafile'),
            getenv('SSL_CERT_FILE'),
            $locations['default_cert_file'] ?? '',
            __DIR__ . '/../database/certs/cacert.pem',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/ssl/cert.pem',
        ];
        $trustedCa = null;
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate) && is_readable($candidate)
                && strpbrk($candidate, ";\r\n\0") === false) {
                $trustedCa = $candidate;
                break;
            }
        }
        if ($trustedCa === null) {
            throw new RuntimeException('Set DB_SSL_CA to a readable trusted CA certificate bundle.');
        }
        $dsn .= ';sslrootcert=' . ezoniPostgresDsnValue($trustedCa);
    }
    $pdo = new PDO($dsn, rawurldecode($parts['user']), rawurldecode($parts['pass']), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Use parameterized unnamed statements with serverless transaction pooling.
        PDO::PGSQL_ATTR_DISABLE_PREPARES => true,
    ]);
    date_default_timezone_set('Africa/Nairobi');
    return $pdo;
}

function ezoniPostgresDsnValue(string $value): string
{
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $value) . "'";
}

/** Read generated IDs in the INSERT statement, including through a transaction pool. */
function ezoniInsertAndGetId(PDO $pdo, string $sql, array $parameters): string
{
    $isPostgres = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    $statement = $pdo->prepare($sql . ($isPostgres ? ' RETURNING id' : ''));
    $statement->execute($parameters);
    return $isPostgres ? (string) $statement->fetchColumn() : (string) $pdo->lastInsertId();
}
