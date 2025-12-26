<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloaderController;
use App\Http\Controllers\DownloadProxyController;
use App\Http\Controllers\Api\InstagramController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage
Route::get('/', [DownloaderController::class, 'index'])->name('home');

// Downloader Pages (SEO-friendly slugs)
Route::get('/instagram-video-downloader', [DownloaderController::class, 'video'])->name('video.downloader');
Route::get('/instagram-reels-downloader', [DownloaderController::class, 'reels'])->name('reels.downloader');
Route::get('/instagram-photo-downloader', [DownloaderController::class, 'photo'])->name('photo.downloader');

// Download Proxy Routes (for streaming downloads)
Route::get('/download', [DownloadProxyController::class, 'download'])->name('download.proxy');
Route::post('/download/direct', [DownloadProxyController::class, 'directDownload'])->name('download.direct');
Route::post('/download/bulk', [DownloadProxyController::class, 'bulkDownload'])->name('download.bulk');
Route::post('/download/filesize', [DownloadProxyController::class, 'getFileSize'])->name('download.filesize');

// API Routes for AJAX calls
Route::prefix('api')->middleware(['throttle:60,1'])->group(function () {
    // Analyze Instagram URL
    Route::post('/analyze', [InstagramController::class, 'analyze'])->name('api.analyze');
    
    // Get download URL
    Route::post('/download-url', [InstagramController::class, 'getDownloadUrl'])->name('api.download.url');
    
    // Get carousel downloads
    Route::post('/carousel-downloads', [InstagramController::class, 'getCarouselDownloads'])->name('api.carousel.downloads');
    
    // Download history
    Route::get('/history', [InstagramController::class, 'history'])->name('api.history');
    
    // Stats (optional - for admin)
    Route::get('/stats', [InstagramController::class, 'stats'])->name('api.stats');
});

// Legal Pages
Route::view('/privacy-policy', 'pages.privacy')->name('privacy');
Route::view('/terms-of-service', 'pages.terms')->name('terms');
Route::view('/dmca', 'pages.dmca')->name('dmca');
Route::view('/contact', 'pages.contact')->name('contact');

// Fallback for 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});