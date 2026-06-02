<?php
require_once 'lib/Database.php';
require_once 'lib/S3Client.php';
$db = new Database();
$s3 = new S3Client();
$s3->createBucket();

// Die Database-Klasse sät nun automatisch die Status-Werte bei der Verbindung zu MariaDB

// Beispiel-Tags (falls noch nicht vorhanden)
try {
    $db->addTag('Serverraum', '#ef4444');
    $db->addTag('Produktiv', '#10b981');
    $db->addTag('Wichtig', '#f59e0b');
} catch (Exception $e) {
    // Ignorieren falls schon da
}

echo "Initialisierung für MariaDB abgeschlossen.\n";

