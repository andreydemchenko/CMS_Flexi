<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    private const FILLABLE = ['parent_id', 'name', 'slug', 'description'];

    public function __construct(private Database $db) {}

    public function getAll(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*,
                    (SELECT COUNT(*) FROM posts p
                       WHERE p.category_id = c.id AND p.status = ?) AS posts_count
               FROM categories c
              ORDER BY c.name ASC',
            ['published']
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM categories WHERE id = ? LIMIT 1', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetchOne('SELECT * FROM categories WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO categories (parent_id, name, slug, description) VALUES (?, ?, ?, ?)',
            [
                $data['parent_id'] ?? null,
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

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

        if (!$sets) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE categories SET ' . implode(', ', $sets) . ' WHERE id = ?';

        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM categories WHERE id = ?', [$id]) > 0;
    }

    // количество опубликованных постов в категории
    public function getPostCount(int $id): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c FROM posts WHERE category_id = ? AND status = ?',
            [$id, 'published']
        );

        return (int) ($row['c'] ?? 0);
    }
}
