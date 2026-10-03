<?php
final class MediaService
{
    private const MAX_BYTES = 3145728; // 3 MiB
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function store(array $file, int $userId): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('The image could not be uploaded. Please choose it again.');
        }
        if ((int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > self::MAX_BYTES) {
            throw new InvalidArgumentException('Images must be smaller than 3 MB.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('The uploaded file could not be verified.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            throw new InvalidArgumentException('Use a JPG, PNG, or WebP image. SVG and other file types are not accepted.');
        }
        $dimensions = @getimagesize($tmp);
        if (!$dimensions || $dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 3000 || $dimensions[1] > 3000) {
            throw new InvalidArgumentException('The image must be valid and no larger than 3,000 × 3,000 pixels.');
        }
        $data = file_get_contents($tmp);
        if ($data === false) throw new InvalidArgumentException('The image could not be read.');

        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? 'image')));
        $original = mb_substr($original, 0, 190, 'UTF-8');
        $extension = self::ALLOWED[$mime];
        $stored = bin2hex(random_bytes(18)) . '.' . $extension;
        $hash = hash('sha256', $data);
        $stmt = db()->prepare('INSERT INTO media (original_name, stored_name, mime_type, extension, file_size, file_hash, data, uploaded_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $size = strlen($data);
        $nullBlob = null;
        $stmt->bind_param('ssssisbi', $original, $stored, $mime, $extension, $size, $hash, $nullBlob, $userId);
        $stmt->send_long_data(6, $data);
        $stmt->execute();
        $id = (int) $stmt->insert_id;
        $stmt->close();
        audit_log('Uploaded media', 'media', $id, ['name' => $original, 'mime' => $mime, 'size' => $size]);
        return $id;
    }

    public static function stream(int $id): void
    {
        $row = db_one('SELECT m.id, m.mime_type, m.file_size, m.file_hash, m.data FROM media m WHERE m.id=? LIMIT 1', 'i', [$id]);
        if (!$row) throw new HttpException(404, 'That image could not be found.');
        header('Content-Type: ' . $row['mime_type']);
        header('Content-Length: ' . (int) $row['file_size']);
        header('Cache-Control: private, max-age=86400');
        header('ETag: "' . $row['file_hash'] . '"');
        echo $row['data'];
        exit;
    }
}
