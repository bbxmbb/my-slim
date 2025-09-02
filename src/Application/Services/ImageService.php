<?php
namespace App\Application\Services;

class ImageService
{
    public function checkAndProcessImage($uploadedFile, $uploadPath)
    {
        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return false;
        }

        $tempFilePath = $uploadedFile->getStream()->getMetadata('uri');
        $imageType    = exif_imagetype($tempFilePath);

        if ($imageType === false) {
            return false;
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($tempFilePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($tempFilePath);
                break;
            default:
                return false;
        }

        // Fix image orientation
        $image = $this->fixOrientationImage($image, $tempFilePath);

        // Sanitize file name
        $originalFileName = htmlspecialchars(preg_replace('/\s+/', '_', $uploadedFile->getClientFilename()));
        $basename         = uniqid();

        // Resize and save images
        $originalPath  = $this->resizeImage($basename . '_' . $originalFileName, $uploadPath, $image, 1024);
        $thumbnailPath = $this->resizeImage($basename . '_' . $originalFileName, $uploadPath, $image, 300);

        return [
            'original_path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
            'original_name' => $originalFileName
        ];
    }

    private function fixOrientationImage($image, $tempFilePath)
    {
        // Check if EXIF is available and file is JPEG
        if (function_exists('exif_read_data') && @exif_imagetype($tempFilePath) === IMAGETYPE_JPEG) {
            $exif = @exif_read_data($tempFilePath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        }
        return $image;
    }

    private function resizeImage($originalFileName, $uploadPath, $image, $imageWidthOrHeight)
    {
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);

        // Resize the image to reduce its dimensions
        $newWidth  = $imageWidthOrHeight; // Set the desired width
        $newHeight = $imageWidthOrHeight;

        $aspectRatio = imagesx($image) / imagesy($image);

        if (imagesx($image) > $newWidth) {
            $newHeight = ceil($newWidth / $aspectRatio);
        } else if (imagesy($image) > $newHeight) {
            $newWidth = ceil($newHeight * $aspectRatio);
        } else {
            $newWidth  = imagesx($image);
            $newHeight = imagesy($image);
        }
        $resizedImage = imagescale($image, $newWidth, $newHeight);

        $filename      = pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . $newWidth;
        $original_path = sprintf('%s.%0.8s', $filename, $extension);

        // Compress and save the image as a JPEG
        $filePath = $uploadPath . $original_path; // Change the file path
        imagejpeg($resizedImage, $filePath, 80); // Adjust the quality (0-100) as needed

        // Free up memory
        imagedestroy($image);
        imagedestroy($resizedImage);

        return $original_path;
    }
}
