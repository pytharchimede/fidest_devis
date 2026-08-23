<?php
declare(strict_types=1);

namespace App\Application\Quote;

use RuntimeException;

final class LogoUploader
{
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly string $directory) {}

    public function upload(?array $file): string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le logo n’a pas pu être transféré.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!isset(self::ALLOWED_TYPES[$mime])) {
            throw new RuntimeException('Format de logo non autorisé.');
        }

        $filename = 'logo_' . bin2hex(random_bytes(8)) . '.' . self::ALLOWED_TYPES[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], $this->directory . '/' . $filename)) {
            throw new RuntimeException('Impossible d’enregistrer le logo.');
        }
        return $filename;
    }
}
