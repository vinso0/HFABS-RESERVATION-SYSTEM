<?php
class UploadController extends Controller
{
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private $maxFileSize  = 5 * 1024 * 1024; // 5MB

    public function __construct()
    {
        // Absolute path to uploads/services inside backend
        $this->uploadDir = dirname(__DIR__, 2) . '/uploads/services/';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Handles a single service image upload.
     * Call this from within other controllers — not as a standalone endpoint.
     *
     * @param  array       $file        $_FILES['service_image']
     * @param  string|null $oldPath     Existing path to delete on replace
     * @return array ['success'=>bool, 'path'=>string|null, 'error'=>string|null]
     */
    public function uploadServiceImage(array $file, ?string $oldPath = null): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'path' => null, 'error' => 'Upload error code: ' . $file['error']];
        }

        if ($file['size'] > $this->maxFileSize) {
            return ['success' => false, 'path' => null, 'error' => 'File size exceeds 5MB limit'];
        }

        // Validate MIME via finfo (not trusting browser-sent type)
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['success' => false, 'path' => null, 'error' => 'Invalid file type. Only JPEG, PNG, WebP, and GIF are allowed.'];
        }

        // Build a unique filename
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'service_' . uniqid('', true) . '.' . strtolower($ext);
        $destPath = $this->uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'path' => null, 'error' => 'Failed to move uploaded file'];
        }

        // Delete the old image if replacing
        if ($oldPath) {
            $oldFull = dirname(__DIR__, 2) . '/' . ltrim($oldPath, '/');
            if (file_exists($oldFull)) {
                @unlink($oldFull);
            }
        }

        // Return a web-accessible relative path
        $relativePath = 'uploads/services/' . $filename;
        return ['success' => true, 'path' => $relativePath, 'error' => null];
    }

    /**
     * Delete a service image by its stored relative path.
     */
    public function deleteServiceImage(?string $path): void
    {
        if (!$path) return;
        $fullPath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }
}