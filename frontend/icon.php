<?php
require_once 'lib/S3Client.php';
require_once 'lib/Database.php';

if (!isset($_GET['id'])) {
    die("Missing ID");
}

$id = (int)$_GET['id'];
$db = new Database();

// Wir brauchen die URL aus der master_icons Tabelle über die neue Methode
$url = $db->getIconUrl($id);

if (!$url) {
    die("Icon not found");
}

// Extrahiere den Dateinamen aus der URL (z.B. seed_router.png)
$urlParts = explode('/', $url);
$fileName = end($urlParts);

$s3 = new S3Client();
$bucket = getenv('S3_BUCKET') ?: $_ENV['S3_BUCKET'] ?? 'hek-net-icons';

try {
    // Hole das Objekt von S3
    $result = $s3->getObject($fileName); // Wir müssen diese Methode noch hinzufügen
    
    header("Content-Type: " . $result['ContentType']);
    echo $result['Body'];
} catch (Exception $e) {
    header("HTTP/1.1 404 Not Found");
    echo "Error: " . $e->getMessage();
}
