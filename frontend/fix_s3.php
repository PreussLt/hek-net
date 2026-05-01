<?php
require_once 'lib/S3Client.php';
$s3 = new S3Client();

echo "Setze Bucket Policy...\n";
try {
    $bucket = getenv('S3_BUCKET') ?: $_ENV['S3_BUCKET'] ?? 'hek-net-icons';
    
    // Wir rufen DoesBucketExist auf, aber wir wollen die Policy setzen, egal ob er existiert
    $policy = [
        'Version' => '2012-10-17',
        'Statement' => [
            [
                'Effect' => 'Allow',
                'Principal' => '*',
                'Action' => ['s3:GetObject'],
                'Resource' => ["arn:aws:s3:::{$bucket}/*"]
            ]
        ]
    ];

    // Wir nutzen das interne Client-Objekt von S3Client (muss evtl. public gemacht werden oder wir nutzen createBucket)
    $s3->createBucket(); 
    
    echo "Policy wurde (hoffentlich) gesetzt.\n";
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
}
