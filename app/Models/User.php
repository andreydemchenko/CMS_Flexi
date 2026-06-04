<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    // поля, которые можно менять через update()
    private const FILLABLE = ['username', 'email', 'display_name', 'role', 'is_active'];

    public function __construct(private Database $db) {}

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll('SELECT * FROM users ORDER BY id DESC');
    }

    // создание — пароль хешируем тут
    public function create(array $data): int
    {
        $sql = 'INSERT INTO users (username, email, password_hash, display_name, role)
                VALUES (?, ?, ?, ?, ?)';

        $this->db->execute($sql, [
            $data['username'],
            $data['email'],
            password_hash((string)$data['password'], PASSWORD_DEFAULT),
            $data['display_name'] ?? null,
            $data['role'] ?? 'subscriber',
        ]);

        return (int) $this->db->lastInsertId();
    }

    // обновление по белому списку полей
    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [];

        foreach (self::FILLABLE as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        // пароль — отдельно, чтобы не забыть хеш
        if (!empty($data['password'])) {
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash((string)$data['password'], PASSWORD_DEFAULT);
        }

        if (!$sets) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';

        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM users WHERE id = ?', [$id]) > 0;
    }

    public function totalCount(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) AS c FROM users');
        return (int) ($row['c'] ?? 0);
    }

    // есть ли уже пользователь с таким username/email (исключая редактируемого)
    public function isTaken(string $field, string $value, ?int $excludeId = null): bool
    {
        if (!in_array($field, ['username', 'email'], true)) {
            return false;
        }
        if ($excludeId === null) {
            $row = $this->db->fetchOne("SELECT 1 AS x FROM users WHERE {$field} = ? LIMIT 1", [$value]);
        } else {
            $row = $this->db->fetchOne("SELECT 1 AS x FROM users WHERE {$field} = ? AND id <> ? LIMIT 1", [$value, $excludeId]);
        }
        return $row !== null;
    }
}
