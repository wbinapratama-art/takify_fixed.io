<?php
require_once __DIR__ . '/src_database.php';

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return ($i === 0 ? (int) $n : round($n, 2)) . ' ' . $units[$i];
}

/**
 * Buat dokumen Word (.doc) berisi data workspace.
 * Return array: ['filename' => ..., 'size' => ..., 'path' => ..., 'bytes' => ...]
 */
function create_backup(int $workspaceId): array
{
    $pdo = Database::conn();
    $wid = (int) $workspaceId;

    // Ambil nama workspace
    $wsStmt = $pdo->prepare("SELECT name FROM workspaces WHERE id = ?");
    $wsStmt->execute([$wid]);
    $wsName = $wsStmt->fetchColumn() ?: "Workspace $wid";

    // HTML dasar untuk Word
    $html = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
    $html .= "<head><meta charset='utf-8'><title>Backup Data</title>";
    $html .= "<style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        h1, h2 { color: #4a44d1; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; font-size: 10pt; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f0efff; }
    </style></head><body>";

    $html .= "<h1>Laporan Data Takify</h1>";
    $html .= "<p><b>Workspace:</b> " . htmlspecialchars($wsName) . "<br>";
    $html .= "<b>Tanggal Cetak:</b> " . date('d F Y, H:i') . " WIB</p><hr>";

    // Daftar tabel yang akan dimasukkan ke Word
    $tables = [
        'tasks' => [
            'title' => 'Daftar Tugas',
            'where' => "workspace_id = {$wid}",
            'columns' => ['id', 'title', 'description', 'status', 'priority', 'progress', 'due_date']
        ],
        'categories' => [
            'title' => 'Kategori',
            'where' => "workspace_id = {$wid}",
            'columns' => ['id', 'name', 'description']
        ],
        'templates' => [
            'title' => 'Template Tugas',
            'where' => "workspace_id = {$wid}",
            'columns' => ['id', 'title', 'description', 'default_priority']
        ],
        'feedbacks' => [
            'title' => 'Masukan / Feedback',
            'where' => "workspace_id = {$wid}",
            'columns' => ['id', 'subject', 'type', 'content', 'status', 'admin_response', 'created_at']
        ],
        'users' => [
            'title' => 'Anggota Tim',
            'where' => "workspace_id = {$wid}",
            'columns' => ['id', 'name', 'email', 'role', 'status', 'last_active_at']
        ]
    ];

    foreach ($tables as $table => $info) {
        try {
            $html .= "<h2>" . htmlspecialchars($info['title']) . "</h2>";

            $rows = $pdo->query("SELECT * FROM `{$table}` WHERE {$info['where']}");
            $data = $rows->fetchAll();

            if (count($data) === 0) {
                $html .= "<p><i>Tidak ada data.</i></p>";
                continue;
            }

            $html .= "<table><thead><tr>";
            foreach ($info['columns'] as $col) {
                $html .= "<th>" . htmlspecialchars(ucwords(str_replace('_', ' ', $col))) . "</th>";
            }
            $html .= "</tr></thead><tbody>";

            foreach ($data as $row) {
                $html .= "<tr>";
                foreach ($info['columns'] as $col) {
                    $val = $row[$col] ?? '';
                    // Format tanggal jika kolom berakhiran _at atau date
                    if (($col === 'due_date' || $col === 'created_at' || $col === 'last_active_at') && !empty($val)) {
                        $val = date('d M Y, H:i', strtotime($val));
                    }
                    $html .= "<td>" . nl2br(htmlspecialchars((string) $val)) . "</td>";
                }
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";

        } catch (Throwable $e) {
            $html .= "<p style='color:red;'>Gagal memuat tabel {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    $html .= "</body></html>";

    $dir = __DIR__ . '/backups';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('Folder backups tidak dapat dibuat.');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('Folder backups tidak writable.');
    }

    // Ekstensi .doc agar dikenali Microsoft Word
    $filename = 'backup_word_ws' . $wid . '_' . date('Ymd_His') . '.doc';
    $path = $dir . '/' . $filename;

    if (file_put_contents($path, $html) === false) {
        throw new RuntimeException('Gagal menulis file backup.');
    }

    return [
        'filename' => $filename,
        'size' => format_bytes(strlen($html)),
        'path' => $path,
        'bytes' => strlen($html),
    ];
}