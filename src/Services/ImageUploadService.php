<?php

namespace App\Services;

use App\Core\Config;

class ImageUploadService
{
    /**
     * Upload image to ImgBB from URL
     * 
     * @param string $imageUrl URL of the image to upload
     * @return array ['success' => bool, 'url' => string|null, 'message' => string]
     */
    public static function uploadFromUrl(string $imageUrl): array
    {
        try {
            // Get ImgBB API key from config
            $apiKey = Config::get('IMGBB_API_KEY');

            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'ImgBB API key not configured'
                ];
            }

            // Download image from URL
            $imageData = @file_get_contents($imageUrl);

            if ($imageData === false) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'Failed to download image from URL'
                ];
            }

            // Convert to base64
            $base64Image = base64_encode($imageData);

            // Upload to ImgBB
            return self::uploadBase64($base64Image, $apiKey);
        } catch (\Exception $e) {
            error_log("ImageUploadService error: " . $e->getMessage());
            return [
                'success' => false,
                'url' => null,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload base64 image to ImgBB
     * 
     * @param string $base64Image Base64 encoded image
     * @param string|null $apiKey ImgBB API key (optional, will use config if null)
     * @return array ['success' => bool, 'url' => string|null, 'message' => string]
     */
    public static function uploadBase64(string $base64Image, ?string $apiKey = null): array
    {
        try {
            if ($apiKey === null) {
                $apiKey = Config::get('IMGBB_API_KEY');
            }

            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'ImgBB API key not configured'
                ];
            }

            // ImgBB API endpoint
            $url = 'https://api.imgbb.com/1/upload';

            // Prepare POST data
            $postData = [
                'key' => $apiKey,
                'image' => $base64Image
            ];

            // Initialize cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'cURL error: ' . $curlError
                ];
            }

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'ImgBB API returned HTTP ' . $httpCode
                ];
            }

            // Parse response
            $result = json_decode($response, true);

            if (!$result || !isset($result['success']) || !$result['success']) {
                return [
                    'success' => false,
                    'url' => null,
                    'message' => 'ImgBB upload failed: ' . ($result['error']['message'] ?? 'Unknown error')
                ];
            }

            // Return the uploaded image URL
            return [
                'success' => true,
                'url' => $result['data']['url'],
                'message' => 'Image uploaded successfully'
            ];
        } catch (\Exception $e) {
            error_log("ImageUploadService::uploadBase64 error: " . $e->getMessage());
            return [
                'success' => false,
                'url' => null,
                'message' => $e->getMessage()
            ];
        }
    }
}
