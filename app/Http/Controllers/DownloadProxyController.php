<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class DownloadProxyController extends Controller
{
    /**
     * Proxy download - fetches from Instagram and streams to user
     */
    public function download(Request $request)
    {
        $mediaUrl = $request->query('media_url');
        $filename = $request->query('filename', 'instagram_download');
        $type = $request->query('type', 'video');

        if (empty($mediaUrl)) {
            return response()->json(['error' => 'No media URL provided'], 400);
        }

        Log::info('Download proxy request', [
            'filename' => $filename,
            'type' => $type,
            'url_length' => strlen($mediaUrl),
            'url_start' => substr($mediaUrl, 0, 100),
        ]);

        try {
            // Determine content type
            $contentType = $this->getContentType($type, pathinfo($filename, PATHINFO_EXTENSION));
            
            // Stream the file using cURL
            return $this->streamWithCurl($mediaUrl, $filename, $contentType);

        } catch (Exception $e) {
            Log::error('Download proxy failed: ' . $e->getMessage());
            
            // Fallback: redirect to direct URL
            return redirect()->away($mediaUrl);
        }
    }

    /**
     * Stream download using cURL - handles Instagram's CDN properly
     */
    protected function streamWithCurl(string $url, string $filename, string $contentType): StreamedResponse
    {
        // First, get file info (size and final URL after redirects)
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: */*',
                'Accept-Encoding: identity',
                'Referer: https://www.instagram.com/',
                'Origin: https://www.instagram.com',
            ],
        ]);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        curl_close($ch);

        Log::info('File info retrieved', [
            'http_code' => $httpCode,
            'file_size' => $fileSize,
            'final_url_length' => strlen($finalUrl),
            'error' => $error,
        ]);

        if ($httpCode >= 400) {
            throw new Exception("Failed to access file: HTTP $httpCode");
        }
        
        // Use final URL (after redirects)
        $downloadUrl = $finalUrl ?: $url;

        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Accel-Buffering' => 'no',
        ];
        
        if ($fileSize > 0) {
            $headers['Content-Length'] = (int) $fileSize;
        }

        return response()->stream(function () use ($downloadUrl) {
            // Disable output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $ch = curl_init($downloadUrl);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0, // No timeout for large files
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_BUFFERSIZE => 16384,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept: */*',
                    'Accept-Encoding: identity',
                    'Referer: https://www.instagram.com/',
                    'Origin: https://www.instagram.com',
                ],
                CURLOPT_WRITEFUNCTION => function ($ch, $data) {
                    echo $data;
                    flush();
                    return strlen($data);
                },
            ]);
            
            $result = curl_exec($ch);
            
            if (curl_errno($ch)) {
                Log::error('cURL streaming error: ' . curl_error($ch));
            }
            
            curl_close($ch);
        }, 200, $headers);
    }

    /**
     * Get content type based on type and format
     */
    protected function getContentType(string $type, string $extension): string
    {
        if ($type === 'video' || $extension === 'mp4') {
            return 'video/mp4';
        }
        
        if ($type === 'audio' || $extension === 'mp3') {
            return 'audio/mpeg';
        }

        return match (strtolower($extension)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            default => 'image/jpeg',
        };
    }
}