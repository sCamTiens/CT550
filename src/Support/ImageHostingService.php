<?php

namespace App\Support;

/**
 * ImgBB Image Hosting Service
 * Free image hosting với API đơn giản
 * https://api.imgbb.com/
 */
class ImageHostingService
{
    private $apiKey;
    private $apiUrl = 'https://api.imgbb.com/1/upload';

    public function __construct()
    {
        $this->apiKey = getenv('IMGBB_API_KEY');

        if (!$this->apiKey) {
            throw new \Exception("IMGBB_API_KEY not set in .env file");
        }
    }

    /**
     * Upload image to ImgBB
     * 
     * @param string $filePath Local file path
     * @param string|null $name Optional image name
     * @param int $expiration Expiration in seconds (null = permanent)
     * @return array ['url' => direct_url, 'delete_url' => delete_url, 'id' => image_id]
     */
    public function uploadImage($filePath, $name = null, $expiration = null)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        // Read image as base64
        $imageData = base64_encode(file_get_contents($filePath));

        // Prepare POST data
        $postData = [
            'key' => $this->apiKey,
            'image' => $imageData
        ];

        if ($name) {
            $postData['name'] = $name;
        }

        if ($expiration) {
            $postData['expiration'] = $expiration;
        }

        // Upload via cURL
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For XAMPP

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("cURL error: $error");
        }

        if ($httpCode !== 200) {
            throw new \Exception("Upload failed (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);

        if (!$data || !$data['success']) {
            throw new \Exception("Upload failed: " . ($data['error']['message'] ?? 'Unknown error'));
        }

        // Return URLs
        return [
            'id' => $data['data']['id'],
            'url' => $data['data']['url'],
            'display_url' => $data['data']['display_url'],
            'delete_url' => $data['data']['delete_url'],
            'thumb_url' => $data['data']['thumb']['url'] ?? null,
            'medium_url' => $data['data']['medium']['url'] ?? null,
            'image' => $data['data']['image'] ?? null
        ];
    }

    /**
     * Upload from URL
     * 
     * @param string $imageUrl
     * @param string|null $name
     * @return array
     */
    public function uploadFromUrl($imageUrl, $name = null)
    {
        $postData = [
            'key' => $this->apiKey,
            'image' => $imageUrl
        ];

        if ($name) {
            $postData['name'] = $name;
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Upload failed (HTTP $httpCode): $response");
        }

        $data = json_decode($response, true);

        if (!$data || !$data['success']) {
            throw new \Exception("Upload failed: " . ($data['error']['message'] ?? 'Unknown error'));
        }

        return [
            'id' => $data['data']['id'],
            'url' => $data['data']['url'],
            'display_url' => $data['data']['display_url'],
            'delete_url' => $data['data']['delete_url'],
            'thumb_url' => $data['data']['thumb']['url'] ?? null
        ];
    }

    /**
     * Delete image (requires delete URL from upload response)
     * Note: ImgBB doesn't provide API delete endpoint, only delete URL
     * 
     * @param string $deleteUrl
     * @return bool
     */
    public function deleteImage($deleteUrl)
    {
        // ImgBB requires visiting delete URL in browser
        // We can only log it for manual deletion
        error_log("To delete image, visit: $deleteUrl");
        return false;
    }

    /**
     * Get direct image URL
     * 
     * @param array $uploadResult Result from uploadImage()
     * @return string
     */
    public function getDirectUrl($uploadResult)
    {
        return $uploadResult['url'] ?? $uploadResult['display_url'];
    }
}
