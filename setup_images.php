<?php
// setup_images.php - Run this script once to set up the images directory structure

// Create images directory structure
$directories = [
    'images/',
    'images/candidates/',
    'images/thumbnails/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "Failed to create directory: $dir\n";
        }
    } else {
        echo "Directory already exists: $dir\n";
    }
}

// Create .htaccess file for images directory security
$htaccess_content = '# Prevent direct access to PHP files in images directory
<Files "*.php">
    Order Deny,Allow
    Deny from all
</Files>

# Set proper MIME types for images
<IfModule mod_mime.c>
    AddType image/jpeg .jpg .jpeg
    AddType image/png .png
    AddType image/gif .gif
    AddType image/webp .webp
</IfModule>

# Enable compression for images
<IfModule mod_deflate.c>
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png|webp)$ no-gzip dont-vary
</IfModule>

# Set proper cache headers
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
</IfModule>';

file_put_contents('images/.htaccess', $htaccess_content);
echo "Created .htaccess file for images directory\n";

// Create index.php files to prevent directory browsing
$index_content = '<?php
// Prevent directory browsing
http_response_code(403);
exit("Access denied");
?>';

file_put_contents('images/index.php', $index_content);
file_put_contents('images/candidates/index.php', $index_content);
file_put_contents('images/thumbnails/index.php', $index_content);
echo "Created index.php files to prevent directory browsing\n";

echo "\nImage upload setup completed successfully!\n";

// Database schema update for profile_image column
echo "\n--- Database Schema Update ---\n";
echo "Run this SQL command to add the profile_image column if it doesn't exist:\n\n";
echo "ALTER TABLE candidates ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER manifesto;\n\n";

// Additional helper functions for image processing
?>

<?php
/**
 * Advanced Image Upload Handler with additional features
 * Include this in your admin_candidates.php file for enhanced functionality
 */

class CandidateImageHandler {
    
    private $upload_dir = 'images/candidates/';
    private $thumbnail_dir = 'images/thumbnails/';
    private $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $max_file_size = 5 * 1024 * 1024; // 5MB
    private $thumbnail_size = 150;
    
    public function __construct() {
        // Ensure directories exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        if (!file_exists($this->thumbnail_dir)) {
            mkdir($this->thumbnail_dir, 0755, true);
        }
    }
    
    /**
     * Upload and process candidate image
     */
    public function uploadImage($file, $candidate_name) {
        // Validate file
        $this->validateFile($file);
        
        // Generate unique filename
        $filename = $this->generateFilename($candidate_name, $file);
        $filepath = $this->upload_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Failed to upload image file.");
        }
        
        // Create thumbnail
        $this->createThumbnail($filepath, $filename);
        
        // Optimize image
        $this->optimizeImage($filepath);
        
        return $filepath;
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        if (empty($file['name'])) {
            throw new Exception("No file uploaded.");
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . $this->getUploadErrorMessage($file['error']));
        }
        
        if (!in_array($file['type'], $this->allowed_types)) {
            throw new Exception("Invalid file type. Only JPEG, PNG, GIF, and WebP files are allowed.");
        }
        
        if ($file['size'] > $this->max_file_size) {
            throw new Exception("File size too large. Maximum " . ($this->max_file_size / 1024 / 1024) . "MB allowed.");
        }
        
        // Additional security check: verify actual file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            throw new Exception("File content does not match allowed image types.");
        }
    }
    
    /**
     * Generate safe filename
     */
    private function generateFilename($candidate_name, $file) {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $candidate_name);
        $safe_name = substr($safe_name, 0, 50); // Limit length
        return $safe_name . '_' . time() . '_' . uniqid() . '.' . $extension;
    }
    
    /**
     * Create thumbnail image
     */
    private function createThumbnail($source_path, $filename) {
        $source_info = getimagesize($source_path);
        if (!$source_info) {
            return false;
        }
        
        $source_width = $source_info[0];
        $source_height = $source_info[1];
        $source_type = $source_info[2];
        
        // Calculate thumbnail dimensions
        if ($source_width > $source_height) {
            $thumb_width = $this->thumbnail_size;
            $thumb_height = intval($source_height * $this->thumbnail_size / $source_width);
        } else {
            $thumb_height = $this->thumbnail_size;
            $thumb_width = intval($source_width * $this->thumbnail_size / $source_height);
        }
        
        // Create source image
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                $source_image = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source_image = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_GIF:
                $source_image = imagecreatefromgif($source_path);
                break;
            default:
                return false;
        }
        
        // Create thumbnail
        $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
        
        // Preserve transparency for PNG and GIF
        if ($source_type == IMAGETYPE_PNG || $source_type == IMAGETYPE_GIF) {
            imagealphablending($thumb_image, false);
            imagesavealpha($thumb_image, true);
            $transparent = imagecolorallocatealpha($thumb_image, 255, 255, 255, 127);
            imagefilledrectangle($thumb_image, 0, 0, $thumb_width, $thumb_height, $transparent);
        }
        
        // Resize image
        imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, $thumb_width, $thumb_height, $source_width, $source_height);
        
        // Save thumbnail
        $thumb_path = $this->thumbnail_dir . 'thumb_' . $filename;
        switch ($source_type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb_image, $thumb_path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb_image, $thumb_path);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumb_image, $thumb_path);
                break;
        }
        
        // Clean up memory
        imagedestroy($source_image);
        imagedestroy($thumb_image);
        
        return $thumb_path;
    }
    
    /**
     * Optimize image file size
     */
    private function optimizeImage($filepath) {
        $info = getimagesize($filepath);
        if (!$info) return false;
        
        $type = $info[2];
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($filepath);
                // Re-save with optimized quality
                imagejpeg($image, $filepath, 85);
                imagedestroy($image);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($filepath);
                // Re-save with compression
                imagepng($image, $filepath, 6);
                imagedestroy($image);
                break;
        }
    }
    
    /**
     * Delete image and thumbnail
     */
    public function deleteImage($filepath) {
        if ($filepath && file_exists($filepath)) {
            unlink($filepath);
            
            // Also delete thumbnail if exists
            $filename = basename($filepath);
            $thumb_path = $this->thumbnail_dir . 'thumb_' . $filename;
            if (file_exists($thumb_path)) {
                unlink($thumb_path);
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Get thumbnail path for a given image
     */
    public function getThumbnailPath($image_path) {
        if (!$image_path) return null;
        
        $filename = basename($image_path);
        $thumb_path = $this->thumbnail_dir . 'thumb_' . $filename;
        
        return file_exists($thumb_path) ? $thumb_path : $image_path;
    }
}

// Usage example:
/*
$imageHandler = new CandidateImageHandler();

// Upload image
try {
    $image_path = $imageHandler->uploadImage($_FILES['profile_image'], $_POST['name']);
    echo "Image uploaded successfully: " . $image_path;
} catch (Exception $e) {
    echo "Upload failed: " . $e->getMessage();
}

// Delete image
$imageHandler->deleteImage($old_image_path);

// Get thumbnail
$thumbnail = $imageHandler->getThumbnailPath($image_path);
*/
?>