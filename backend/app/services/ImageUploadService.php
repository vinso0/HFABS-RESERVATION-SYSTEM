<?php

class ImageUploadService
{
    private $uploadDir;
    private $uploadWebPath;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private $maxFileSize  = 5 * 1024 * 1024; // 5MB

    public function __construct()
    {
        // Matches your existing uploads path: backend/public/uploads/
        $this->uploadDir     = __DIR__ . '/../../public/uploads/services/';
        $this->uploadWebPath = 'uploads/services/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Save a Base64-encoded image string to disk.
     * Returns ['success' => bool, 'path' => string|null, 'error' => string|null]
     *
     * @param string      $base64Data  Full data URI or raw base64 string
     * @param string|null $oldPath     Existing stored path to delete on replace
     */
    public function saveBase64Image(string $base64Data, ?string $oldPath = null): array
    {
        // Strip data URI prefix if present: "data:image/png;base64,..."
        if (strpos($base64Data, 'data:') === 0) {
            if (!preg_match('/^data:(image\/\w+);base64,(.+)$/s', $base64Data, $matches)) {
                return ['success' => false, 'path' => null, 'error' => 'Invalid image data format.'];
            }
            $mimeType  = $matches[1];
            $base64Raw = $matches[2];
        } else {
            // Plain base64 — detect type from decoded bytes
            $base64Raw = $base64Data;
            $decoded   = base64_decode($base64Raw, true);
            if ($decoded === false) {
                return ['success' => false, 'path' => null, 'error' => 'Failed to decode image data.'];
            }
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $decoded);
            finfo_close($finfo);
        }

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'path' => null, 'error' => 'Invalid image type. Allowed: JPEG, PNG, WebP, GIF.'];
        }

        $decoded = base64_decode($base64Raw, true);
        if ($decoded === false) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to decode image data.'];
        }

        if (strlen($decoded) > $this->maxFileSize) {
            return ['success' => false, 'path' => null, 'error' => 'Image exceeds 5MB size limit.'];
        }

        // Build extension from MIME
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $ext      = $extMap[$mimeType] ?? 'jpg';
        $filename = 'service_' . uniqid('', true) . '.' . $ext;
        $fullPath = $this->uploadDir . $filename;

        if (file_put_contents($fullPath, $decoded) === false) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to save image file.'];
        }

        // Delete old image if replacing
        $this->deleteImage($oldPath);

        return ['success' => true, 'path' => $this->uploadWebPath . $filename, 'error' => null];
    }

    /**
     * Delete an image by its stored relative path (e.g. "uploads/services/service_xxx.jpg")
     */
    public function deleteImage(?string $path): void
    {
        if (!$path) return;
        // Resolve from backend/public/ root
        $fullPath = __DIR__ . '/../../public/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}