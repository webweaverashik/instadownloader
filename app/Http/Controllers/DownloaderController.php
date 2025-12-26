<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DownloaderController extends Controller
{
    /**
     * Display the homepage
     */
    public function index(): View
    {
        $meta = [
            'title' => 'InstaDownloader - Free Instagram Video, Reels & Photo Downloader',
            'description' => 'Free Instagram Downloader - Download Instagram Videos, Reels, and Photos in HD quality. Fast, free, and easy to use. No login required.',
            'keywords' => 'instagram downloader, instagram video downloader, instagram reels downloader, instagram photo downloader, download instagram',
            'canonical' => url('/'),
            'og_type' => 'website',
        ];

        return view('home', compact('meta'));
    }

    /**
     * Display the Video Downloader page
     */
    public function video(Request $request): View
    {
        $meta = [
            'title' => 'Instagram Video Downloader - Download Instagram Videos in HD Quality Free',
            'description' => 'Free Instagram Video Downloader - Download Instagram videos in HD and SD quality. Fast, free, and easy to use. No login required.',
            'keywords' => 'instagram video downloader, download instagram video, instagram video download, save instagram video, instagram video saver',
            'canonical' => url('/instagram-video-downloader'),
            'og_type' => 'website',
        ];

        $prefilledUrl = $request->query('url', '');

        return view('downloaders.video', compact('meta', 'prefilledUrl'));
    }

    /**
     * Display the Reels Downloader page
     */
    public function reels(Request $request): View
    {
        $meta = [
            'title' => 'Instagram Reels Downloader - Download Reels in HD Quality Free',
            'description' => 'Free Instagram Reels Downloader - Download Instagram Reels in HD quality. Save trending reels with audio. Fast, free, and easy to use.',
            'keywords' => 'instagram reels downloader, download instagram reels, instagram reels download, save instagram reels, reels saver',
            'canonical' => url('/instagram-reels-downloader'),
            'og_type' => 'website',
        ];

        $prefilledUrl = $request->query('url', '');

        return view('downloaders.reels', compact('meta', 'prefilledUrl'));
    }

    /**
     * Display the Photo Downloader page
     */
    public function photo(Request $request): View
    {
        $meta = [
            'title' => 'Instagram Photo Downloader - Download Instagram Photos in HD Free',
            'description' => 'Free Instagram Photo Downloader - Download Instagram photos in original quality. Save images in JPG, PNG, or WebP format. Fast, free, and easy to use.',
            'keywords' => 'instagram photo downloader, download instagram photo, instagram image download, save instagram photo, instagram picture saver',
            'canonical' => url('/instagram-photo-downloader'),
            'og_type' => 'website',
        ];

        $prefilledUrl = $request->query('url', '');

        return view('downloaders.photo', compact('meta', 'prefilledUrl'));
    }
}