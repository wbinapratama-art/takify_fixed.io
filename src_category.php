<?php
require_once __DIR__ . '/src_database.php';

class CategoryRepository
{
    public static function all(int $userId): array
    {
        $stmt = Database::conn()->prepare('SELECT c.id,c.name AS nama,c.color AS warna,c.description FROM categories c WHERE c.workspace_id=(SELECT workspace_id FROM users WHERE id=:uid) ORDER BY c.name');
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();
        $palette = ['bg-purple-500' => '#8B5CF6', 'bg-red-500' => '#EF4444', 'bg-green-500' => '#22C55E', 'bg-blue-500' => '#3B82F6'];
        foreach ($rows as &$row) $row['warna'] = $palette[$row['warna']] ?? $row['warna'];
        return $rows;
    }

    public static function create(int $userId, array $d): void
    {
        $stmt = Database::conn()->prepare('INSERT INTO categories (workspace_id,name,color,created_by) SELECT workspace_id,:name,:color,id FROM users WHERE id=:uid');
        $stmt->execute(['name' => $d['nama'], 'color' => $d['warna'] ?? '#3B82F6', 'uid' => $userId]);
    }

    public static function delete(int $id, int $userId): void
    {
        Database::conn()->prepare('DELETE FROM categories WHERE id=:id AND workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)')->execute(['id' => $id, 'uid' => $userId]);
    }
}
