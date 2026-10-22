<?php

final class EzoniPdoSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT session_data FROM app_sessions WHERE session_id = ? AND expires_at > ?'
        );
        $stmt->execute([$id, time()]);
        $data = $stmt->fetchColumn();

        if ($data === false) {
            return '';
        }
        // Text storage avoids driver-specific BLOB/bytea handling and preserves bytes.
        return base64_decode((string) $data, true) ?: '';
    }

    public function write(string $id, string $data): bool
    {
        $expiresAt = time() + (int) ini_get('session.gc_maxlifetime');
        $data = base64_encode($data);
        $upsert = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? 'ON CONFLICT (session_id) DO UPDATE SET session_data = ?, expires_at = ?'
            : 'ON DUPLICATE KEY UPDATE session_data = ?, expires_at = ?';
        $stmt = $this->pdo->prepare(
            'INSERT INTO app_sessions (session_id, session_data, expires_at) VALUES (?, ?, ?) '
            . $upsert
        );
        $stmt->execute([$id, $data, $expiresAt, $data, $expiresAt]);

        return true;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE session_id = ?');
        $stmt->execute([$id]);

        return true;
    }

    public function gc(int $maxLifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM app_sessions WHERE expires_at <= ?');
        $stmt->execute([time()]);

        return $stmt->rowCount();
    }
}
?>
