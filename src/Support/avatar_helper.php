<?php

/**
 * Helper function to get avatar URL
 * Supports both local paths and cloud URLs (ImgBB)
 * 
 * @param string|null $avatarUrl
 * @return string
 */
function getAvatarUrl($avatarUrl)
{
    if (empty($avatarUrl)) {
        return '/assets/images/avatar/default.png';
    }

    // If it's a full URL (from ImgBB), return as is
    if (str_starts_with($avatarUrl, 'http://') || str_starts_with($avatarUrl, 'https://')) {
        return htmlspecialchars($avatarUrl);
    }

    // Otherwise, it's a local path
    return '/assets/images/avatar/' . htmlspecialchars($avatarUrl);
}
