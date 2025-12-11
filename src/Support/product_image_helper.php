<?php

/**
 * Get product image URL - supports both local paths and cloud URLs (ImgBB)
 * 
 * @param string|null $imageUrl
 * @return string
 */
if (!function_exists('getProductImageUrl')) {
    function getProductImageUrl($imageUrl)
    {
        if (empty($imageUrl)) {
            return '/assets/images/products/default.png';
        }

        // If it's a full URL (from ImgBB), return as is
        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            return htmlspecialchars($imageUrl);
        }

        // Otherwise, it's a local path - prepend if needed
        if (!str_starts_with($imageUrl, '/')) {
            return '/' . htmlspecialchars($imageUrl);
        }

        return htmlspecialchars($imageUrl);
    }
}
