<?php
/**
 * File Upload Handling
 * Validates, stores, and manages uploaded files
 */

// Validate uploaded files
if (!function_exists('upload_validate')) {
    function upload_validate(array $files): array
    {
        $config = $GLOBALS['config']['upload'] ?? [];
        $maxSize = $config['max_size'] ?? 10 * 1024 * 1024;
        $allowedTypes = $config['allowed_types'] ?? [];
        $allowedExtensions = $config['allowed_extensions'] ?? [];

        $errors = [];
        $validFiles = [];

        foreach ($files['name'] as $index => $name) {
            $error = $files['error'][$index];
            $tmpName = $files['tmp_name'][$index];
            $size = $files['size'][$index];

            // Check upload error
            if ($error !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => t('upload.error.ini_size'),
                    UPLOAD_ERR_FORM_SIZE => t('upload.error.form_size'),
                    UPLOAD_ERR_PARTIAL => t('upload.error.partial'),
                    UPLOAD_ERR_NO_FILE => t('upload.error.no_file'),
                    UPLOAD_ERR_NO_TMP_DIR => t('upload.error.no_tmp_dir'),
                    UPLOAD_ERR_CANT_WRITE => t('upload.error.cant_write'),
                    UPLOAD_ERR_EXTENSION => t('upload.error.extension'),
                ];
                $errors[] = $errorMessages[$error] ?? t('upload.error.unknown');
                continue;
            }

            // Check file size
            if ($size > $maxSize) {
                $errors[] = t('upload.error.too_large', ['name' => $name, 'max' => format_file_size($maxSize)]);
                continue;
            }

            // Check extension
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions, true)) {
                $errors[] = t('upload.error.invalid_type', ['name' => $name, 'allowed' => implode(', ', $allowedExtensions)]);
                continue;
            }

            // Check MIME type using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpName);

            // Some platforms report Office Open XML files as application/zip
            // and legacy binary Office files as CDFV2; accept those variants.
            $mimeAllowed = in_array($mime, $allowedTypes, true);
            if (!$mimeAllowed && in_array($ext, ['docx', 'pptx', 'xlsx'], true) && in_array($mime, ['application/zip', 'application/x-zip-compressed'], true)) {
                $mimeAllowed = true;
            }
            if (!$mimeAllowed && in_array($ext, ['doc', 'ppt', 'xls'], true) && in_array($mime, ['application/CDFV2', 'application/x-ole-storage'], true)) {
                $mimeAllowed = true;
            }

            if (!$mimeAllowed) {
                $errors[] = t('upload.error.invalid_mime', ['name' => $name, 'mime' => $mime]);
                continue;
            }

            $validFiles[] = [
                'index' => $index,
                'original_name' => $name,
                'tmp_name' => $tmpName,
                'size' => $size,
                'mime' => $mime,
                'extension' => $ext,
            ];
        }

        return [
            'valid' => $validFiles,
            'errors' => $errors,
        ];
    }
}

// Store uploaded files
if (!function_exists('upload_store')) {
    function upload_store(array $validFiles, int $itemId): array
    {
        $uploadPath = $GLOBALS['config']['upload']['path'] ?? __DIR__ . '/../storage/uploads';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $storedFiles = [];
        $pdo = $GLOBALS['pdo'] ?? null;

        foreach ($validFiles as $file) {
            // Generate secure stored name
            $storedName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
            $destPath = $uploadPath . '/' . $storedName;

            // Move file
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                error_log("Failed to move uploaded file: {$file['original_name']}");
                continue;
            }

            // Set restrictive permissions
            chmod($destPath, 0640);

            // Insert into database
            $fileId = db_insert('files', [
                'item_id' => $itemId,
                'original_name' => $file['original_name'],
                'stored_name' => $storedName,
                'mime' => $file['mime'],
                'size' => $file['size'],
                'created_at' => utc_now(),
            ]);

            $storedFiles[] = [
                'id' => $fileId,
                'original_name' => $file['original_name'],
                'stored_name' => $storedName,
                'mime' => $file['mime'],
                'size' => $file['size'],
            ];
        }

        return $storedFiles;
    }
}

// Delete file from storage and database
if (!function_exists('upload_delete')) {
    function upload_delete(int $fileId): bool
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return false;

        $stmt = $pdo->prepare('SELECT stored_name FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) return false;

        $uploadPath = $GLOBALS['config']['upload']['path'] ?? __DIR__ . '/../storage/uploads';
        $filePath = $uploadPath . '/' . $file['stored_name'];

        // Delete from filesystem
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        // Delete from database (cascade will handle items->files)
        db_delete('files', 'id = ?', [$fileId]);

        return true;
    }
}

// Delete all files for an item
if (!function_exists('upload_delete_item_files')) {
    function upload_delete_item_files(int $itemId): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;

        $stmt = $pdo->prepare('SELECT stored_name FROM files WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $files = $stmt->fetchAll();

        $uploadPath = $GLOBALS['config']['upload']['path'] ?? __DIR__ . '/../storage/uploads';

        foreach ($files as $file) {
            $filePath = $uploadPath . '/' . $file['stored_name'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // Database cascade will delete file records
    }
}

// Get files for an item
if (!function_exists('upload_get_item_files')) {
    function upload_get_item_files(int $itemId): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];

        $stmt = $pdo->prepare('SELECT * FROM files WHERE item_id = ? ORDER BY created_at');
        $stmt->execute([$itemId]);
        return $stmt->fetchAll();
    }
}

// Serve file for download (used by download.php)
if (!function_exists('upload_serve')) {
    function upload_serve(int $fileId): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            http_response_code(500);
            exit('Database not available');
        }

        $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
        $stmt->execute([$fileId]);
        $file = $stmt->fetch();

        if (!$file) {
            http_response_code(404);
            exit('File not found');
        }

        $uploadPath = $GLOBALS['config']['upload']['path'] ?? __DIR__ . '/../storage/uploads';
        $filePath = $uploadPath . '/' . $file['stored_name'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File not found on disk');
        }

        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . $file['size']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($file['original_name']) . '"');
        header('Cache-Control: private, max-age=3600');
        header('Accept-Ranges: bytes');

        // Stream file
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }
        exit;
    }
}

// Get MIME type from extension (for client-side validation)
if (!function_exists('upload_mime_from_extension')) {
    function upload_mime_from_extension(string $ext): string
    {
        $map = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'zip' => 'application/zip',
        ];
        return $map[strtolower($ext)] ?? 'application/octet-stream';
    }
}