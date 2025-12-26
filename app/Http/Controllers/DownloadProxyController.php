<?php

namespace App\Http\Controllers;

use App\Services\InstagramScraperService;
use App\Models\DownloadHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class DownloadProxyController extends Controller
{
    protected InstagramScraperService $scraper;

    public function __construct(InstagramScraperService $scraper)
    {
        $this->scraper = $scraper;
    }

    /**
     * Proxy download - fetches from Instagram and streams to user
     */
    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'media_url' => 'required|url',
            'filename' => 'required|string',
            'type' => 'required|in:video,image,audio',
            'format' => 'nullable|string',
            'quality' => 'nullable|string',
        ]);

        $mediaUrl = $request->input('media_url');
        $filename = $request->input('filename');
        $type = $request->input('type');
        $format = $request->input('format', 'mp4');
        $quality = $request->input('quality', 'hd');

        try {
            // Log the download
            DownloadHistory::create([
                'instagram_url' => $request->input('url'),
                'content_type' => $type === 'image' ? 'photo' : ($type === 'audio' ? 'video' : $type),
                'quality' => $quality,
                'format' => $format,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Determine content type
            $contentType = $this->getContentType($type, $format);
            
            // Stream the file
            return $this->streamDownload($mediaUrl, $filename, $contentType);

        } catch (Exception $e) {
            Log::error('Download failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Download failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Direct download endpoint - analyzes URL and returns download link
     */
    public function directDownload(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'quality' => 'nullable|in:hd,sd,audio',
            'format' => 'nullable|in:jpg,png,webp,mp4,mp3',
            'media_index' => 'nullable|integer|min:0',
        ]);

        $url = $request->input('url');
        $quality = $request->input('quality', 'hd');
        $format = $request->input('format');
        $mediaIndex = $request->input('media_index', 0);

        try {
            // Analyze the URL
            $data = $this->scraper->analyzeUrl($url);

            if (!$data || empty($data['media'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not fetch media from this URL'
                ], 404);
            }

            // Get the specific media item
            $media = $data['media'][$mediaIndex] ?? $data['media'][0];
            $isVideo = $media['type'] === 'video';

            // Determine the download URL and filename
            if ($isVideo) {
                $downloadUrl = $media['video_url'] ?? null;
                $ext = ($format === 'mp3' || $quality === 'audio') ? 'mp3' : 'mp4';
                $filename = 'instagram_' . ($data['type'] ?? 'video') . '_' . $data['shortcode'] . '.' . $ext;
                $type = ($format === 'mp3' || $quality === 'audio') ? 'audio' : 'video';
            } else {
                $downloadUrl = $media['preview_url'] ?? null;
                
                // Get highest quality resource
                if (isset($media['resources']) && !empty($media['resources'])) {
                    $resources = $media['resources'];
                    usort($resources, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
                    $downloadUrl = $resources[0]['src'] ?? $downloadUrl;
                }
                
                $ext = $format ?: 'jpg';
                $filename = 'instagram_photo_' . $data['shortcode'] . '_' . $mediaIndex . '.' . $ext;
                $type = 'image';
            }

            if (!$downloadUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Download URL not available'
                ], 404);
            }

            // Log the download
            DownloadHistory::create([
                'instagram_url' => $url,
                'content_type' => $data['type'] ?? 'post',
                'quality' => $quality,
                'format' => $ext,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Return download info or stream directly
            if ($request->has('stream')) {
                return $this->streamDownload($downloadUrl, $filename, $this->getContentType($type, $ext));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'download_url' => $downloadUrl,
                    'proxy_url' => route('download.proxy') . '?' . http_build_query([
                        'url' => $url,
                        'media_url' => $downloadUrl,
                        'filename' => $filename,
                        'type' => $type,
                        'format' => $ext,
                        'quality' => $quality,
                    ]),
                    'filename' => $filename,
                    'type' => $type,
                    'format' => $ext,
                    'quality' => $quality,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Direct download failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process download: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk download for carousel posts
     */
    public function bulkDownload(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'format' => 'nullable|in:jpg,png,webp',
        ]);

        $url = $request->input('url');
        $format = $request->input('format', 'jpg');

        try {
            $data = $this->scraper->analyzeUrl($url);

            if (!$data || empty($data['media'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not fetch media from this URL'
                ], 404);
            }

            $downloads = [];
            foreach ($data['media'] as $index => $media) {
                $isVideo = $media['type'] === 'video';
                
                if ($isVideo) {
                    $downloadUrl = $media['video_url'] ?? null;
                    $filename = 'instagram_video_' . $data['shortcode'] . '_' . $index . '.mp4';
                    $type = 'video';
                    $ext = 'mp4';
                } else {
                    $downloadUrl = $media['preview_url'] ?? null;
                    if (isset($media['resources']) && !empty($media['resources'])) {
                        $resources = $media['resources'];
                        usort($resources, fn($a, $b) => ($b['width'] ?? 0) - ($a['width'] ?? 0));
                        $downloadUrl = $resources[0]['src'] ?? $downloadUrl;
                    }
                    $filename = 'instagram_photo_' . $data['shortcode'] . '_' . $index . '.' . $format;
                    $type = 'image';
                    $ext = $format;
                }

                if ($downloadUrl) {
                    $downloads[] = [
                        'index' => $index,
                        'download_url' => $downloadUrl,
                        'proxy_url' => route('download.proxy') . '?' . http_build_query([
                            'url' => $url,
                            'media_url' => $downloadUrl,
                            'filename' => $filename,
                            'type' => $type,
                            'format' => $ext,
                            'quality' => 'hd',
                        ]),
                        'filename' => $filename,
                        'type' => $type,
                        'format' => $ext,
                    ];
                }
            }

            // Log the download
            DownloadHistory::create([
                'instagram_url' => $url,
                'content_type' => 'carousel',
                'quality' => 'hd',
                'format' => $format,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => count($downloads),
                    'downloads' => $downloads,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Bulk download failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk download: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stream download to user
     */
    protected function streamDownload(string $url, string $filename, string $contentType): StreamedResponse
    {
        return response()->stream(function () use ($url) {
            $handle = fopen($url, 'rb');
            
            if ($handle === false) {
                throw new Exception('Could not open URL for streaming');
            }

            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            
            fclose($handle);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Alternative stream using cURL for better handling
     */
    protected function streamDownloadCurl(string $url, string $filename, string $contentType): StreamedResponse
    {
        return response()->stream(function () use ($url) {
            $ch = curl_init($url);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ],
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;
                    flush();
                    return strlen($data);
                },
            ]);
            
            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache',
        ]);
    }

    /**
     * Get content type based on type and format
     */
    protected function getContentType(string $type, string $format): string
    {
        if ($type === 'video') {
            return 'video/mp4';
        }
        
        if ($type === 'audio') {
            return 'audio/mpeg';
        }

        return match (strtolower($format)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            default => 'image/jpeg',
        };
    }

    /**
     * Get file size from URL (for display purposes)
     */
    public function getFileSize(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $response = Http::head($request->input('url'));
            $size = $response->header('Content-Length');
            
            return response()->json([
                'success' => true,
                'size' => $size ? (int) $size : null,
                'formatted_size' => $size ? $this->formatBytes((int) $size) : 'Unknown',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not determine file size',
            ]);
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
