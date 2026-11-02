<?php
require_once __DIR__ . '/src_database.php';

class TaskRepository
{
    private static function statusToDb(string $status): string
    {
        return ['belum' => 'Belum dimulai', 'proses' => 'Sedang dikerjakan', 'selesai' => 'Selesai'][$status] ?? 'Belum dimulai';
    }

    private static function statusFromDb(string $status): string
    {
        return ['Belum dimulai' => 'belum', 'Sedang dikerjakan' => 'proses', 'Selesai' => 'selesai'][$status] ?? 'belum';
    }

    public static function all(int $userId, ?string $status = null, ?string $q = null): array
    {
        $sql = "SELECT t.*, c.name AS kategori_nama, c.color AS kategori_warna
                FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
                WHERE t.workspace_id = (SELECT workspace_id FROM users WHERE id = :uid)";
        $params = ['uid' => $userId];
        if ($status && $status !== 'semua') {
            $sql .= ' AND t.status = :status';
            $params['status'] = self::statusToDb($status);
        }
        if ($q) {
            $sql .= ' AND (t.title LIKE :q OR t.description LIKE :q2)';
            $params['q'] = "%$q%";
            $params['q2'] = "%$q%";
        }
        $sql .= " ORDER BY FIELD(t.status,'Belum dimulai','Sedang dikerjakan','Selesai'), t.due_date ASC";
        $stmt = Database::conn()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['judul'] = $row['title'];
            $row['deskripsi'] = $row['description'];
            $row['deadline'] = $row['due_date'];
            $row['prioritas'] = strtolower($row['priority']);
            $row['status'] = self::statusFromDb($row['status']);
            $row['pinned'] = false;
        }
        return $rows;
    }

    /** Tugas aktif (belum selesai) untuk slider "Fokus hari ini", urut dari tenggat terdekat. */
    public static function focus(int $userId, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $stmt = Database::conn()->prepare("SELECT t.id, t.title, t.status, t.progress, t.due_date, c.name AS kategori_nama,
                (SELECT COUNT(*) FROM subtasks s WHERE s.task_id = t.id) AS sub_total,
                (SELECT COUNT(*) FROM subtasks s WHERE s.task_id = t.id AND s.is_completed = 1) AS sub_done
            FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
            WHERE t.workspace_id = (SELECT workspace_id FROM users WHERE id = :uid)
              AND t.status <> 'Selesai' AND t.due_date IS NOT NULL
            ORDER BY t.due_date ASC LIMIT " . $limit);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['status'] = self::statusFromDb($row['status']);
        }
        return $rows;
    }

    public static function find(int $id, int $userId): ?array
    {
        $stmt = Database::conn()->prepare("SELECT t.*, c.name AS kategori_nama FROM tasks t LEFT JOIN categories c ON c.id=t.category_id WHERE t.id=:id AND t.workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)");
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['judul'] = $row['title']; $row['deskripsi'] = $row['description'];
        $row['kategori_id'] = $row['category_id']; $row['deadline'] = $row['due_date'];
        $row['prioritas'] = strtolower($row['priority']); $row['status'] = self::statusFromDb($row['status']);
        return $row;
    }

    public static function create(int $userId, array $d): int
    {
        $sql = "INSERT INTO tasks (workspace_id, category_id, title, description, due_date, priority, status, progress, is_recurring, recurrence_rule, created_by, assigned_to)
                SELECT u.workspace_id, :cat, :title, :description, :due, :priority, :status, :progress, :recur, :rule, u.id, u.id FROM users u WHERE u.id=:uid";
        Database::conn()->prepare($sql)->execute(['cat' => $d['kategori_id'] ?: null, 'title' => $d['judul'], 'description' => $d['deskripsi'] ?: null,
            'due' => str_replace('T', ' ', $d['deadline']) . (strlen($d['deadline']) === 16 ? ':00' : ''), 'priority' => ucfirst($d['prioritas']),
            'status' => self::statusToDb($d['status']), 'progress' => (int)$d['progress'], 'recur' => !empty($d['is_recurring']) ? 1 : 0,
            'rule' => !empty($d['is_recurring']) ? ($d['recurrence_rule'] ?? 'weekly') : null, 'uid' => $userId]);
        $id = (int) Database::conn()->lastInsertId();
        self::scheduleReminder($userId, $id, $d);
        return $id;
    }

    public static function update(int $id, int $userId, array $d): void
    {
        Database::conn()->prepare("UPDATE tasks SET category_id=:cat,title=:title,description=:description,due_date=:due,priority=:priority,status=:status,progress=:progress,is_recurring=:recur,recurrence_rule=:rule,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)")
            ->execute(['cat' => $d['kategori_id'] ?: null, 'title' => $d['judul'], 'description' => $d['deskripsi'] ?: null,
                'due' => str_replace('T', ' ', $d['deadline']) . (strlen($d['deadline']) === 16 ? ':00' : ''), 'priority' => ucfirst($d['prioritas']),
                'status' => self::statusToDb($d['status']), 'progress' => (int)$d['progress'], 'recur' => !empty($d['is_recurring']) ? 1 : 0,
                'rule' => !empty($d['is_recurring']) ? ($d['recurrence_rule'] ?? 'weekly') : null, 'id' => $id, 'uid' => $userId]);
        self::scheduleReminder($userId, $id, $d);
    }

    private static function scheduleReminder(int $userId, int $taskId, array $d): void
    {
        $pdo = Database::conn();
        $pdo->prepare("DELETE FROM reminders WHERE user_id=:u AND task_id=:t AND status='Antre'")->execute(['u'=>$userId,'t'=>$taskId]);
        $minutes = max(0, (int)($d['reminder_minutes'] ?? 30));
        $due = strtotime(str_replace('T', ' ', $d['deadline']));
        if ($due === false) return;
        $at = date('Y-m-d H:i:s', $due - ($minutes * 60));
        $channels = ['Aplikasi'];
        if (!empty($d['reminder_email'])) $channels[] = 'Email';
        foreach ($channels as $channel) $pdo->prepare("INSERT INTO reminders(user_id,task_id,remind_at,channel,status) VALUES(:u,:t,:at,:c,'Antre')")->execute(['u'=>$userId,'t'=>$taskId,'at'=>$at,'c'=>$channel]);
    }

    public static function delete(int $id, int $userId): void
    {
        Database::conn()->prepare("DELETE FROM tasks WHERE id=:id AND workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)")->execute(['id'=>$id,'uid'=>$userId]);
    }

    public static function toggleStatus(int $id, int $userId): void
    {
        Database::conn()->prepare("UPDATE tasks SET progress=IF(status='Selesai',0,100), status=IF(status='Selesai','Belum dimulai','Selesai') WHERE id=:id AND workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)")->execute(['id'=>$id,'uid'=>$userId]);
    }

    public static function stats(int $userId): array
    {
        $stmt = Database::conn()->prepare("SELECT COUNT(*) total,SUM(status='Selesai') selesai,SUM(status='Sedang dikerjakan') proses,SUM(status='Belum dimulai') belum,SUM(status<>'Selesai' AND due_date<NOW()) terlambat FROM tasks WHERE workspace_id=(SELECT workspace_id FROM users WHERE id=:uid)");
        $stmt->execute(['uid'=>$userId]);
        return $stmt->fetch() ?: ['total'=>0,'selesai'=>0,'proses'=>0,'belum'=>0,'terlambat'=>0];
    }
}
