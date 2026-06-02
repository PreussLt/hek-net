<?php
class Database {
    private $pdo;

    public function __construct() {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'hek_net';
        $user = getenv('DB_USER') ?: 'hek_user';
        $pass = getenv('DB_PASS') ?: 'hek_pass';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
            $this->checkInit();
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }

    private function checkInit() {
        // Standard-Status anlegen, falls die Tabelle noch leer ist
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM statuses");
        if ($stmt->fetchColumn() == 0) {
            $this->pdo->exec("
                INSERT INTO statuses (id, name, color) VALUES 
                (1, 'Online', 'success'),
                (2, 'Offline', 'danger'),
                (3, 'Wartung', 'warning'),
                (4, 'Unbekannt', 'muted');
            ");
        }
    }

    public function getRecords() {
        // Lese alle Hardware-Geräte inkl. Icon-URL und Parent-Info
        $stmt = $this->pdo->query("
            SELECT h.*, mi.url as icon_url, p.Name as parent_name
            FROM hardware h 
            LEFT JOIN master_icons mi ON h.icon_id = mi.id 
            LEFT JOIN hardware p ON h.parent_id = p.id
            ORDER BY h.id DESC
        ");
        $hardwareList = $stmt->fetchAll();

        // Lese Services und Tags pro Gerät aus
        $stmtSvc = $this->pdo->prepare("
            SELECT s.name, s.port, s.protocol, st.name as status_name, st.color as status_color 
            FROM services s
            LEFT JOIN statuses st ON s.status_id = st.id
            WHERE s.hardware_id = ?
        ");

        $stmtTags = $this->pdo->prepare("
            SELECT t.* FROM tags t
            JOIN hardware_tags ht ON t.id = ht.tag_id
            WHERE ht.hardware_id = ?
        ");

        foreach ($hardwareList as &$hw) {
            $stmtSvc->execute([$hw['id']]);
            $hw['ServicesList'] = $stmtSvc->fetchAll();

            $stmtTags->execute([$hw['id']]);
            $hw['TagsList'] = $stmtTags->fetchAll();
        }
        
        return $hardwareList;
    }

    public function createRecord($data) {
        $this->pdo->beginTransaction();
        try {
            $sql = "INSERT INTO hardware (Name, IP, Hersteller, icon_id, parent_id, notes) VALUES (:name, :ip, :hersteller, :icon_id, :parent_id, :notes)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['Name'] ?? '',
                ':ip' => $data['IP'] ?? '',
                ':hersteller' => $data['Hersteller'] ?? '',
                ':icon_id' => $data['icon_id'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':notes' => $data['notes'] ?? ''
            ]);
            
            $hardwareId = $this->pdo->lastInsertId();

            if (!empty($data['Services']) && is_array($data['Services'])) {
                $sqlSvc = "INSERT INTO services (hardware_id, status_id, name, port) VALUES (?, 4, ?, ?)";
                $stmtSvc = $this->pdo->prepare($sqlSvc);
                foreach ($data['Services'] as $svc) {
                    $svcName = trim($svc['name'] ?? '');
                    $svcPort = !empty($svc['port']) ? (int)$svc['port'] : null;
                    if (!empty($svcName)) {
                        $stmtSvc->execute([$hardwareId, $svcName, $svcPort]);
                    }
                }
            }

            if (!empty($data['Tags']) && is_array($data['Tags'])) {
                $sqlTag = "INSERT INTO hardware_tags (hardware_id, tag_id) VALUES (?, ?)";
                $stmtTag = $this->pdo->prepare($sqlTag);
                foreach ($data['Tags'] as $tagId) {
                    $stmtTag->execute([$hardwareId, $tagId]);
                }
            }

            $this->pdo->commit();
            return $hardwareId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function updateNotes($id, $notes) {
        $stmt = $this->pdo->prepare("UPDATE hardware SET notes = ? WHERE id = ?");
        return $stmt->execute([$notes, $id]);
    }

    public function addHardwareFile($hwId, $name, $url, $type) {
        $stmt = $this->pdo->prepare("INSERT INTO hardware_files (hardware_id, name, url, file_type) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$hwId, $name, $url, $type]);
    }

    public function getHardwareFiles($hwId) {
        $stmt = $this->pdo->prepare("SELECT * FROM hardware_files WHERE hardware_id = ? ORDER BY created_at DESC");
        $stmt->execute([$hwId]);
        return $stmt->fetchAll();
    }

    // === Stammdaten Methoden ===

    public function getMasterIcons() {
        return $this->pdo->query("SELECT * FROM master_icons ORDER BY name ASC")->fetchAll();
    }

    public function addMasterIcon($name, $url, $description = '') {
        $stmt = $this->pdo->prepare("INSERT INTO master_icons (name, url, description) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $url, $description]);
    }

    public function getTags() {
        return $this->pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();
    }

    public function addTag($name, $color) {
        $stmt = $this->pdo->prepare("INSERT INTO tags (name, color) VALUES (?, ?)");
        return $stmt->execute([$name, $color]);
    }

    public function getIconUrl($id) {
        $stmt = $this->pdo->prepare("SELECT url FROM master_icons WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $row['url'] : null;
    }
}
?>
