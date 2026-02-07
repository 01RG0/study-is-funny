<?php
/**
 * Video Utilities - Handle video URL parsing and conversion
 * Supports: YouTube, Vimeo, CDN links, MP4/Local files, iframe embeds
 */

class VideoUtils {
    
    /**
     * Extract Vimeo video ID from various URL formats
     * @param string $url The Vimeo URL or iframe embed code
     * @return string|null The Vimeo video ID or null if not found
     */
    public static function extractVimeoId($url) {
        if (empty($url)) return null;
        
        // Clean up HTML entities
        $url = html_entity_decode($url);
        $url = str_replace(['%22', '%20', '%2F'], ['"', ' ', '/'], $url);
        
        // Pattern 1: vimeo.com/VIDEO_ID
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Pattern 2: player.vimeo.com/video/VIDEO_ID
        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Pattern 3: iframe src with video ID in attributes
        if (preg_match('/[?&]video(?:_id)?=(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Pattern 4: numbers within src attribute
        if (preg_match('/src\s*=\s*["\']?[^"\']*?(\d{7,})/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if URL is an iframe embed code
     * @param string $url The URL to check
     * @return bool True if it's an iframe embed
     */
    public static function isIframeEmbed($url) {
        return (strpos($url, '<iframe') !== false || 
                strpos($url, '&lt;iframe') !== false);
    }
    
    /**
     * Extract the src attribute from iframe embed code
     * @param string $iframeCode The iframe HTML code
     * @return string|null The src URL or null if not found
     */
    public static function extractIframeSrc($iframeCode) {
        if (empty($iframeCode)) return null;
        
        // Decode HTML entities if present
        $decoded = html_entity_decode($iframeCode);
        
        // Extract src attribute
        if (preg_match('/src\s*=\s*["\']([^"\']+)["\']/', $decoded, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract YouTube video ID from various URL formats
     * @param string $url The YouTube URL
     * @return string|null The YouTube video ID or null if not found
     */
    public static function extractYouTubeId($url) {
        if (empty($url)) return null;
        
        // youtu.be/ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        
        // youtube.com/watch?v=ID
        if (preg_match('/v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        
        // youtube.com/embed/ID
        if (preg_match('/\/embed\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Detect video source type from URL
     * @param string $url The video URL
     * @return string One of: 'vimeo', 'youtube', 'local', 'cdn', 'iframe', 'unknown'
     */
    public static function detectVideoSource($url) {
        if (empty($url)) return 'unknown';
        
        // Check for iframe embed code
        if (static::isIframeEmbed($url)) {
            return 'iframe';
        }
        
        // Vimeo detection
        if (strpos($url, 'vimeo.com') !== false || strpos($url, 'player.vimeo.com') !== false) {
            return 'vimeo';
        }
        
        // YouTube detection
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return 'youtube';
        }
        
        // Local video file detection (by extension)
        if (preg_match('/\.(mp4|webm|avi|mov|mkv|flv|m3u8|ts)$/i', $url)) {
            return 'local';
        }
        
        // Local uploads detection
        if (strpos($url, 'uploads/videos') !== false || strpos($url, 'uploads\\videos') !== false) {
            return 'local';
        }
        
        // Generic CDN detection (http/https URL but not a local file)
        if (filter_var($url, FILTER_VALIDATE_URL) && (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0)) {
            return 'cdn';
        }
        
        return 'unknown';
    }
    
    /**
     * Generate proper embed URL for Vimeo
     * @param string $videoId The Vimeo video ID
     * @return string The embed URL
     */
    public static function getVimeoEmbedUrl($videoId) {
        return "https://player.vimeo.com/video/{$videoId}?badge=0&autopause=0&player_id=0&app_id=58479";
    }
    
    /**
     * Generate proper embed URL for YouTube
     * @param string $videoId The YouTube video ID
     * @return string The embed URL
     */
    public static function getYouTubeEmbedUrl($videoId) {
        return "https://www.youtube.com/embed/{$videoId}?rel=0&modestbranding=1&autoplay=0";
    }
    
    /**
     * Normalize and clean a video URL
     * @param string $url The video URL
     * @param string $source The source type (if known)
     * @return array ['url' => string, 'source' => string, 'video_id' => string|null, 'embed_type' => string]
     */
    public static function normalizeVideoUrl($url, $source = null) {
        if (empty($url)) {
            return ['url' => null, 'source' => 'unknown', 'video_id' => null, 'embed_type' => 'unknown'];
        }
        
        $source = $source ?? static::detectVideoSource($url);
        $videoId = null;
        $embedUrl = $url;
        $embedType = 'video'; // 'video', 'iframe'
        
        switch ($source) {
            case 'iframe':
                // Extract the src from iframe embed code
                $extractedSrc = static::extractIframeSrc($url);
                if ($extractedSrc) {
                    $embedUrl = $extractedSrc;
                    $embedType = 'iframe';
                    // Recursively normalize the extracted src
                    $normalized = static::normalizeVideoUrl($extractedSrc, null);
                    return [
                        'url' => $normalized['url'],
                        'source' => $normalized['source'],
                        'video_id' => $normalized['video_id'],
                        'embed_type' => $normalized['embed_type']
                    ];
                }
                break;
                
            case 'vimeo':
                $videoId = static::extractVimeoId($url);
                if ($videoId) {
                    $embedUrl = static::getVimeoEmbedUrl($videoId);
                    $embedType = 'iframe';
                }
                break;
                
            case 'youtube':
                $videoId = static::extractYouTubeId($url);
                if ($videoId) {
                    $embedUrl = static::getYouTubeEmbedUrl($videoId);
                    $embedType = 'iframe';
                }
                break;
                
            case 'cdn':
                // For CDN URLs, check if they look like they can be embedded as iframes
                // (many CDN providers support iframe embeds for video files)
                if (static::isIframeableUrl($url)) {
                    $embedType = 'iframe';
                } else {
                    // Otherwise use as direct video source
                    $embedType = 'video';
                }
                $embedUrl = $url;
                break;
                
            case 'local':
                // Keep local URLs as is
                $embedUrl = $url;
                $embedType = 'video';
                break;
        }
        
        return [
            'url' => $embedUrl,
            'source' => $source,
            'video_id' => $videoId,
            'embed_type' => $embedType
        ];
    }
    
    /**
     * Check if a URL looks like it can be embedded as an iframe
     * @param string $url The URL to check
     * @return bool True if it looks iframe-able
     */
    public static function isIframeableUrl($url) {
        if (empty($url)) return false;
        
        // Check for known iframe-compatible CDN domains
        $iframeableDomains = [
            'vimeo.com',
            'youtube.com',
            'youtu.be',
            'dailymotion.com',
            'wistia.com',
            'twitch.tv',
            'rumble.com',
            'bitchute.com',
            'odysee.com',
            'archive.org',
            'cloudflare.com',
            'bunny.net',
            'fastly.net',
            'akamai.net',
            'cdn77.com',
            'keycdn.com',
            'stackpath.com',
            's3.amazonaws.com',
            'azure.microsoft.com',
            'storage.googleapis.com',
            'mediadelivery.net',
            'iframe.mediadelivery.net'
        ];
        
        foreach ($iframeableDomains as $domain) {
            if (stripos($url, $domain) !== false) {
                return true;
            }
        }
        
        // If it's an HTML or JSON-LD based embed format, it's iframe-able
        if (preg_match('/<iframe|<embed|<video/i', $url)) {
            return true;
        }
        
        return false;
    }
}
?>
