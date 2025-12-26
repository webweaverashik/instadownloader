<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class InstagramScraperService
{
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    
    protected array $defaultHeaders = [];

    public function __construct()
    {
        // Note: Removed 'br' (Brotli) encoding as it's not supported by all cURL installations
        $this->defaultHeaders = [
            'User-Agent' => $this->userAgent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate', // Removed 'br' to fix Laragon cURL issue
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Sec-Fetch-User' => '?1',
            'Cache-Control' => 'max-age=0',
        ];
    }

    /**
     * Get HTTP client with proper configuration for Laragon/Windows
     */
    protected function getHttpClient()
    {
        return Http::withHeaders($this->defaultHeaders)
            ->withOptions([
                'verify' => false, // Disable SSL verification for local development
                'timeout' => 30,
                'connect_timeout' => 10,
                'decode_content' => true, // Let Guzzle handle decoding
            ]);
    }

    /**
     * Analyze Instagram URL and extract content information
     */
    public function analyzeUrl(string $url): array
    {
        // Clean the URL - remove tracking parameters
        $url = $this->cleanInstagramUrl($url);
        
        $shortcode = $this->extractShortcode($url);
        
        if (empty($shortcode)) {
            throw new Exception('Invalid Instagram URL. Could not extract shortcode.');
        }

        $cacheKey = 'instagram_' . md5($shortcode);
        
        // Try cache first (cache for 30 minutes)
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Try multiple methods to fetch data
        $data = null;
        $errors = [];

        // Method 1: Try GraphQL API
        try {
            $data = $this->fetchViaGraphQL($shortcode);
        } catch (Exception $e) {
            $errors[] = 'GraphQL: ' . $e->getMessage();
            Log::debug('GraphQL fetch failed: ' . $e->getMessage());
        }
        
        // Method 2: Try oEmbed
        if (!$data) {
            try {
                $data = $this->fetchViaOEmbed($url);
            } catch (Exception $e) {
                $errors[] = 'oEmbed: ' . $e->getMessage();
                Log::debug('oEmbed fetch failed: ' . $e->getMessage());
            }
        }
        
        // Method 3: Try embed page scraping
        if (!$data) {
            try {
                $data = $this->fetchViaEmbedPage($shortcode);
            } catch (Exception $e) {
                $errors[] = 'Embed: ' . $e->getMessage();
                Log::debug('Embed page fetch failed: ' . $e->getMessage());
            }
        }

        // Method 4: Try direct page with JSON
        if (!$data) {
            try {
                $data = $this->fetchViaDirectApi($shortcode);
            } catch (Exception $e) {
                $errors[] = 'Direct: ' . $e->getMessage();
                Log::debug('Direct API fetch failed: ' . $e->getMessage());
            }
        }

        // Method 5: Try web page scraping
        if (!$data) {
            try {
                $data = $this->fetchViaWebPage($url, $shortcode);
            } catch (Exception $e) {
                $errors[] = 'WebPage: ' . $e->getMessage();
                Log::debug('Web page fetch failed: ' . $e->getMessage());
            }
        }

        if (!$data) {
            Log::error('All Instagram fetch methods failed', ['errors' => $errors, 'url' => $url]);
            throw new Exception('Unable to fetch Instagram content. The post may be private or deleted. Errors: ' . implode('; ', $errors));
        }

        // Cache the result
        Cache::put($cacheKey, $data, 1800); // 30 minutes

        return $data;
    }

    /**
     * Clean Instagram URL - remove tracking parameters
     */
    protected function cleanInstagramUrl(string $url): string
    {
        // Parse URL and remove query parameters
        $parsed = parse_url($url);
        $cleanUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'www.instagram.com') . ($parsed['path'] ?? '');
        
        // Remove trailing slash
        return rtrim($cleanUrl, '/');
    }

    /**
     * Fetch data via Instagram GraphQL API
     */
    protected function fetchViaGraphQL(string $shortcode): ?array
    {
        $variables = json_encode([
            'shortcode' => $shortcode,
            'child_comment_count' => 0,
            'fetch_comment_count' => 0,
            'parent_comment_count' => 0,
            'has_threaded_comments' => false,
        ]);

        $url = 'https://www.instagram.com/graphql/query/?' . http_build_query([
            'query_hash' => 'b3055c01b4b222b8a47dc12b090e4e64',
            'variables' => $variables,
        ]);

        $response = $this->getHttpClient()->get($url);

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['data']['shortcode_media'])) {
                return $this->parseGraphQLResponse($json['data']['shortcode_media']);
            }
        }

        return null;
    }

    /**
     * Fetch data via oEmbed API
     */
    protected function fetchViaOEmbed(string $url): ?array
    {
        $oembedUrl = 'https://api.instagram.com/oembed/?' . http_build_query([
            'url' => $url,
            'omitscript' => 'true',
        ]);

        $response = $this->getHttpClient()->get($oembedUrl);

        if ($response->successful()) {
            $data = $response->json();
            if ($data && isset($data['thumbnail_url'])) {
                return $this->parseOEmbedResponse($data, $url);
            }
        }

        return null;
    }

    /**
     * Fetch via embed page
     */
    protected function fetchViaEmbedPage(string $shortcode): ?array
    {
        $embedUrl = "https://www.instagram.com/p/{$shortcode}/embed/captioned/";
        
        $response = $this->getHttpClient()->get($embedUrl);

        if ($response->successful()) {
            $html = $response->body();
            $data = $this->parseEmbedPage($html, $shortcode);
            if ($data) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Fetch via direct API endpoint
     */
    protected function fetchViaDirectApi(string $shortcode): ?array
    {
        $directUrl = "https://www.instagram.com/p/{$shortcode}/?__a=1&__d=dis";
        
        $response = $this->getHttpClient()
            ->withHeaders([
                'X-IG-App-ID' => '936619743392459',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get($directUrl);

        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['items'][0])) {
                return $this->parseDirectResponse($json['items'][0]);
            }
            if (isset($json['graphql']['shortcode_media'])) {
                return $this->parseGraphQLResponse($json['graphql']['shortcode_media']);
            }
        }

        return null;
    }

    /**
     * Fetch data by scraping the web page
     */
    protected function fetchViaWebPage(string $url, string $shortcode): ?array
    {
        $response = $this->getHttpClient()->get($url);

        if ($response->successful()) {
            $html = $response->body();
            return $this->parseWebPage($html, $shortcode);
        }

        return null;
    }

    /**
     * Parse GraphQL response
     */
    protected function parseGraphQLResponse(array $media): array
    {
        $isVideo = $media['is_video'] ?? false;
        $isCarousel = ($media['__typename'] ?? '') === 'GraphSidecar';
        
        $contentType = 'photo';
        if ($isVideo) {
            $contentType = $this->isReels($media) ? 'reels' : 'video';
        }

        $result = [
            'success' => true,
            'shortcode' => $media['shortcode'],
            'type' => $contentType,
            'is_video' => $isVideo,
            'is_carousel' => $isCarousel,
            'username' => '@' . ($media['owner']['username'] ?? 'unknown'),
            'user_id' => $media['owner']['id'] ?? null,
            'user_avatar' => $media['owner']['profile_pic_url'] ?? $this->getDefaultAvatar(),
            'user_full_name' => $media['owner']['full_name'] ?? '',
            'post_date' => isset($media['taken_at_timestamp']) 
                ? date('F j, Y', $media['taken_at_timestamp']) 
                : date('F j, Y'),
            'timestamp' => $media['taken_at_timestamp'] ?? time(),
            'caption' => $this->extractCaption($media),
            'likes' => $media['edge_media_preview_like']['count'] ?? 0,
            'comments' => $media['edge_media_to_comment']['count'] ?? 0,
            'preview_url' => $media['display_url'] ?? $media['thumbnail_src'] ?? '',
            'media' => [],
        ];

        if ($isCarousel && isset($media['edge_sidecar_to_children']['edges'])) {
            foreach ($media['edge_sidecar_to_children']['edges'] as $index => $edge) {
                $child = $edge['node'];
                $result['media'][] = $this->extractMediaInfo($child, $index);
            }
            $result['carousel_count'] = count($result['media']);
        } else {
            $result['media'][] = $this->extractMediaInfo($media, 0);
            $result['carousel_count'] = 0;
        }

        // Set main media info from first item
        if (!empty($result['media'])) {
            $mainMedia = $result['media'][0];
            $result['duration'] = $mainMedia['duration'] ?? null;
            $result['resolution'] = $mainMedia['resolution'] ?? '1080 × 1080';
            $result['download_options'] = $this->generateDownloadOptions($result);
        }

        return $result;
    }

    /**
     * Extract media information from a single media node
     */
    protected function extractMediaInfo(array $media, int $index): array
    {
        $isVideo = $media['is_video'] ?? false;
        
        $info = [
            'index' => $index,
            'type' => $isVideo ? 'video' : 'photo',
            'preview_url' => $media['display_url'] ?? '',
            'thumbnail_url' => $media['thumbnail_src'] ?? $media['display_url'] ?? '',
        ];

        if ($isVideo) {
            $info['video_url'] = $media['video_url'] ?? null;
            $info['duration'] = isset($media['video_duration']) 
                ? $this->formatDuration($media['video_duration']) 
                : null;
            $info['views'] = $media['video_view_count'] ?? 0;
        }

        // Get dimensions
        $width = $media['dimensions']['width'] ?? 1080;
        $height = $media['dimensions']['height'] ?? 1080;
        $info['width'] = $width;
        $info['height'] = $height;
        $info['resolution'] = "{$width} × {$height}";

        // Get all available image resources
        if (isset($media['display_resources'])) {
            $info['resources'] = array_map(function ($resource) {
                return [
                    'src' => $resource['src'],
                    'width' => $resource['config_width'],
                    'height' => $resource['config_height'],
                ];
            }, $media['display_resources']);
        }

        return $info;
    }

    /**
     * Parse oEmbed response
     */
    protected function parseOEmbedResponse(array $data, string $originalUrl): array
    {
        $shortcode = $this->extractShortcode($originalUrl);
        
        return [
            'success' => true,
            'shortcode' => $shortcode,
            'type' => 'post',
            'is_video' => false,
            'is_carousel' => false,
            'username' => '@' . ($data['author_name'] ?? 'unknown'),
            'user_avatar' => $this->getDefaultAvatar(),
            'post_date' => date('F j, Y'),
            'caption' => strip_tags($data['title'] ?? ''),
            'preview_url' => $data['thumbnail_url'] ?? '',
            'thumbnail_url' => $data['thumbnail_url'] ?? '',
            'media' => [
                [
                    'index' => 0,
                    'type' => 'photo',
                    'preview_url' => $data['thumbnail_url'] ?? '',
                    'width' => $data['thumbnail_width'] ?? 1080,
                    'height' => $data['thumbnail_height'] ?? 1080,
                    'resolution' => ($data['thumbnail_width'] ?? 1080) . ' × ' . ($data['thumbnail_height'] ?? 1080),
                ]
            ],
            'resolution' => ($data['thumbnail_width'] ?? 1080) . ' × ' . ($data['thumbnail_height'] ?? 1080),
            'download_options' => $this->generateDownloadOptions(['type' => 'photo']),
        ];
    }

    /**
     * Parse embed page HTML
     */
    protected function parseEmbedPage(string $html, string $shortcode): ?array
    {
        // Try to extract JSON data from embed page
        $patterns = [
            '/window\.__additionalDataLoaded\s*\(\s*[\'"]extra[\'"]\s*,\s*(\{.+?\})\s*\)\s*;/s',
            '/"shortcode_media"\s*:\s*(\{.+?\})\s*,\s*"[a-z]/s',
            '/\\\\\"shortcode_media\\\\\":\s*(\{.+?\})/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $jsonStr = $matches[1];
                // Unescape if needed
                $jsonStr = str_replace(['\\\"', '\\\\'], ['"', '\\'], $jsonStr);
                $json = json_decode($jsonStr, true);
                if ($json && isset($json['shortcode'])) {
                    return $this->parseGraphQLResponse($json);
                }
            }
        }

        // Fallback: Extract basic info from HTML
        $result = [
            'success' => true,
            'shortcode' => $shortcode,
            'type' => 'post',
            'is_video' => false,
            'is_carousel' => false,
            'media' => [],
            'username' => '@unknown',
            'user_avatar' => $this->getDefaultAvatar(),
            'post_date' => date('F j, Y'),
            'caption' => '',
            'preview_url' => '',
        ];

        // Extract image URL
        $imgPatterns = [
            '/<img[^>]+class="[^"]*EmbeddedMediaImage[^"]*"[^>]+src="([^"]+)"/i',
            '/property="og:image"\s+content="([^"]+)"/i',
            '/srcset="([^"\s,]+)/i',
            '/<img[^>]+src="(https:\/\/[^"]+instagram[^"]+)"/i',
        ];

        foreach ($imgPatterns as $pattern) {
            if (preg_match($pattern, $html, $imgMatch)) {
                $result['preview_url'] = html_entity_decode($imgMatch[1]);
                $result['media'][] = [
                    'index' => 0,
                    'type' => 'photo',
                    'preview_url' => $result['preview_url'],
                    'resolution' => '1080 × 1080',
                ];
                break;
            }
        }

        // Check for video
        if (preg_match('/<video[^>]+src="([^"]+)"/i', $html, $videoMatch)) {
            $result['is_video'] = true;
            $result['type'] = 'video';
            if (!empty($result['media'])) {
                $result['media'][0]['type'] = 'video';
                $result['media'][0]['video_url'] = html_entity_decode($videoMatch[1]);
            } else {
                $result['media'][] = [
                    'index' => 0,
                    'type' => 'video',
                    'video_url' => html_entity_decode($videoMatch[1]),
                    'preview_url' => $result['preview_url'],
                ];
            }
        }

        // Extract username
        if (preg_match('/"username"\s*:\s*"([^"]+)"/', $html, $userMatch)) {
            $result['username'] = '@' . $userMatch[1];
        } elseif (preg_match('/instagram\.com\/([a-zA-Z0-9_.]+)/', $html, $userMatch)) {
            $result['username'] = '@' . $userMatch[1];
        }

        // Extract caption
        if (preg_match('/<div[^>]+class="[^"]*Caption[^"]*"[^>]*>.*?<span[^>]*>(.+?)<\/span>/is', $html, $captionMatch)) {
            $result['caption'] = strip_tags(html_entity_decode($captionMatch[1]));
        }

        $result['resolution'] = '1080 × 1080';
        $result['download_options'] = $this->generateDownloadOptions($result);

        if (!empty($result['media']) || !empty($result['preview_url'])) {
            return $result;
        }

        return null;
    }

    /**
     * Parse direct API response
     */
    protected function parseDirectResponse(array $item): array
    {
        $isVideo = isset($item['video_versions']);
        $isCarousel = isset($item['carousel_media']);
        
        $contentType = 'photo';
        if ($isVideo) {
            $contentType = ($item['product_type'] ?? '') === 'clips' ? 'reels' : 'video';
        }

        $result = [
            'success' => true,
            'shortcode' => $item['code'] ?? '',
            'type' => $contentType,
            'is_video' => $isVideo,
            'is_carousel' => $isCarousel,
            'username' => '@' . ($item['user']['username'] ?? 'unknown'),
            'user_avatar' => $item['user']['profile_pic_url'] ?? $this->getDefaultAvatar(),
            'post_date' => isset($item['taken_at']) ? date('F j, Y', $item['taken_at']) : date('F j, Y'),
            'caption' => $item['caption']['text'] ?? '',
            'likes' => $item['like_count'] ?? 0,
            'comments' => $item['comment_count'] ?? 0,
            'media' => [],
        ];

        if ($isCarousel) {
            foreach ($item['carousel_media'] as $index => $media) {
                $result['media'][] = $this->parseDirectMediaItem($media, $index);
            }
            $result['carousel_count'] = count($result['media']);
        } else {
            $result['media'][] = $this->parseDirectMediaItem($item, 0);
            $result['carousel_count'] = 0;
        }

        // Set preview and main info
        if (!empty($result['media'])) {
            $mainMedia = $result['media'][0];
            $result['preview_url'] = $mainMedia['preview_url'];
            $result['duration'] = $mainMedia['duration'] ?? null;
            $result['resolution'] = $mainMedia['resolution'] ?? '1080 × 1080';
        }

        $result['download_options'] = $this->generateDownloadOptions($result);

        return $result;
    }

    /**
     * Parse a single media item from direct API
     */
    protected function parseDirectMediaItem(array $item, int $index): array
    {
        $isVideo = isset($item['video_versions']);
        
        $info = [
            'index' => $index,
            'type' => $isVideo ? 'video' : 'photo',
        ];

        // Get best image
        $images = $item['image_versions2']['candidates'] ?? [];
        if (!empty($images)) {
            usort($images, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
            $info['preview_url'] = $images[0]['url'];
            $info['width'] = $images[0]['width'] ?? 1080;
            $info['height'] = $images[0]['height'] ?? 1080;
            $info['resolution'] = $info['width'] . ' × ' . $info['height'];
            $info['resources'] = array_map(fn($img) => [
                'src' => $img['url'],
                'width' => $img['width'] ?? 0,
                'height' => $img['height'] ?? 0,
            ], $images);
        }

        // Get video URL
        if ($isVideo) {
            $videos = $item['video_versions'] ?? [];
            if (!empty($videos)) {
                usort($videos, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
                $info['video_url'] = $videos[0]['url'];
                $info['video_versions'] = $videos;
            }
            $info['duration'] = isset($item['video_duration']) 
                ? $this->formatDuration($item['video_duration']) 
                : null;
        }

        return $info;
    }

    /**
     * Parse regular web page
     */
    protected function parseWebPage(string $html, string $shortcode): ?array
    {
        // Look for various JSON patterns in the page
        $patterns = [
            '/window\._sharedData\s*=\s*(\{.+?\});<\/script>/s',
            '/window\.__additionalDataLoaded\([^,]+,\s*(\{.+?\})\);/s',
            '/"graphql"\s*:\s*\{"shortcode_media"\s*:\s*(\{.+?\})\}/s',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $json = json_decode($matches[1], true);
                if ($json) {
                    // Try different paths to find media data
                    $media = $json['entry_data']['PostPage'][0]['graphql']['shortcode_media'] 
                        ?? $json['graphql']['shortcode_media']
                        ?? $json['shortcode_media']
                        ?? null;
                    
                    if ($media) {
                        return $this->parseGraphQLResponse($media);
                    }
                }
            }
        }

        // Try to find og:image as fallback
        if (preg_match('/property="og:image"\s+content="([^"]+)"/i', $html, $ogMatch)) {
            return [
                'success' => true,
                'shortcode' => $shortcode,
                'type' => 'post',
                'is_video' => strpos($html, 'og:video') !== false,
                'is_carousel' => false,
                'username' => '@unknown',
                'user_avatar' => $this->getDefaultAvatar(),
                'post_date' => date('F j, Y'),
                'caption' => '',
                'preview_url' => html_entity_decode($ogMatch[1]),
                'media' => [
                    [
                        'index' => 0,
                        'type' => strpos($html, 'og:video') !== false ? 'video' : 'photo',
                        'preview_url' => html_entity_decode($ogMatch[1]),
                        'resolution' => '1080 × 1080',
                    ]
                ],
                'resolution' => '1080 × 1080',
                'download_options' => $this->generateDownloadOptions(['type' => 'photo']),
            ];
        }

        return null;
    }

    /**
     * Generate download options based on content type
     */
    protected function generateDownloadOptions(array $data): array
    {
        $isVideo = $data['is_video'] ?? false;
        $type = $data['type'] ?? 'photo';

        if ($isVideo || $type === 'video' || $type === 'reels') {
            return [
                [
                    'quality' => 'hd',
                    'label' => 'HD Quality',
                    'resolution' => '1080p',
                    'format' => 'mp4',
                    'estimated_size' => $type === 'reels' ? '~12 MB' : '~15 MB',
                ],
                [
                    'quality' => 'sd',
                    'label' => 'SD Quality',
                    'resolution' => '720p',
                    'format' => 'mp4',
                    'estimated_size' => $type === 'reels' ? '~6 MB' : '~8 MB',
                ],
                [
                    'quality' => 'audio',
                    'label' => 'Audio Only',
                    'resolution' => '320kbps',
                    'format' => 'mp3',
                    'estimated_size' => '~3 MB',
                ],
            ];
        }

        return [
            [
                'format' => 'jpg',
                'label' => 'JPG Format',
                'description' => 'Original Quality • Compressed',
                'estimated_size' => '~500 KB',
            ],
            [
                'format' => 'png',
                'label' => 'PNG Format',
                'description' => 'Lossless • Best Quality',
                'estimated_size' => '~1.2 MB',
            ],
            [
                'format' => 'webp',
                'label' => 'WebP Format',
                'description' => 'Modern • Optimized',
                'estimated_size' => '~300 KB',
            ],
        ];
    }

    /**
     * Extract shortcode from Instagram URL
     */
    public function extractShortcode(string $url): string
    {
        // Match various Instagram URL formats
        $patterns = [
            '/instagram\.com\/p\/([A-Za-z0-9_-]+)/i',
            '/instagram\.com\/reel\/([A-Za-z0-9_-]+)/i',
            '/instagram\.com\/reels\/([A-Za-z0-9_-]+)/i',
            '/instagram\.com\/tv\/([A-Za-z0-9_-]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return '';
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
        return 'post';
    }

    /**
     * Check if media is a Reels
     */
    protected function isReels(array $media): bool
    {
        $productType = $media['product_type'] ?? '';
        return $productType === 'clips' || $productType === 'reels';
    }

    /**
     * Extract caption from media
     */
    protected function extractCaption(array $media): string
    {
        if (isset($media['edge_media_to_caption']['edges'][0]['node']['text'])) {
            return $media['edge_media_to_caption']['edges'][0]['node']['text'];
        }
        return $media['caption'] ?? '';
    }

    /**
     * Format duration in seconds to MM:SS
     */
    protected function formatDuration(float $seconds): string
    {
        $minutes = floor($seconds / 60);
        $secs = round($seconds % 60);
        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Get image MIME type
     */
    protected function getImageMimeType(string $format): string
    {
        return match (strtolower($format)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    /**
     * Get default avatar URL
     */
    protected function getDefaultAvatar(): string
    {
        return 'https://ui-avatars.com/api/?name=IG&background=E1306C&color=fff&size=150';
    }

    /**
     * Validate Instagram URL
     */
    public function isValidUrl(string $url): bool
    {
        return (bool) preg_match(
            '/^https?:\/\/(www\.)?instagram\.com\/(p|reel|reels|tv)\/[A-Za-z0-9_-]+/',
            $url
        );
    }
}