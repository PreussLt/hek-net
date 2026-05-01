<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client as AwsS3Client;
use Aws\Exception\AwsException;

class S3Client {
    private $client;
    private $bucket;

    public function __construct() {
        $endpoint = getenv('S3_ENDPOINT') ?: $_ENV['S3_ENDPOINT'] ?? 'http://rustfs:9000';
        $accessKey = getenv('S3_ACCESS_KEY') ?: $_ENV['S3_ACCESS_KEY'] ?? 'rustfsadmin';
        $secretKey = getenv('S3_SECRET_KEY') ?: $_ENV['S3_SECRET_KEY'] ?? 'rustfsadmin';
        $this->bucket = getenv('S3_BUCKET') ?: $_ENV['S3_BUCKET'] ?? 'hek-net-icons';

        $this->client = new AwsS3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => $accessKey,
                'secret' => $secretKey,
            ],
        ]);
    }

    public function uploadFile($fileName, $filePath, $fileType) {
        try {
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $fileName,
                'SourceFile' => $filePath,
                'ContentType' => $fileType,
                'ACL'        => 'public-read' // Versuche öffentlichen Zugriff direkt beim Upload
            ]);

            // Externe URL generieren (localhost:9100 für den Browser)
            return "http://localhost:9100/" . $this->bucket . "/" . $fileName;
        } catch (AwsException $e) {
            error_log("S3 Upload Fehler: " . $e->getMessage());
            return false;
        }
    }

    public function createBucket() {
        try {
            if (!$this->client->doesBucketExist($this->bucket)) {
                $this->client->createBucket(['Bucket' => $this->bucket]);
                
                // Bucket-Policy für öffentlichen Lesezugriff setzen
                $policy = [
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Effect' => 'Allow',
                            'Principal' => '*',
                            'Action' => ['s3:GetObject'],
                            'Resource' => ["arn:aws:s3:::{$this->bucket}/*"]
                        ]
                    ]
                ];
                
                $this->client->putBucketPolicy([
                    'Bucket' => $this->bucket,
                    'Policy' => json_encode($policy),
                ]);
            }
        } catch (AwsException $e) {
            error_log("S3 Bucket Fehler: " . $e->getMessage());
        }
    }

    public function getObject($key) {
        try {
            return $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $key
            ]);
        } catch (AwsException $e) {
            error_log("S3 GetObject Fehler: " . $e->getMessage());
            throw $e;
        }
    }
}
