<?php
/**
 * Simple File Upload Handler
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * FileUpload class for handling image uploads
 */
class FileUpload {

    /** @var array Allowed file extensions */
    private $allowedTypes;

    /** @var int Maximum file size in bytes */
    private $maxSize;

    /** @var string Base upload directory */
    private $uploadPath;

    /**
     * Constructor
     *
     * @param array $allowedTypes
     * @param int $maxSize
     * @param string $uploadPath
     */
    public function __construct(
        $allowedTypes = ALLOWED_IMAGE_TYPES,
        $maxSize = MAX_FILE_SIZE,
        $uploadPath = UPLOAD_PATH
    ) {
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;
        $this->uploadPath = rtrim($uploadPath, '/') . '/';
    }

    /**
     * Upload image
     *
     * @param array $file     File array from $_FILES
     * @param string $subDir  Optional sub directory inside upload path
     * @return array          Result array with success flag and message/filename
     */
    public function upload(array $file, $subDir = '') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'No file uploaded'
            ];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Invalid file type'
            ];
        }

        if ($file['size'] > $this->maxSize) {
            return [
                'success' => false,
                'message' => 'File is too large'
            ];
        }

        $directory = $this->uploadPath . trim($subDir, '/');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = uniqid('img_', true) . '.' . $extension;
        $destination = rtrim($directory, '/') . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $destination
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to move uploaded file'
        ];
    }
}

?>