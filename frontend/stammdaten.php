<?php 
require_once 'lib/Database.php';
require_once 'lib/S3Client.php';

$db = new Database();
$s3 = new S3Client();

// === POST Handlers ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Icon Upload
    if (isset($_POST['action']) && $_POST['action'] === 'add_icon') {
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $s3->createBucket();
            $tempPath = $_FILES['icon']['tmp_name'];
            $fileName = time() . '_' . basename($_FILES['icon']['name']);
            $fileType = $_FILES['icon']['type'];
            $iconUrl = $s3->uploadFile($fileName, $tempPath, $fileType);
            
            if ($iconUrl) {
                $db->addMasterIcon($_POST['name'], $iconUrl, $_POST['description']);
            }
        }
    }
    
    // 2. Tag Erstellung
    if (isset($_POST['action']) && $_POST['action'] === 'add_tag') {
        $db->addTag($_POST['name'], $_POST['color']);
    }
    
    header("Location: stammdaten.php");
    exit;
}

$icons = $db->getMasterIcons();
$tags = $db->getTags();

include 'header.php'; 
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>Stammdaten Verwaltung</h2>
    <p style="color: var(--text-muted);">Verwalte globale Icons und Tags für deine Hardware.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- ICON VERWALTUNG -->
    <div class="glass-card" style="padding: 24px;">
        <h3 style="margin-top: 0;">Globale Icons</h3>
        <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px;">
            <input type="hidden" name="action" value="add_icon">
            <div style="display: flex; gap: 10px;">
                <input type="text" name="name" placeholder="Icon Name (z.B. Router)" required style="flex: 2; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border);">
                <input type="file" name="icon" accept="image/*" required style="flex: 1; font-size: 12px;">
            </div>
            <textarea name="description" placeholder="Beschreibung (optional)" style="padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border); min-height: 60px;"></textarea>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Icon hochladen</button>
        </form>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px;">
            <?php foreach ($icons as $icon): ?>
                <div style="text-align: center; background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px; border: 1px solid var(--glass-border);">
                    <img src="icon.php?id=<?php echo $icon['id']; ?>" style="width: 40px; height: 40px; object-fit: contain; margin-bottom: 5px;">
                    <div style="font-size: 11px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($icon['name']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAG VERWALTUNG -->
    <div class="glass-card" style="padding: 24px;">
        <h3 style="margin-top: 0;">Hardware Tags</h3>
        <form method="POST" style="display: flex; gap: 10px; margin-bottom: 30px; background: rgba(255,255,255,0.05); padding: 15px; border-radius: 12px;">
            <input type="hidden" name="action" value="add_tag">
            <input type="text" name="name" placeholder="Tag Name (z.B. Serverraum)" required style="flex: 2; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border);">
            <select name="color" style="flex: 1; padding: 10px; border-radius: 8px; border: 1px solid var(--glass-border);">
                <option value="#3b82f6">Blau</option>
                <option value="#10b981">Grün</option>
                <option value="#ef4444">Rot</option>
                <option value="#f59e0b">Orange</option>
                <option value="#8b5cf6">Lila</option>
                <option value="#6b7280">Grau</option>
            </select>
            <button type="submit" class="btn btn-primary">Tag erstellen</button>
        </form>

        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php foreach ($tags as $tag): ?>
                <span class="badge" style="background: <?php echo htmlspecialchars($tag['color']); ?>22; color: <?php echo htmlspecialchars($tag['color']); ?>; border: 1px solid <?php echo htmlspecialchars($tag['color']); ?>44; padding: 8px 12px; font-size: 13px;">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
