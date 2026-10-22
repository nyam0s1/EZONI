# Fresh database setup

`schema_postgres.sql` defines every table used by the existing PHP app for managed PostgreSQL on Neon, including the serverless session table. `schema.sql` provides the same model for an optional MySQL-compatible deployment. Both create missing tables without dropping, truncating or replacing data. They do not upgrade an incompatible existing schema. Use an empty database for this fresh installation.

PostgreSQL uses the `citext` extension to retain the original case-insensitive username, email, tag, name and settings comparisons. Passwords and session IDs remain case-sensitive. The database account needs permission to create the extension and the application's tables and indexes.

PostgreSQL record timestamps default explicitly to Africa/Nairobi time, so daily reports retain the farm's date boundary across pooled database connections.

No recovered InfinityFree data or sample farm records are included. `seed_defaults.sql` adds editable starter inventory categories (Feeds, Meds, Tools), produce categories (Dairy, Crops, Eggs), and livestock species (Cattle, Goats, Sheep, Chicken, Duck). Initial species alert thresholds are 5, and existing thresholds are preserved. These choices let the existing forms work immediately and can be changed through **Admin > Settings**. They are starter choices, not recovered old database settings.

Deleting a stock item preserves its financial ledger, deleting an animal preserves its history, and deleting a mother detaches its offspring's mother reference.

## Initialize from a trusted terminal

Use PHP with `pdo_pgsql` enabled for Neon, or `pdo_mysql` for MySQL. Set `DATABASE_URL` to the Neon PostgreSQL connection URL and use TLS certificate verification (`sslmode=verify-full`). The shared connection factory validates TLS and certificates for hosted connections. Set `DB_SSL_CA` when the provider needs a specific trusted CA file or the runtime has no trusted system CA bundle.

The initial administrator is provided through these process environment variables, using your secrets manager or local terminal without committing values:

| Variable | Purpose |
| --- | --- |
| `DATABASE_URL` | Neon/PostgreSQL connection URL, including database credentials |
| `DB_SSL_CA` | Optional trusted database CA PEM path |
| `INITIAL_ADMIN_USERNAME` | First administrator's login name |
| `INITIAL_ADMIN_EMAIL` | First administrator's valid email address |
| `INITIAL_ADMIN_PASSWORD` | Unique password of 12–72 bytes, without surrounding whitespace |

For an optional MySQL-compatible database, omit `DATABASE_URL` and provide:

| Variable | Purpose |
| --- | --- |
| `DB_HOST` | MySQL server hostname |
| `DB_PORT` | MySQL port, default `3306` |
| `DB_NAME` | Existing, empty database to initialize |
| `DB_USER` | Database username |
| `DB_PASSWORD` | Database password |
| `DB_SSL_REQUIRED` | TLS is required by default for remote hosts; `true` requires an encrypted connection |
| `DB_SSL_CA` | Optional path to the provider's trusted CA PEM file, overriding trusted system CA discovery |

Run from the repository root:

```text
php database/initialize.php
```

The initializer uses the application's shared connection factory and chooses the correct schema from the connected PDO driver. TiDB integration environment variables (`TIDB_HOST`, `TIDB_PORT`, `TIDB_DATABASE`, `TIDB_USER`, `TIDB_PASSWORD`) are accepted as fallbacks for the corresponding MySQL `DB_*` values.

The script is CLI only, imports the schema and editable starter choices, hashes the initial password with PHP's current password algorithm, and never prints it. Existing users prevent it from creating another administrator. Repeating it with the same existing active administrator succeeds without changing the account or password. Missing starter choices are added without duplicating existing choices or changing existing species thresholds. Clear the `INITIAL_ADMIN_*` variables from the terminal after setup and do not put them in the public application's Vercel environment.

The existing registration screen creates pending standard users. Approve those requests from the administrator's Users page, or add users through that page as before.

For separate web and Android databases, run initialization once against each empty database using the intended credentials. Business records in those databases then remain independent. This schema is reconstructed from the repository rather than an export of the unavailable old database.
