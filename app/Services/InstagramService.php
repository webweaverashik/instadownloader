<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InstagramService
{
    /**
     * Analyze Instagram URL and extract content information
     */
    public function analyzeUrl(string $url): array
    {
        // Detect content type from URL
        $contentType = $this->detectContentType($url);
        
        // Extract shortcode from URL
        $shortcode = $this->extractShortcode($url);
        
        // Cache key for this URL
        $cacheKey = 'instagram_' . md5($url);
        
        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fetch content data (this is a simplified version)
        // In production, you would use Instagram's API or a third-party service
        $contentData = $this->fetchContentData($url, $shortcode, $contentType);
        
        // Cache for 1 hour
        Cache::put($cacheKey, $contentData, 3600);
        
        return $contentData;
    }

    /**
     * Get download URL for the content
     */
    public function getDownloadUrl(string $url, string $type, string $quality, string $format): array
    {
        $shortcode = $this->extractShortcode($url);
        
        // In production, this would call an actual Instagram API or scraping service
        // For now, we'll return a demo response
        
        $downloadUrl = $this->generateDownloadUrl($url, $type, $quality, $format);
        
        return [
            'download_url' => $downloadUrl,
            'filename' => $this->generateFilename($shortcode, $type, $quality, $format),
            'type' => $type,
            'quality' => $quality,
            'format' => $format,
        ];
    }

    /**
     * Detect content type from URL
     */
    public function detectContentType(string $url): string
    {
        if (preg_match('/instagram\.com\/(reel|reels)\//i', $url)) {
            return 'reels';
        }
        
        if (preg_match('/instagram\.com\/tv\//i', $url)) {
            return 'video';
        }
        
        if (preg_match('/instagram\.com\/stories\//i', $url)) {
            return 'story';
        }
        
        // Default to post (could be video or photo)
        return 'post';
    }

    /**
     * Extract shortcode from Instagram URL
     */
    protected function extractShortcode(string $url): string
    {
        preg_match('/instagram\.com\/(?:p|reel|reels|tv)\/([A-Za-z0-9_-]+)/i', $url, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Fetch content data from Instagram
     * Note: In production, implement actual API calls here
     */
    protected function fetchContentData(string $url, string $shortcode, string $contentType): array
    {
        // This is demo data - replace with actual API integration
        // You can use RapidAPI's Instagram Scraper or similar services
        
        $isVideo = in_array($contentType, ['video', 'reels']);
        $isCarousel = rand(0, 1) === 1 && $contentType === 'post';
        
        return [
            'shortcode' => $shortcode,
            'type' => $contentType,
            'is_video' => $isVideo,
            'is_carousel' => $isCarousel,
            'carousel_count' => $isCarousel ? rand(2, 10) : 0,
            'username' => '@instagram_user',
            'user_avatar' => 'https://ui-avatars.com/api/?name=User&background=random',
            'post_date' => now()->subDays(rand(1, 30))->format('F j, Y'),
            'caption' => 'Amazing content! 📸 #instagram #download #free',
            'likes' => rand(100, 10000),
            'comments' => rand(10, 500),
            'preview_url' => 'https://images.unsplash.com/photo-1611262588024-d12430b98920?w=600',
            'duration' => $isVideo ? $this->formatDuration(rand(15, 180)) : null,
            'resolution' => $isVideo ? '1080x1920' : '1080x1350',
            'download_options' => $this->getDownloadOptions($contentType, $isVideo),
        ];
    }

    /**
     * Get available download options based on content type
     */
    protected function getDownloadOptions(string $contentType, bool $isVideo): array
    {
        if ($isVideo || $contentType === 'reels') {
            return [
                [
                    'quality' => 'hd',
                    'label' => 'HD Quality',
                    'resolution' => '1080p',
                    'format' => 'mp4',
                    'size' => rand(10, 50) . ' MB',
                ],
                [
                    'quality' => 'sd',
                    'label' => 'SD Quality',
                    'resolution' => '720p',
                    'format' => 'mp4',
                    'size' => rand(5, 20) . ' MB',
                ],
                [
                    'quality' => 'audio',
                    'label' => 'Audio Only',
                    'resolution' => '320kbps',
                    'format' => 'mp3',
                    'size' => rand(1, 5) . ' MB',
                ],
            ];
        }

        return [
            [
                'format' => 'jpg',
                'label' => 'JPG Format',
                'description' => 'Original Quality • Compressed',
                'size' => rand(300, 800) . ' KB',
            ],
            [
                'format' => 'png',
                'label' => 'PNG Format',
                'description' => 'Lossless • Best Quality',
                'size' => rand(800, 2000) . ' KB',
            ],
            [
                'format' => 'webp',
                'label' => 'WebP Format',
                'description' => 'Modern • Optimized',
                'size' => rand(200, 500) . ' KB',
            ],
        ];
    }

    /**
     * Generate download URL
     * Note: Replace with actual download URL generation logic
     */
    protected function generateDownloadUrl(string $url, string $type, string $quality, string $format): string
    {
        // In production, this would return the actual Instagram media URL
        // For now, return a placeholder
        $token = Str::random(32);
        return url("/api/download/{$token}?format={$format}&quality={$quality}");
    }

    /**
     * Generate filename for download
     */
    protected function generateFilename(string $shortcode, string $type, string $quality, string $format): string
    {
        $prefix = match($type) {
            'reels' => 'instagram_reel',
            'video' => 'instagram_video',
            'photo' => 'instagram_photo',
            default => 'instagram_content',
        };
        
        return "{$prefix}_{$shortcode}_{$quality}.{$format}";
    }

    /**
     * Format duration in seconds to MM:SS
     */
    protected function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }
}