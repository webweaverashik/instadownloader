<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstagramScraperService;
use App\Models\DownloadHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Exception;

class InstagramController extends Controller
{
    protected InstagramScraperService $scraper;

    public function __construct(InstagramScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Analyze Instagram URL and return content information
     */
    public function analyze(Request $request): JsonResponse
    {
        $key = 'analyze:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 30)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please try again in {$seconds} seconds.",
            ], 429);
        }
        
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'url' => [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    if (!$this->scraper->isValidUrl($value)) {
                        $fail('Please provide a valid Instagram post, reel, or video URL.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $url = $request->input('url');
            $contentData = $this->scraper->analyzeUrl($url);

            if (!$contentData || !isset($contentData['success']) || !$contentData['success']) {
                throw new Exception('Failed to fetch content data');
            }

            // Log for debugging
            Log::info('Analyze successful', [
                'shortcode' => $contentData['shortcode'] ?? 'unknown',
                'type' => $contentData['type'] ?? 'unknown',
                'has_video' => $contentData['is_video'] ?? false,
                'media_count' => count($contentData['media'] ?? []),
                'first_media_has_video_url' => !empty($contentData['media'][0]['video_url'] ?? null),
            ]);

            return response()->json([
                'success' => true,
                'data' => $contentData
            ]);

        } catch (Exception $e) {
            Log::error('Analyze failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to analyze URL. The post may be private or deleted.',
            ], 500);
        }
    }

    /**
     * Get download URL for specific quality/format
     */
    public function getDownloadUrl(Request $request): JsonResponse
    {
        $key = 'download:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many requests. Please try again in {$seconds} seconds.",
            ], 429);
        }
        
        RateLimiter::hit($key, 60);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'quality' => 'nullable|in:hd,sd,audio',
            'format' => 'nullable|in:jpg,png,webp,mp4,mp3',
            'media_index' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $url = $request->input('url');
            $quality = $request->input('quality', 'hd');
            $format = $request->input('format');
            $mediaIndex = $request->input('media_index', 0);

            $data = $this->scraper->analyzeUrl($url);

            if (!$data || empty($data['media'])) {
                throw new Exception('Could not fetch media from this URL');
            }

            $media = $data['media'][$mediaIndex] ?? $data['media'][0];
            $isVideo = $media['type'] === 'video';

            // Get the download URL
            if ($isVideo) {
                // Get video URL based on quality
                $downloadUrl = $this->scraper->getVideoUrl($media, $quality);
                
                // Log what we're getting
                Log::info('Getting video download URL', [
                    'quality' => $quality,
                    'has_video_versions' => isset($media['video_versions']),
                    'direct_video_url' => isset($media['video_url']),
                    'result_url_length' => strlen($downloadUrl ?? ''),
                ]);
                
                if (!$downloadUrl) {
                    // Check if we have the video URL in the right place
                    throw new Exception('Video URL not available. The video may be protected or the post is private.');
                }
                
                $ext = ($format === 'mp3' || $quality === 'audio') ? 'mp3' : 'mp4';
                $filename = 'instagram_' . ($data['type'] ?? 'video') . '_' . $data['shortcode'] . '.' . $ext;
                $type = ($format === 'mp3' || $quality === 'audio') ? 'audio' : 'video';
                $mimeType = $type === 'audio' ? 'audio/mpeg' : 'video/mp4';
            } else {
                // Get highest quality image
                $downloadUrl = $media['preview_url'] ?? null;
                
                if (isset($media['resources']) && !empty($media['resources'])) {
                    $resources = $media['resources'];
                    usort($resources, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
                    $downloadUrl = $resources[0]['src'] ?? $downloadUrl;
                }
                
                if (!$downloadUrl) {
                    throw new Exception('Image URL not available.');
                }
                
                $ext = $format ?: 'jpg';
                $filename = 'instagram_photo_' . $data['shortcode'] . '_' . ($mediaIndex + 1) . '.' . $ext;
                $type = 'image';
                $mimeType = $this->getImageMimeType($ext);
            }

            // Log the download request
            DownloadHistory::create([
                'instagram_url' => $url,
                'content_type' => $data['type'] ?? 'post',
                'quality' => $quality,
                'format' => $ext,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Build proxy URL for safe download
            $proxyUrl = route('download.proxy') . '?' . http_build_query([
                'media_url' => $downloadUrl,
                'filename' => $filename,
                'type' => $type,
            ]);

            Log::info('Download URL prepared', [
                'type' => $type,
                'filename' => $filename,
                'url_length' => strlen($downloadUrl),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $downloadUrl,
                    'proxy_url' => $proxyUrl,
                    'filename' => $filename,
                    'type' => $type,
                    'format' => $ext,
                    'quality' => $quality,
                    'mime_type' => $mimeType,
                    'resolution' => $media['resolution'] ?? null,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Download URL failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to get download URL.',
            ], 500);
        }
    }

    /**
     * Get all download URLs for carousel
     */
    public function getCarouselDownloads(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'format' => 'nullable|in:jpg,png,webp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $url = $request->input('url');
            $format = $request->input('format', 'jpg');

            $data = $this->scraper->analyzeUrl($url);

            if (!$data || empty($data['media'])) {
                throw new Exception('Could not fetch media from this URL');
            }

            $downloads = [];
            foreach ($data['media'] as $index => $media) {
                $isVideo = $media['type'] === 'video';
                
                if ($isVideo) {
                    $downloadUrl = $this->scraper->getVideoUrl($media, 'hd');
                    $filename = 'instagram_video_' . $data['shortcode'] . '_' . ($index + 1) . '.mp4';
                    $type = 'video';
                    $ext = 'mp4';
                } else {
                    $downloadUrl = $media['preview_url'] ?? null;
                    if (isset($media['resources']) && !empty($media['resources'])) {
                        $resources = $media['resources'];
                        usort($resources, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
                        $downloadUrl = $resources[0]['src'] ?? $downloadUrl;
                    }
                    $filename = 'instagram_photo_' . $data['shortcode'] . '_' . ($index + 1) . '.' . $format;
                    $type = 'image';
                    $ext = $format;
                }

                if ($downloadUrl) {
                    $downloads[] = [
                        'index' => $index,
                        'type' => $type,
                        'download_url' => $downloadUrl,
                        'proxy_url' => route('download.proxy') . '?' . http_build_query([
                            'media_url' => $downloadUrl,
                            'filename' => $filename,
                            'type' => $type,
                        ]),
                        'filename' => $filename,
                        'format' => $ext,
                        'resolution' => $media['resolution'] ?? null,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'shortcode' => $data['shortcode'],
                    'total' => count($downloads),
                    'downloads' => $downloads,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Carousel downloads failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to get carousel downloads.',
            ], 500);
        }
    }

    /**
     * Get download history
     */
    public function history(Request $request): JsonResponse
    {
        $history = DownloadHistory::where('ip_address', $request->ip())
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'url' => $item->instagram_url,
                    'type' => $item->content_type,
                    'quality' => $item->quality,
                    'format' => $item->format,
                    'downloaded_at' => $item->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Get download statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_downloads' => DownloadHistory::count(),
            'today_downloads' => DownloadHistory::whereDate('created_at', today())->count(),
            'by_type' => DownloadHistory::getCountByType(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
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
}