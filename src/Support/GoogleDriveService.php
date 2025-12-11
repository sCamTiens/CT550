<?php

namespace App\Support;

/**
 * Google Drive Service - cURL Implementation
 * Không cần Google API Client SDK
 * Chỉ dùng cURL thuần để upload file lên Drive
 */
class GoogleDriveService
{
    private $credentials;
    private $accessToken;
    private $folderId;

    public function __construct()
    {
        // Load credentials
        $credentialsPath = __DIR__ . '/../../config/google/credentials.json';
        if (!file_exists($credentialsPath)) {
            throw new \Exception("Google Drive credentials file not found at: $credentialsPath");
        }

        $this->credentials = json_decode(file_get_contents($credentialsPath), true);
        $this->folderId = getenv('GOOGLE_DRIVE_FOLDER_ID') ?: null;

        // Get access token
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get OAuth2 access token using service account
     */
    private function getAccessToken()
    {
        // Create JWT
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claimSet = json_encode([
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        // Base64 encode
        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlClaimSet = $this->base64UrlEncode($claimSet);

        // Sign with private key
        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaimSet;
        $signature = '';
        openssl_sign($signatureInput, $signature, $this->credentials['private_key'], 'SHA256');
        $base64UrlSignature = $this->base64UrlEncode($signature);

        // Create JWT
        $jwt = $signatureInput . '.' . $base64UrlSignature;

        // Exchange JWT for access token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to get access token: $response");
        }

        $data = json_decode($response, true);
        return $data['access_token'];
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile($filePath, $fileName, $mimeType = 'image/jpeg', $subfolder = null)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        // Get or create subfolder
        $parentFolderId = $this->folderId;
        if ($subfolder) {
            $parentFolderId = $this->getOrCreateFolder($subfolder, $this->folderId);
        }

        // Read file content
        $fileContent = file_get_contents($filePath);

        // Create metadata
        $metadata = json_encode([
            'name' => $fileName,
            'parents' => $parentFolderId ? [$parentFolderId] : []
        ]);

        // Create multipart body
        $boundary = uniqid();
        $delimiter = "\r\n--" . $boundary . "\r\n";
        $closeDelimiter = "\r\n--" . $boundary . "--";

        $multipartBody = $delimiter;
        $multipartBody .= 'Content-Type: application/json; charset=UTF-8' . "\r\n\r\n";
        $multipartBody .= $metadata . $delimiter;
        $multipartBody .= 'Content-Type: ' . $mimeType . "\r\n";
        $multipartBody .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $multipartBody .= base64_encode($fileContent) . $closeDelimiter;

        // Upload to Drive
        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink,webContentLink');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipartBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
            'Content-Length: ' . strlen($multipartBody)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Upload failed (HTTP $httpCode): $response");
        }

        $file = json_decode($response, true);

        // Make file public
        $this->makePublic($file['id']);

        // Transfer ownership to actual user (to use their quota)
        $ownerEmail = getenv('GOOGLE_DRIVE_OWNER_EMAIL');
        if ($ownerEmail) {
            $this->transferOwnership($file['id'], $ownerEmail);
        }

        // Return result
        return [
            'id' => $file['id'],
            'url' => $this->getPublicUrl($file['id']),
            'webViewLink' => $file['webViewLink'] ?? null,
            'webContentLink' => $file['webContentLink'] ?? null
        ];
    }

    /**
     * Delete file from Drive
     */
    public function deleteFile($fileId)
    {
        $ch = curl_init('https://www.googleapis.com/drive/v3/files/' . $fileId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 204;
    }

    /**
     * Make file publicly accessible
     */
    private function makePublic($fileId)
    {
        $ch = curl_init('https://www.googleapis.com/drive/v3/files/' . $fileId . '/permissions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'role' => 'reader',
            'type' => 'anyone'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Transfer file ownership to another user
     */
    private function transferOwnership($fileId, $newOwnerEmail)
    {
        $ch = curl_init('https://www.googleapis.com/drive/v3/files/' . $fileId . '/permissions?transferOwnership=true');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'role' => 'owner',
            'type' => 'user',
            'emailAddress' => $newOwnerEmail
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Failed to transfer ownership: $response");
        }
    }

    /**
     * Get or create folder
     */
    private function getOrCreateFolder($folderName, $parentId = null)
    {
        // Search for folder
        $query = "mimeType='application/vnd.google-apps.folder' and name='{$folderName}' and trashed=false";
        if ($parentId) {
            $query .= " and '{$parentId}' in parents";
        }

        $ch = curl_init('https://www.googleapis.com/drive/v3/files?q=' . urlencode($query) . '&fields=files(id,name)');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!empty($data['files'])) {
            return $data['files'][0]['id'];
        }

        // Create folder
        $ch = curl_init('https://www.googleapis.com/drive/v3/files?fields=id');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parentId ? [$parentId] : []
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $folder = json_decode($response, true);
        return $folder['id'];
    }

    /**
     * Get public URL
     */
    public function getPublicUrl($fileId)
    {
        return "https://drive.google.com/uc?export=view&id={$fileId}";
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Extract file ID from Drive URL
     */
    public static function extractFileId($url)
    {
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
