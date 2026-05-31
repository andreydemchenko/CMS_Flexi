<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Comment
{
    public function __construct(private Database $db) {}

    // комментарии поста в виде дерева (parent_id -> children)
    public function getByPostId(int $postId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT c.*, u.display_name AS user_display_name, u.username AS user_username
               FROM comments c
               LEFT JOIN users u ON u.id = c.user_id
              WHERE c.post_id = ? AND c.status = ?
              ORDER BY c.created_at ASC',
            [$postId, 'approved']
        );

        return $this->buildTree($rows);
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM comments WHERE id = ? LIMIT 1', [$id]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO comments
                (post_id, user_id, parent_id, author_name, author_email, content, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['post_id'],
                $data['user_id']      ?? null,
                $data['parent_id']    ?? null,
                $data['author_name']  ?? null,
                $data['author_email'] ?? null,
                $data['content'],
                $data['status']       ?? 'pending',
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function approve(int $id): bool
    {
        return $this->db->execute(
            'UPDATE comments SET status = ? WHERE id = ?',
            ['approved', $id]
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM comments WHERE id = ?', [$id]) > 0;
    }

    public function countByPost(int $postId, string $status = 'approved'): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS c FROM comments WHERE post_id = ? AND status = ?',
            [$postId, $status]
        );

        return (int) ($row['c'] ?? 0);
    }

    // плоский список -> дерево по parent_id
    private function buildTree(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $byId[(int) $row['id']] = $row;
        }

        $tree = [];
        foreach ($byId as $id => $row) {
            $parentId = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            if ($parentId && isset($byId[$parentId])) {
                $byId[$parentId]['children'][] = &$byId[$id];
            } else {
                $tree[] = &$byId[$id];
            }
        }

        return $tree;
    }
}
