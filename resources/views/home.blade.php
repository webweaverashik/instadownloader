@extends('layouts.app')

@section('theme-styles')
    :root {
    --primary-start: #833ab4;
    --primary-middle: #fd1d1d;
    --primary-end: #fcb045;
    }

    .video-gradient { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .reels-gradient { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .photo-gradient { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); }

    .tool-card {
    position: relative;
    overflow: hidden;
    }
    .tool-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    }
    .tool-card.video::before { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .tool-card.reels::before { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .tool-card.photo::before { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); }
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="gradient-bg py-16 lg:py-28">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <div
                class="inline-flex items-center gap-2 bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium mb-6">
                <i class="fas fa-star"></i>
                <span>Free Instagram Downloader - No Login Required</span>
            </div>

            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                Download Instagram<br>
                <span class="text-yellow-200">Videos, Reels & Photos</span>
            </h1>
            <p class="text-lg md:text-xl text-white/90 mb-10 max-w-2xl mx-auto">
                The fastest and most reliable Instagram downloader. Save content in HD quality with just one click.
                Completely free, forever.
            </p>

            <!-- Quick Download Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-3xl mx-auto mb-8">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-link absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="instagram-url" placeholder="Paste any Instagram URL here..."
                            class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl text-lg input-glow focus:border-purple-500 focus:outline-none transition-all">
                    </div>
                    <button id="download-btn"
                        class="gradient-bg text-white font-semibold px-8 py-4 rounded-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 pulse-animation">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </button>
                </div>

                <div id="url-type-indicator" class="mt-4 text-sm text-gray-500 hidden">
                    <span id="type-badge" class="inline-flex items-center gap-2 px-3 py-1 rounded-full">
                        <i class="fas fa-check-circle"></i>
                        <span id="detected-type">Content detected</span>
                    </span>
                </div>

                <!-- Loading State -->
                <div id="loading-state" class="hidden mt-6">
                    <div class="flex flex-col items-center gap-4">
                        <div class="loader"></div>
                        <p class="text-gray-600">Analyzing URL...</p>
                    </div>
                </div>

                <!-- Error State -->
                <div id="error-state" class="hidden mt-6">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="error-message">Invalid URL. Please enter a valid Instagram URL.</span>
                    </div>
                </div>
            </div>

            <!-- Supported Types -->
            <div class="flex flex-wrap justify-center gap-4">
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-video mr-2"></i>Videos
                </span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-film mr-2"></i>Reels
                </span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-image mr-2"></i>Photos
                </span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-images mr-2"></i>Carousel
                </span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm">
                    <i class="fas fa-tv mr-2"></i>IGTV
                </span>
            </div>
        </div>
    </section>

    <!-- Tools Section -->
    <section class="py-20 bg-white" id="tools">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <span class="inline-block bg-purple-100 text-purple-700 px-4 py-1 rounded-full text-sm font-medium mb-4">Our
                    Tools</span>
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4">Choose Your Downloader</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Select the right tool for the content you want to
                    download.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Video Downloader Card -->
                <a href="{{ route('video.downloader') }}"
                    class="tool-card video card-hover bg-white rounded-2xl shadow-lg p-8 block">
                    <div class="w-16 h-16 video-gradient rounded-2xl flex items-center justify-center mb-6 floating">
                        <i class="fas fa-video text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Video Downloader</h3>
                    <p class="text-gray-600 mb-6">Download Instagram videos and IGTV in HD quality. Choose between different
                        quality options.</p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-purple-500"></i><span>HD & SD Quality</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-purple-500"></i><span>Extract Audio</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-purple-500"></i><span>IGTV Support</span></li>
                    </ul>
                    <span class="inline-flex items-center gap-2 text-purple-600 font-semibold">Download Videos <i
                            class="fas fa-arrow-right"></i></span>
                </a>

                <!-- Reels Downloader Card -->
                <a href="{{ route('reels.downloader') }}"
                    class="tool-card reels card-hover bg-white rounded-2xl shadow-lg p-8 block">
                    <div class="w-16 h-16 reels-gradient rounded-2xl flex items-center justify-center mb-6 floating"
                        style="animation-delay: 0.5s;">
                        <i class="fas fa-film text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Reels Downloader</h3>
                    <p class="text-gray-600 mb-6">Save trending Instagram Reels with audio. Download in full HD quality.</p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-pink-500"></i><span>Full HD 1080x1920</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-pink-500"></i><span>With Audio</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-pink-500"></i><span>Trending Audio</span></li>
                    </ul>
                    <span class="inline-flex items-center gap-2 text-pink-600 font-semibold">Download Reels <i
                            class="fas fa-arrow-right"></i></span>
                </a>

                <!-- Photo Downloader Card -->
                <a href="{{ route('photo.downloader') }}"
                    class="tool-card photo card-hover bg-white rounded-2xl shadow-lg p-8 block">
                    <div class="w-16 h-16 photo-gradient rounded-2xl flex items-center justify-center mb-6 floating"
                        style="animation-delay: 1s;">
                        <i class="fas fa-image text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Photo Downloader</h3>
                    <p class="text-gray-600 mb-6">Download Instagram photos in original quality. Multiple formats available.
                    </p>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-orange-500"></i><span>Original Resolution</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-orange-500"></i><span>JPG, PNG, WebP</span></li>
                        <li class="flex items-center gap-2 text-gray-600"><i
                                class="fas fa-check text-orange-500"></i><span>Carousel Support</span></li>
                    </ul>
                    <span class="inline-flex items-center gap-2 text-orange-600 font-semibold">Download Photos <i
                            class="fas fa-arrow-right"></i></span>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <span
                    class="inline-block bg-green-100 text-green-700 px-4 py-1 rounded-full text-sm font-medium mb-4">Features</span>
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4">Why Choose InstaDownloader?</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach ([['icon' => 'fa-bolt', 'title' => 'Lightning Fast', 'desc' => 'Download content in seconds.'], ['icon' => 'fa-hd', 'title' => 'HD Quality', 'desc' => 'Highest available quality.'], ['icon' => 'fa-lock', 'title' => 'No Login Required', 'desc' => 'No Instagram account needed.'], ['icon' => 'fa-infinity', 'title' => 'Unlimited Downloads', 'desc' => 'No daily limits.'], ['icon' => 'fa-mobile-alt', 'title' => 'All Devices', 'desc' => 'Works everywhere.'], ['icon' => 'fa-shield-alt', 'title' => '100% Safe', 'desc' => 'No malware, no ads.'], ['icon' => 'fa-ban', 'title' => 'No Watermark', 'desc' => 'Clean downloads.'], ['icon' => 'fa-clock', 'title' => '24/7 Available', 'desc' => 'Always online.']] as $feature)
                    <div class="card-hover bg-white rounded-2xl p-6 text-center shadow-sm">
                        <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas {{ $feature['icon'] }} text-white text-2xl"></i>
                        </div>
                        <h3 class="font-bold text-gray-900 mb-2 text-lg">{{ $feature['title'] }}</h3>
                        <p class="text-gray-600">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <span class="inline-block bg-blue-100 text-blue-700 px-4 py-1 rounded-full text-sm font-medium mb-4">Simple
                    Steps</span>
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4">How It Works</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                <div
                    class="hidden md:block absolute top-24 left-1/4 right-1/4 h-0.5 bg-gradient-to-r from-purple-300 via-pink-300 to-orange-300">
                </div>

                @foreach ([['num' => '1', 'title' => 'Copy the Link', 'desc' => 'Open Instagram and copy the link.'], ['num' => '2', 'title' => 'Paste the Link', 'desc' => 'Paste in the input field and click Download.'], ['num' => '3', 'title' => 'Download & Enjoy', 'desc' => 'Choose quality and download instantly.']] as $step)
                    <div class="text-center relative z-10">
                        <div
                            class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold shadow-lg">
                            {{ $step['num'] }}</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $step['title'] }}</h3>
                        <p class="text-gray-600">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center mb-16">
                <span
                    class="inline-block bg-yellow-100 text-yellow-700 px-4 py-1 rounded-full text-sm font-medium mb-4">FAQ</span>
                <h2 class="text-3xl md:text-5xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h2>
            </div>

            <div class="space-y-4">
                @foreach ([['q' => 'Is InstaDownloader completely free?', 'a' => 'Yes, InstaDownloader is completely free. No hidden charges or subscriptions.'], ['q' => 'Do I need to log in to my Instagram account?', 'a' => 'No, you don\'t need to log in. Just paste the link.'], ['q' => 'What types of content can I download?', 'a' => 'You can download videos, IGTV, Reels, photos, and carousel albums.'], ['q' => 'What quality options are available?', 'a' => 'HD (1080p), SD (720p), and audio-only (MP3) for videos. JPG, PNG, WebP for photos.'], ['q' => 'Can I download from private accounts?', 'a' => 'No, our tool only works with public Instagram content.']] as $faq)
                    <div class="faq-item bg-white rounded-xl shadow-sm">
                        <button class="faq-question w-full flex items-center justify-between p-6 text-left">
                            <span class="font-semibold text-gray-900">{{ $faq['q'] }}</span>
                            <i class="fas fa-chevron-down text-gray-500 transition-transform"></i>
                        </button>
                        <div class="faq-answer hidden px-6 pb-6">
                            <p class="text-gray-600">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 gradient-bg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <p class="text-4xl md:text-5xl font-bold text-white mb-2">10M+</p>
                    <p class="text-white/80">Downloads</p>
                </div>
                <div>
                    <p class="text-4xl md:text-5xl font-bold text-white mb-2">500K+</p>
                    <p class="text-white/80">Happy Users</p>
                </div>
                <div>
                    <p class="text-4xl md:text-5xl font-bold text-white mb-2">99.9%</p>
                    <p class="text-white/80">Uptime</p>
                </div>
                <div>
                    <p class="text-4xl md:text-5xl font-bold text-white mb-2">24/7</p>
                    <p class="text-white/80">Available</p>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        const urlInput = document.getElementById('instagram-url');
        const downloadBtn = document.getElementById('download-btn');
        const urlTypeIndicator = document.getElementById('url-type-indicator');
        const typeBadge = document.getElementById('type-badge');
        const detectedTypeSpan = document.getElementById('detected-type');
        const loadingState = document.getElementById('loading-state');
        const errorState = document.getElementById('error-state');
        const errorMessage = document.getElementById('error-message');

        urlInput.addEventListener('input', (e) => {
            const url = e.target.value.trim();
            errorState.classList.add('hidden');

            if (url.includes('instagram.com')) {
                const detected = detectContentType(url);
                if (detected) {
                    urlTypeIndicator.classList.remove('hidden');
                    typeBadge.className = 'inline-flex items-center gap-2 px-3 py-1 rounded-full ' + detected.color;
                    detectedTypeSpan.textContent = detected.label;
                } else {
                    urlTypeIndicator.classList.add('hidden');
                }
            } else {
                urlTypeIndicator.classList.add('hidden');
            }
        });

        downloadBtn.addEventListener('click', async () => {
            const url = urlInput.value.trim();

            errorState.classList.add('hidden');

            if (!url) {
                errorMessage.textContent = 'Please enter an Instagram URL';
                errorState.classList.remove('hidden');
                return;
            }

            if (!isValidInstagramUrl(url)) {
                errorMessage.textContent = 'Invalid URL. Please enter a valid Instagram URL.';
                errorState.classList.remove('hidden');
                return;
            }

            loadingState.classList.remove('hidden');
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';

            const detected = detectContentType(url);

            setTimeout(() => {
                loadingState.classList.add('hidden');
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download';

                let redirectUrl = '{{ route('video.downloader') }}';
                if (detected) {
                    if (detected.type === 'reels') {
                        redirectUrl = '{{ route('reels.downloader') }}';
                    } else if (detected.type === 'post') {
                        redirectUrl = '{{ route('photo.downloader') }}';
                    }
                }

                window.location.href = redirectUrl + '?url=' + encodeURIComponent(url);
            }, 1500);
        });
    </script>
@endsection
