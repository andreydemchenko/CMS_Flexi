<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use Throwable;

class Post
{
    private const FILLABLE = [
        'author_id', 'category_id', 'title', 'slug', 'excerpt',
        'content', 'featured_image', 'status', 'published_at',
    ];

    // базовые поля + join к авторам/категориям, чтобы шаблон получал готовые данные
    private const SELECT_WITH_JOINS = <<<'SQL'
        SELECT p.*,
               c.name AS category_name,
               c.slug AS category_slug,
               u.username     AS author_username,
               u.display_name AS author_name,
               (SELECT COUNT(*) FROM comments cm
                  WHERE cm.post_id = p.id AND cm.status = 'approved') AS comments_count
          FROM posts p
          LEFT JOIN categories c ON c.id = p.category_id
          LEFT JOIN users      u ON u.id = p.author_id
    SQL;

    public function __construct(private Database $db) {}

    public function getAll(string $status = 'published', int $limit = 10, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = self::SELECT_WITH_JOINS .
            ' WHERE p.status = ? ORDER BY COALESCE(p.published_at, p.created_at) DESC LIMIT ' .
            $limit . ' OFFSET ' . $offset;

        return $this->db->fetchAll($sql, [$status]);
    }

    public function findById(int $id): ?array
    {
        $post = $this->db->fetchOne(
            self::SELECT_WITH_JOINS . ' WHERE p.id = ? LIMIT 1',
            [$id]
        );

        return $post ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $post = $this->db->fetchOne(
            self::SELECT_WITH_JOINS . ' WHERE p.slug = ? LIMIT 1',
            [$slug]
        );

        return $post ?: null;
    }

    public function getByCategory(int $categoryId, int $limit = 10, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = self::SELECT_WITH_JOINS .
            ' WHERE p.category_id = ? AND p.status = ?
              ORDER BY COALESCE(p.published_at, p.created_at) DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset;

        return $this->db->fetchAll($sql, [$categoryId, 'published']);
    }

    public function getByTag(int $tagId, int $limit = 10, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $sql = self::SELECT_WITH_JOINS .
            ' INNER JOIN post_tags pt ON pt.post_id = p.id
              WHERE pt.tag_id = ? AND p.status = ?
              ORDER BY COALESCE(p.published_at, p.created_at) DESC
              LIMIT ' . $limit . ' OFFSET ' . $offset;

        return $this->db->fetchAll($sql, [$tagId, 'published']);
    }

    public function getRecent(int $limit = 5): array
    {
        return $this->getAll('published', $limit, 0);
    }

    public function getPopular(int $limit = 5): array
    {
        $limit = max(1, $limit);

        $sql = self::SELECT_WITH_JOINS .
            ' WHERE p.status = ?
              ORDER BY p.views DESC, COALESCE(p.published_at, p.created_at) DESC
              LIMIT ' . $limit;

        return $this->db->fetchAll($sql, ['published']);
    }

    public function getTotalCount(string $status = 'published'): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c FROM posts WHERE status = ?',
            [$status]
        );

        return (int) ($row['c'] ?? 0);
    }

    public function getCountByCategory(int $categoryId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c FROM posts WHERE category_id = ? AND status = ?',
            [$categoryId, 'published']
        );

        return (int) ($row['c'] ?? 0);
    }

    public function getCountByTag(int $tagId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c
               FROM post_tags pt
               INNER JOIN posts p ON p.id = pt.post_id
              WHERE pt.tag_id = ? AND p.status = ?',
            [$tagId, 'published']
        );

        return (int) ($row['c'] ?? 0);
    }

    // создание + опциональная связь с тегами
    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'INSERT INTO posts
                    (author_id, category_id, title, slug, excerpt, content, featured_image, status, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['author_id'],
                    $data['category_id'] ?? null,
                    $data['title'],
                    $data['slug'],
                    $data['excerpt']        ?? null,
                    $data['content'],
                    $data['featured_image'] ?? null,
                    $data['status']         ?? 'draft',
                    $data['published_at']   ?? null,
                ]
            );

            $postId = (int) $this->db->lastInsertId();

            if (!empty($data['tag_ids']) && is_array($data['tag_ids'])) {
                $this->syncTags($postId, $data['tag_ids']);
            }

            $this->db->commit();
            return $postId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $sets   = [];
            $params = [];

            foreach (self::FILLABLE as $field) {
                if (array_key_exists($field, $data)) {
                    $sets[]   = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }

            $updated = false;
            if ($sets) {
                $params[] = $id;
                $sql = 'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = ?';
                $updated = $this->db->execute($sql, $params) > 0;
            }

            if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
                $this->syncTags($id, $data['tag_ids']);
                $updated = true;
            }

            $this->db->commit();
            return $updated;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM posts WHERE id = ?', [$id]) > 0;
    }

    // перезаписываем связи post_tags
    public function syncTags(int $postId, array $tagIds): void
    {
        $this->db->execute('DELETE FROM post_tags WHERE post_id = ?', [$postId]);

        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        if (!$tagIds) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($tagIds), '(?, ?)'));
        $params       = [];
        foreach ($tagIds as $tagId) {
            $params[] = $postId;
            $params[] = $tagId;
        }

        $this->db->execute(
            'INSERT INTO post_tags (post_id, tag_id) VALUES ' . $placeholders,
            $params
        );
    }

    public function incrementViews(int $id): void
    {
        $this->db->execute('UPDATE posts SET views = views + 1 WHERE id = ?', [$id]);
    }
}
