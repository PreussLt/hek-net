<?php
require_once 'lib/Database.php';
require_once 'lib/S3Client.php';

$db = new Database();
$s3 = new S3Client();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$records = $db->getRecords();
$device = null;

foreach ($records as $r) {
    if ($r['id'] === $id) {
        $device = $r;
        break;
    }
}

if (!$device) {
    header("Location: hardware.php");
    exit;
}

// === POST Handlers ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Notizen speichern
    if (isset($_POST['action']) && $_POST['action'] === 'save_notes') {
        $db->updateNotes($id, $_POST['notes']);
        header("Location: details.php?id=$id");
        exit;
    }

    // 2. Datei hochladen
    if (isset($_POST['action']) && $_POST['action'] === 'upload_file') {
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $s3->createBucket();
            $tempPath = $_FILES['file']['tmp_name'];
            $fileName = 'doc_' . time() . '_' . basename($_FILES['file']['name']);
            $fileType = $_FILES['file']['type'];
            $url = $s3->uploadFile($fileName, $tempPath, $fileType);
            
            if ($url) {
                $db->addHardwareFile($id, basename($_FILES['file']['name']), $url, $fileType);
            }
        }
        header("Location: details.php?id=$id");
        exit;
    }
}

$files = $db->getHardwareFiles($id);

include 'header.php';
?>

<div class="section-header" style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px;">
    <a href="hardware.php" style="text-decoration: none; color: var(--text-muted);">&larr; Zurück</a>
    <h2>Dokumentation: <?php echo htmlspecialchars($device['Name']); ?></h2>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
    <!-- Linke Spalte: Notizen & Details -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- Geräte Info Card -->
        <div class="glass-card" style="padding: 24px; display: flex; gap: 20px; align-items: center;">
            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.5); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--glass-border);">
                <?php if ($device['icon_id']): ?>
                    <img src="icon.php?id=<?php echo $device['icon_id']; ?>" style="width: 60px; height: 60px; object-fit: contain;">
                <?php else: ?>
                    <span style="font-size: 24px; color: var(--text-muted);">?</span>
                <?php endif; ?>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 24px;"><?php echo htmlspecialchars($device['Name']); ?></h3>
                <p style="margin: 5px 0; color: var(--text-muted);">IP: <?php echo htmlspecialchars($device['IP']); ?> | Hersteller: <?php echo htmlspecialchars($device['Hersteller']); ?></p>
                <div style="display: flex; gap: 5px; margin-top: 10px;">
                    <?php foreach ($device['TagsList'] as $tag): ?>
                        <span class="badge" style="background: <?php echo $tag['color']; ?>22; color: <?php echo $tag['color']; ?>; border: 1px solid <?php echo $tag['color']; ?>44;">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Notizen Card -->
        <div class="glass-card" style="padding: 24px;">
            <h3 style="margin-top: 0; margin-bottom: 15px;">Geräte-Notizen</h3>
            <form method="POST">
                <input type="hidden" name="action" value="save_notes">
                <textarea name="notes" placeholder="Konfiguration, Zugangsdaten (verschlüsselt empfohlen), Wartungshistorie..." 
                          style="width: 100%; min-height: 300px; padding: 15px; border-radius: 12px; border: 1px solid var(--glass-border); background: rgba(255,255,255,0.8); font-family: 'Inter', sans-serif; resize: vertical;"><?php echo htmlspecialchars($device['notes'] ?? ''); ?></textarea>
                <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Notizen speichern</button>
            </form>
        </div>
    </div>

    <!-- Rechte Spalte: Dateien / Anhänge -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <div class="glass-card" style="padding: 24px;">
            <h3 style="margin-top: 0; margin-bottom: 15px;">Dokumente & Anhänge</h3>
            
            <form method="POST" enctype="multipart/form-data" style="margin-bottom: 25px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px; border: 1px dashed var(--glass-border);">
                <input type="hidden" name="action" value="upload_file">
                <input type="file" name="file" required style="width: 100%; margin-bottom: 10px; font-size: 12px;">
                <button type="submit" class="btn btn-secondary" style="width: 100%;">Datei hochladen</button>
            </form>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php if (empty($files)): ?>
                    <p style="text-align: center; color: var(--text-muted); font-size: 14px; padding: 20px;">Keine Dokumente vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.1); padding: 10px 15px; border-radius: 8px; border: 1px solid var(--glass-border);">
                            <div style="overflow: hidden;">
                                <div style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($file['name']); ?></div>
                                <div style="font-size: 11px; color: var(--text-muted);"><?php echo date('d.m.Y H:i', strtotime($file['created_at'])); ?></div>
                            </div>
                            <a href="<?php echo htmlspecialchars($file['url']); ?>" target="_blank" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px;">Öffnen</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card" style="padding: 24px;">
            <h3 style="margin-top: 0; margin-bottom: 15px;">Verbindung</h3>
            <?php if ($device['parent_id']): ?>
                <p style="font-size: 14px;">Dieses Gerät ist verbunden mit:</p>
                <div style="display: flex; align-items: center; gap: 10px; background: rgba(37, 99, 235, 0.05); padding: 10px; border-radius: 8px; border: 1px solid rgba(37, 99, 235, 0.1);">
                    <span style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($device['parent_name']); ?></span>
                    <a href="details.php?id=<?php echo $device['parent_id']; ?>" style="font-size: 11px; color: var(--text-muted);">Ansehen &rarr;</a>
                </div>
            <?php else: ?>
                <p style="font-size: 14px; color: var(--text-muted);">Kein übergeordnetes Gerät (Root-Node).</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
