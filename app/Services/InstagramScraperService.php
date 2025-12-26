<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramScraperService
{
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Analyze Instagram URL and extract content information
     */
    public function analyzeUrl(string $url): array
    {
        $url       = $this->cleanInstagramUrl($url);
        $shortcode = $this->extractShortcode($url);

        if (empty($shortcode)) {
            throw new Exception('Invalid Instagram URL. Could not extract shortcode.');
        }

        $cacheKey = 'instagram_v3_' . md5($shortcode);

        // Clear cache for testing - remove in production
        // Cache::forget($cacheKey);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $data   = null;
        $errors = [];

        // Method 1: Try the direct API endpoint (best for getting video URLs)
        try {
            $data = $this->fetchViaDirectApi($shortcode);
            if ($data && ! empty($data['media'])) {
                Log::info('Fetched via Direct API', ['shortcode' => $shortcode]);
            }
        } catch (Exception $e) {
            $errors[] = 'DirectAPI: ' . $e->getMessage();
            Log::debug('Direct API failed: ' . $e->getMessage());
            $data = null;
        }

        // Method 2: Try embed page with better video extraction
        if (! $data || empty($data['media'][0]['video_url'])) {
            try {
                $embedData = $this->fetchViaEmbed($shortcode);
                if ($embedData) {
                    // Merge video URL if we got it
                    if ($data && ! empty($embedData['video_url'])) {
                        $data['media'][0]['video_url'] = $embedData['video_url'];
                    } elseif ($embedData) {
                        $data = $embedData;
                    }
                    Log::info('Fetched via Embed', ['shortcode' => $shortcode]);
                }
            } catch (Exception $e) {
                $errors[] = 'Embed: ' . $e->getMessage();
                Log::debug('Embed failed: ' . $e->getMessage());
            }
        }

        // Method 3: Try scraping the main page for video URL
        if (! $data || ($data['is_video'] && empty($data['media'][0]['video_url']))) {
            try {
                $pageData = $this->fetchViaPageScrape($url, $shortcode);
                if ($pageData) {
                    if ($data && ! empty($pageData['video_url'])) {
                        $data['media'][0]['video_url'] = $pageData['video_url'];
                    } elseif (! $data) {
                        $data = $pageData;
                    }
                    Log::info('Fetched via Page Scrape', ['shortcode' => $shortcode]);
                }
            } catch (Exception $e) {
                $errors[] = 'PageScrape: ' . $e->getMessage();
                Log::debug('Page scrape failed: ' . $e->getMessage());
            }
        }

        if (! $data) {
            Log::error('All Instagram fetch methods failed', ['errors' => $errors, 'url' => $url]);
            throw new Exception('Unable to fetch Instagram content. The post may be private or deleted.');
        }

        // Log what we got for debugging
        Log::info('Final data', [
            'shortcode'         => $shortcode,
            'is_video'          => $data['is_video'] ?? false,
            'has_video_url'     => ! empty($data['media'][0]['video_url'] ?? null),
            'video_url_preview' => substr($data['media'][0]['video_url'] ?? 'none', 0, 100),
        ]);

        // Cache for 30 minutes
        Cache::put($cacheKey, $data, 1800);

        return $data;
    }

    /**
     * Fetch via Instagram's direct API endpoint
     */
    protected function fetchViaDirectApi(string $shortcode): ?array
    {
        // Use the Instagram API endpoint
        $url = "https://www.instagram.com/api/v1/media/{$shortcode}/info/";

        $response = $this->makeRequest($url, [
            'X-IG-App-ID'      => '936619743392459',
            'X-ASBD-ID'        => '129477',
            'X-IG-WWW-Claim'   => '0',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        if (! $response) {
            // Try alternate endpoint
            $url      = "https://www.instagram.com/p/{$shortcode}/?__a=1&__d=dis";
            $response = $this->makeRequest($url, [
                'X-IG-App-ID'      => '936619743392459',
                'X-Requested-With' => 'XMLHttpRequest',
            ]);
        }

        if (! $response) {
            return null;
        }

        $json = json_decode($response, true);
        if (! $json) {
            return null;
        }

        // Handle different response formats
        if (isset($json['items'][0])) {
            return $this->parseItemsFormat($json['items'][0], $shortcode);
        }

        if (isset($json['graphql']['shortcode_media'])) {
            return $this->parseGraphQLFormat($json['graphql']['shortcode_media']);
        }

        return null;
    }

    /**
     * Parse the items format from Instagram API
     */
    protected function parseItemsFormat(array $item, string $shortcode): array
    {
        $mediaType   = $item['media_type'] ?? 1;
        $productType = $item['product_type'] ?? '';

        $isVideo    = $mediaType == 2 || isset($item['video_versions']);
        $isCarousel = $mediaType == 8 || isset($item['carousel_media']);

        $contentType = 'photo';
        if ($isVideo) {
            $contentType = ($productType === 'clips' || $productType === 'reels') ? 'reels' : 'video';
        }

        $user       = $item['user'] ?? [];
        $username   = $user['username'] ?? 'unknown';
        $userAvatar = $user['profile_pic_url'] ?? $this->getDefaultAvatar($username);

        $caption = '';
        if (isset($item['caption']['text'])) {
            $caption = $item['caption']['text'];
        }

        $timestamp = $item['taken_at'] ?? time();

        $media = [];

        if ($isCarousel && isset($item['carousel_media'])) {
            foreach ($item['carousel_media'] as $index => $carouselItem) {
                $media[] = $this->extractMediaInfo($carouselItem, $index);
            }
        } else {
            $media[] = $this->extractMediaInfo($item, 0);
        }

        $result = [
            'success'        => true,
            'shortcode'      => $shortcode,
            'type'           => $contentType,
            'is_video'       => $isVideo,
            'is_carousel'    => $isCarousel,
            'carousel_count' => $isCarousel ? count($media) : 0,
            'username'       => '@' . $username,
            'user_full_name' => $user['full_name'] ?? $username,
            'user_avatar'    => $userAvatar,
            'post_date'      => date('F j, Y', $timestamp),
            'timestamp'      => $timestamp,
            'caption'        => $caption,
            'likes'          => $item['like_count'] ?? 0,
            'comments'       => $item['comment_count'] ?? 0,
            'post_url'       => "https://www.instagram.com/p/{$shortcode}/",
            'media'       => $media,
            'preview_url' => $media[0]['preview_url'] ?? '',
            'duration'    => $media[0]['duration'] ?? null,
            'resolution'  => $media[0]['resolution'] ?? '1080 × 1080',
        ];

        $result['download_options'] = $this->generateDownloadOptions($result);

        return $result;
    }

    /**
     * Extract media info from an item
     */
    protected function extractMediaInfo(array $item, int $index): array
    {
        $mediaType = $item['media_type'] ?? 1;
        $isVideo   = $mediaType == 2 || isset($item['video_versions']);

        $media = [
            'index'       => $index,
            'type'        => $isVideo ? 'video' : 'photo',
            'preview_url' => '',
            'video_url'   => null,
            'width'       => 0,
            'height'      => 0,
            'resolution'  => '1080 × 1080',
            'resources'   => [],
        ];

        // Get preview image - try multiple sources
        $images = $item['image_versions2']['candidates'] ?? [];
        if (! empty($images)) {
            usort($images, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
            $media['preview_url'] = $images[0]['url'] ?? '';
            $media['width']       = $images[0]['width'] ?? 1080;
            $media['height']      = $images[0]['height'] ?? 1080;
            $media['resolution']  = $media['width'] . ' × ' . $media['height'];

            $media['resources'] = array_map(fn($img) => [
                'src'    => $img['url'] ?? '',
                'width'  => $img['width'] ?? 0,
                'height' => $img['height'] ?? 0,
            ], $images);
        }

        // Get video URL - THIS IS CRITICAL
        if ($isVideo && isset($item['video_versions']) && ! empty($item['video_versions'])) {
            $videos = $item['video_versions'];

            // Sort by quality (width) - highest first
            usort($videos, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));

            // Get the best quality video URL
            $media['video_url']    = $videos[0]['url'] ?? null;
            $media['video_width']  = $videos[0]['width'] ?? 0;
            $media['video_height'] = $videos[0]['height'] ?? 0;

            // Store all versions for quality selection
            $media['video_versions'] = array_map(fn($v) => [
                'url'    => $v['url'] ?? '',
                'width'  => $v['width'] ?? 0,
                'height' => $v['height'] ?? 0,
                'type'   => $v['type'] ?? 0,
            ], $videos);

            Log::info('Extracted video URL', [
                'url_length'     => strlen($media['video_url'] ?? ''),
                'versions_count' => count($videos),
            ]);
        }

        // Get duration for videos
        if (isset($item['video_duration'])) {
            $media['duration']         = $this->formatDuration($item['video_duration']);
            $media['duration_seconds'] = $item['video_duration'];
        }

        if (isset($item['view_count'])) {
            $media['view_count'] = $item['view_count'];
        } elseif (isset($item['play_count'])) {
            $media['view_count'] = $item['play_count'];
        }

        return $media;
    }

    /**
     * Fetch via embed page - good for getting video URLs
     */
    protected function fetchViaEmbed(string $shortcode): ?array
    {
        $embedUrl = "https://www.instagram.com/p/{$shortcode}/embed/";

        $html = $this->makeRequest($embedUrl);
        if (! $html) {
            return null;
        }

        $result = [
            'video_url'   => null,
            'preview_url' => null,
        ];

        // Extract video URL from embed page - multiple patterns
        $videoPatterns = [
            '/"video_url"\s*:\s*"([^"]+)"/',
            '/video_url["\']?\s*:\s*["\']([^"\']+)["\']/',
            '/class="[^"]*EmbeddedMediaVideo[^"]*"[^>]*src="([^"]+)"/',
            '/<video[^>]+src="([^"]+)"/',
            '/property="og:video"[^>]+content="([^"]+)"/',
            '/property="og:video:url"[^>]+content="([^"]+)"/',
            '/property="og:video:secure_url"[^>]+content="([^"]+)"/',
        ];

        foreach ($videoPatterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $videoUrl = $this->unescapeUrl($matches[1]);
                if ($this->isValidVideoUrl($videoUrl)) {
                    $result['video_url'] = $videoUrl;
                    Log::info('Found video URL via embed pattern', ['pattern' => $pattern]);
                    break;
                }
            }
        }

        // Extract preview image
        if (preg_match('/class="[^"]*EmbeddedMediaImage[^"]*"[^>]+src="([^"]+)"/', $html, $m)) {
            $result['preview_url'] = html_entity_decode($m[1]);
        } elseif (preg_match('/property="og:image"[^>]+content="([^"]+)"/', $html, $m)) {
            $result['preview_url'] = html_entity_decode($m[1]);
        }

        // Try to find JSON data in the page
        if (preg_match('/window\.__additionalDataLoaded\s*\([\'"][^\'"]+[\'"]\s*,\s*(\{.+?\})\s*\)\s*;/s', $html, $jsonMatch)) {
            $json = json_decode($jsonMatch[1], true);
            if ($json && isset($json['shortcode_media'])) {
                $media = $json['shortcode_media'];
                if (isset($media['video_url']) && $this->isValidVideoUrl($media['video_url'])) {
                    $result['video_url'] = $media['video_url'];
                }
            }
        }

        return $result;
    }

    /**
     * Fetch via page scraping
     */
    protected function fetchViaPageScrape(string $url, string $shortcode): ?array
    {
        $html = $this->makeRequest($url);
        if (! $html) {
            return null;
        }

        $result = [
            'video_url' => null,
        ];

        // Look for video URL in page source
        $patterns = [
            '/"video_url"\s*:\s*"([^"]+)"/',
            '/contentUrl["\']?\s*:\s*["\']([^"\']+\.mp4[^"\']*)["\']/',
            '/"playback_video_url"\s*:\s*"([^"]+)"/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $videoUrl = $this->unescapeUrl($matches[1]);
                if ($this->isValidVideoUrl($videoUrl)) {
                    $result['video_url'] = $videoUrl;
                    Log::info('Found video URL via page scrape', ['pattern' => $pattern]);
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Parse GraphQL format response
     */
    protected function parseGraphQLFormat(array $media): array
    {
        $isVideo    = $media['is_video'] ?? false;
        $typename   = $media['__typename'] ?? '';
        $isCarousel = $typename === 'GraphSidecar';

        $contentType = 'photo';
        if ($isVideo) {
            $productType = $media['product_type'] ?? '';
            $contentType = ($productType === 'clips' || $productType === 'reels') ? 'reels' : 'video';
        }

        $owner      = $media['owner'] ?? [];
        $username   = $owner['username'] ?? 'unknown';
        $userAvatar = $owner['profile_pic_url'] ?? $this->getDefaultAvatar($username);

        $caption = '';
        if (isset($media['edge_media_to_caption']['edges'][0]['node']['text'])) {
            $caption = $media['edge_media_to_caption']['edges'][0]['node']['text'];
        }

        $timestamp = $media['taken_at_timestamp'] ?? time();
        $shortcode = $media['shortcode'] ?? '';

        $mediaList = [];

        if ($isCarousel && isset($media['edge_sidecar_to_children']['edges'])) {
            foreach ($media['edge_sidecar_to_children']['edges'] as $index => $edge) {
                $mediaList[] = $this->extractGraphQLMedia($edge['node'], $index);
            }
        } else {
            $mediaList[] = $this->extractGraphQLMedia($media, 0);
        }

        $result = [
            'success'        => true,
            'shortcode'      => $shortcode,
            'type'           => $contentType,
            'is_video'       => $isVideo,
            'is_carousel'    => $isCarousel,
            'carousel_count' => $isCarousel ? count($mediaList) : 0,
            'username'       => '@' . $username,
            'user_full_name' => $owner['full_name'] ?? $username,
            'user_avatar'    => $userAvatar,
            'post_date'      => date('F j, Y', $timestamp),
            'timestamp'      => $timestamp,
            'caption'        => $caption,
            'likes'          => $media['edge_media_preview_like']['count'] ?? 0,
            'comments'       => $media['edge_media_to_comment']['count'] ?? 0,
            'post_url'       => "https://www.instagram.com/p/{$shortcode}/",
            'media'       => $mediaList,
            'preview_url' => $media['display_url'] ?? '',
            'duration'    => $mediaList[0]['duration'] ?? null,
            'resolution'  => $mediaList[0]['resolution'] ?? '1080 × 1080',
        ];

        $result['download_options'] = $this->generateDownloadOptions($result);

        return $result;
    }

    /**
     * Extract media from GraphQL node
     */
    protected function extractGraphQLMedia(array $node, int $index): array
    {
        $isVideo = $node['is_video'] ?? false;

        $width  = $node['dimensions']['width'] ?? 1080;
        $height = $node['dimensions']['height'] ?? 1080;

        $media = [
            'index'       => $index,
            'type'        => $isVideo ? 'video' : 'photo',
            'preview_url' => $node['display_url'] ?? '',
            'video_url'   => null,
            'width'       => $width,
            'height'      => $height,
            'resolution'  => "{$width} × {$height}",
            'resources' => [],
        ];

        // Get video URL directly from GraphQL response
        if ($isVideo && isset($node['video_url'])) {
            $media['video_url']  = $node['video_url'];
            $media['view_count'] = $node['video_view_count'] ?? 0;

            if (isset($node['video_duration'])) {
                $media['duration']         = $this->formatDuration($node['video_duration']);
                $media['duration_seconds'] = $node['video_duration'];
            }
        }

        // Get display resources
        if (isset($node['display_resources'])) {
            $media['resources'] = array_map(fn($r) => [
                'src'    => $r['src'],
                'width'  => $r['config_width'],
                'height' => $r['config_height'],
            ], $node['display_resources']);
        }

        return $media;
    }

    /**
     * Make HTTP request with proper headers
     */
    protected function makeRequest(string $url, array $extraHeaders = []): ?string
    {
        $headers = array_merge([
            'User-Agent'      => $this->userAgent,
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/json',
            'Accept-Language' => 'en-US,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate',
            'Connection'      => 'keep-alive',
            'Sec-Fetch-Dest'  => 'document',
            'Sec-Fetch-Mode'  => 'navigate',
            'Sec-Fetch-Site'  => 'none',
            'Sec-Fetch-User'  => '?1',
            'Cache-Control'   => 'max-age=0',
            'Referer'         => 'https://www.instagram.com/',
        ], $extraHeaders);

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify'          => false,
                    'timeout'         => 30,
                    'connect_timeout' => 10,
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            Log::debug('Request failed', ['url' => $url, 'status' => $response->status()]);
            return null;
        } catch (Exception $e) {
            Log::debug('Request exception', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if URL is a valid video URL
     */
    protected function isValidVideoUrl(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Must contain video indicators
        $videoIndicators = ['.mp4', 'video', '/v/', 'cdninstagram.com'];
        foreach ($videoIndicators as $indicator) {
            if (stripos($url, $indicator) !== false) {
                // Must NOT be an image
                if (stripos($url, '.jpg') === false && stripos($url, '.png') === false && stripos($url, '.webp') === false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clean Instagram URL
     */
    protected function cleanInstagramUrl(string $url): string
    {
        // Remove query parameters
        $url = preg_replace('/\?.*$/', '', $url);

        // Normalize reels URLs
        $url = preg_replace('/instagram\.com\/reels?\//i', 'instagram.com/reel/', $url);

        // Ensure proper format
        if (! preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }

        return rtrim($url, '/') . '/';
    }

    /**
     * Extract shortcode from URL
     */
    public function extractShortcode(string $url): string
    {
        if (preg_match('/instagram\.com\/(?:p|reel|reels|tv)\/([A-Za-z0-9_-]+)/i', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Validate Instagram URL
     */
    public function isValidUrl(string $url): bool
    {
        return (bool) preg_match(
            '/^https?:\/\/(www\.)?instagram\.com\/(p|reel|reels|tv)\/[A-Za-z0-9_-]+/i',
            $url
        );
    }

    /**
     * Get video URL by quality
     */
    public function getVideoUrl(array $media, string $quality = 'hd'): ?string
    {
        // First check for video_versions array
        if (isset($media['video_versions']) && is_array($media['video_versions']) && ! empty($media['video_versions'])) {
            $versions = $media['video_versions'];

            if ($quality === 'sd') {
                // Get lowest quality
                usort($versions, fn($a, $b) => ($a['width'] ?? 0) - ($b['width'] ?? 0));
            } else {
                // Get highest quality (HD)
                usort($versions, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
            }

            return $versions[0]['url'] ?? null;
        }

        // Fallback to direct video_url
        return $media['video_url'] ?? null;
    }

    /**
     * Format duration
     */
    protected function formatDuration(float $seconds): string
    {
        $mins = floor($seconds / 60);
        $secs = round($seconds % 60);
        return sprintf('%d:%02d', $mins, $secs);
    }

    /**
     * Unescape URL
     */
    protected function unescapeUrl(string $url): string
    {
        $url = stripslashes($url);
        $url = str_replace(['\u0026', '\\u0026', '\u002F', '\\u002F'], ['&', '&', '/', '/'], $url);
        $url = html_entity_decode($url);
        $url = urldecode($url);
        return $url;
    }

    /**
     * Get default avatar
     */
    protected function getDefaultAvatar(string $username): string
    {
        $name = ltrim($username, '@');
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=E1306C&color=fff&size=150&bold=true";
    }

    /**
     * Generate download options
     */
    protected function generateDownloadOptions(array $data): array
    {
        $isVideo = $data['is_video'] ?? false;
        $type    = $data['type'] ?? 'photo';

        if ($isVideo || $type === 'video' || $type === 'reels') {
            return [
                [
                    'quality'        => 'hd',
                    'label'          => 'HD Quality',
                    'resolution'     => '1080p',
                    'format'         => 'mp4',
                    'estimated_size' => $type === 'reels' ? '~12 MB' : '~15 MB',
                ],
                [
                    'quality'        => 'sd',
                    'label'          => 'SD Quality',
                    'resolution'     => '720p',
                    'format'         => 'mp4',
                    'estimated_size' => $type === 'reels' ? '~6 MB' : '~8 MB',
                ],
                [
                    'quality'        => 'audio',
                    'label'          => 'Audio Only',
                    'resolution'     => '320kbps',
                    'format'         => 'mp3',
                    'estimated_size' => '~3 MB',
                ],
            ];
        }

        return [
            [
                'format'         => 'jpg',
                'label'          => 'JPG Format',
                'description'    => 'Original Quality',
                'estimated_size' => '~500 KB',
            ],
            [
                'format'         => 'png',
                'label'          => 'PNG Format',
                'description'    => 'Lossless Quality',
                'estimated_size' => '~1.2 MB',
            ],
            [
                'format'         => 'webp',
                'label'          => 'WebP Format',
                'description'    => 'Optimized',
                'estimated_size' => '~300 KB',
            ],
        ];
    }
}
