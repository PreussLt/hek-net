<?php
class Database {
    private $pdo;

    public function __construct() {
        // Der neue Datenbank-Ordner ist nun über Docker unter /var/www/database gemountet
        $dataDir = '/var/www/database';
        
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0777, true);
        }

        $dbFile = $dataDir . '/database.sqlite';
        $isNew = !file_exists($dbFile);

        try {
            $this->pdo = new PDO('sqlite:' . $dbFile);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Wichtig für ON DELETE CASCADE und Foreign Keys
            $this->pdo->exec('PRAGMA foreign_keys = ON;');

            if ($isNew) {
                $this->initDb();
            }
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }

    private function initDb() {
        // Das Schema wird nun direkt aus der SQL-Struktur-Datei geladen
        $schemaFile = '/var/www/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $this->pdo->exec($sql);

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
    }

    public function getRecords() {
        // Lese alle Hardware-Geräte inkl. Icon-URL
        $stmt = $this->pdo->query("
            SELECT h.*, mi.url as icon_url 
            FROM hardware h 
            LEFT JOIN master_icons mi ON h.icon_id = mi.id 
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
            // Services
            $stmtSvc->execute([$hw['id']]);
            $hw['ServicesList'] = $stmtSvc->fetchAll();

            // Tags
            $stmtTags->execute([$hw['id']]);
            $hw['TagsList'] = $stmtTags->fetchAll();
        }
        
        return $hardwareList;
    }

    public function createRecord($data) {
        $this->pdo->beginTransaction();
        try {
            // Gerät anlegen
            $sql = "INSERT INTO hardware (Name, IP, Hersteller, icon_id) VALUES (:name, :ip, :hersteller, :icon_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['Name'] ?? '',
                ':ip' => $data['IP'] ?? '',
                ':hersteller' => $data['Hersteller'] ?? '',
                ':icon_id' => $data['icon_id'] ?? null
            ]);
            
            $hardwareId = $this->pdo->lastInsertId();

            // Services speichern
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

            // Tags speichern
            if (!empty($data['Tags']) && is_array($data['Tags'])) {
                $sqlTag = "INSERT INTO hardware_tags (hardware_id, tag_id) VALUES (?, ?)";
                $stmtTag = $this->pdo->prepare($sqlTag);
                foreach ($data['Tags'] as $tagId) {
                    $stmtTag->execute([$hardwareId, $tagId]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
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
