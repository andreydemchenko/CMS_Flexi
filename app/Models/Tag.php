<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Tag
{
    private const FILLABLE = ['name', 'slug'];

    public function __construct(private Database $db) {}

    public function getAll(): array
    {
        return $this->db->fetchAll(
            'SELECT t.*,
                    (SELECT COUNT(*) FROM post_tags pt
                       INNER JOIN posts p ON p.id = pt.post_id
                      WHERE pt.tag_id = t.id AND p.status = ?) AS posts_count
               FROM tags t
              ORDER BY t.name ASC',
            ['published']
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM tags WHERE id = ? LIMIT 1', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetchOne('SELECT * FROM tags WHERE slug = ? LIMIT 1', [$slug]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO tags (name, slug) VALUES (?, ?)',
            [$data['name'], $data['slug']]
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
        $sql = 'UPDATE tags SET ' . implode(', ', $sets) . ' WHERE id = ?';

        return $this->db->execute($sql, $params) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM tags WHERE id = ?', [$id]) > 0;
    }

    public function getPostCount(int $id): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c
               FROM post_tags pt
               INNER JOIN posts p ON p.id = pt.post_id
              WHERE pt.tag_id = ? AND p.status = ?',
            [$id, 'published']
        );

        return (int) ($row['c'] ?? 0);
    }

    // теги, привязанные к посту
    public function getByPostId(int $postId): array
    {
        return $this->db->fetchAll(
            'SELECT t.*
               FROM tags t
               INNER JOIN post_tags pt ON pt.tag_id = t.id
              WHERE pt.post_id = ?
              ORDER BY t.name ASC',
            [$postId]
        );
    }
}
