@extends('layouts.app')

@section('theme-styles')
    :root {
    --primary-start: #f093fb;
    --primary-middle: #f5576c;
    --primary-end: #ff6b6b;
    }

    .theme-light-bg { background-color: #fef2f2; }
    .theme-text { color: #f5576c; }
    .feature-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .reels-phone { background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); }
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="gradient-bg py-16 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div
                class="inline-flex items-center gap-2 bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium mb-6">
                <i class="fas fa-film"></i>
                <span>Instagram Reels Downloader</span>
            </div>

            <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">Download Instagram Reels in HD</h1>
            <p class="text-lg md:text-xl text-white/90 mb-8">Save trending Instagram Reels with audio. Download in full HD
                quality, fast and free.</p>

            <!-- Main Download Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-3xl mx-auto">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-link absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="instagram-url" value="{{ $prefilledUrl ?? '' }}"
                            placeholder="Paste Instagram Reels URL here..."
                            class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl text-lg input-glow focus:border-pink-500 focus:outline-none transition-all">
                    </div>
                    <button id="download-btn"
                        class="gradient-btn text-white font-semibold px-8 py-4 rounded-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 pulse-animation">
                        <i class="fas fa-search"></i>
                        <span>Analyze</span>
                    </button>
                </div>

                <div id="url-type-indicator" class="mt-4 text-sm hidden">
                    <span class="inline-flex items-center gap-2 bg-pink-100 text-pink-700 px-3 py-1 rounded-full">
                        <i class="fas fa-check-circle"></i>
                        <span id="detected-type">Reels detected</span>
                    </span>
                </div>

                <div id="loading-state" class="hidden mt-6">
                    <div class="flex flex-col items-center gap-4">
                        <div class="loader"></div>
                        <p class="text-gray-600">Fetching reels content...</p>
                    </div>
                </div>

                <div id="error-state" class="hidden mt-6">
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span id="error-message">Invalid URL.</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-4 mt-8">
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>Trending Reels</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>With Audio</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>Full HD 1080p</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>No Watermark</span>
            </div>
        </div>
    </section>

    <!-- Preview Result Section -->
    <section id="preview-section" class="hidden py-12 bg-white">
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-gray-50 rounded-2xl p-6 md:p-8 fade-in">
                <div class="flex items-center gap-4 mb-6">
                    <img id="user-avatar" src="" alt="User Avatar"
                        class="w-12 h-12 rounded-full object-cover border-2 border-pink-200">
                    <div>
                        <h3 id="username" class="font-semibold text-gray-900">@username</h3>
                        <p id="post-date" class="text-sm text-gray-500">Posted on Jan 1, 2024</p>
                    </div>
                    <a id="post-link" href="#" target="_blank" class="ml-auto theme-text hover:opacity-80 text-sm"><i
                            class="fas fa-external-link-alt mr-1"></i> View Reels</a>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="relative flex justify-center">
                        <div class="relative w-64">
                            <div class="reels-phone rounded-3xl p-2 shadow-2xl">
                                <div class="bg-black rounded-2xl overflow-hidden aspect-[9/16] relative">
                                    <img id="preview-image" src="" alt="Preview"
                                        class="w-full h-full object-cover">
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div
                                            class="w-14 h-14 bg-white/30 rounded-full flex items-center justify-center backdrop-blur-sm">
                                            <i class="fas fa-play text-white text-lg ml-1"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span id="duration-badge"
                                class="absolute bottom-6 right-6 bg-black/70 text-white px-3 py-1 rounded-full text-sm"><i
                                    class="fas fa-clock mr-1"></i> <span id="duration-text">0:30</span></span>
                            <span
                                class="absolute top-6 left-6 bg-gradient-to-r from-pink-500 to-red-500 text-white px-3 py-1 rounded-full text-xs font-medium"><i
                                    class="fas fa-music mr-1"></i> Audio</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">Download Options</h4>
                        <div class="space-y-3">
                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-pink-500 hover:bg-pink-50 transition-all group"
                                data-quality="hd" data-format="mp4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center group-hover:bg-pink-200 transition-all">
                                        <i class="fas fa-video text-pink-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">HD Quality</p>
                                        <p class="text-sm text-gray-500">1080x1920 • MP4</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500 size-estimate">~12 MB</span>
                                    <i class="fas fa-download theme-text"></i>
                                </div>
                            </button>

                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all group"
                                data-quality="sd" data-format="mp4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-all">
                                        <i class="fas fa-video text-purple-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">SD Quality</p>
                                        <p class="text-sm text-gray-500">720x1280 • MP4</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500 size-estimate">~6 MB</span>
                                    <i class="fas fa-download text-purple-600"></i>
                                </div>
                            </button>

                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all group"
                                data-quality="audio" data-format="mp3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-all">
                                        <i class="fas fa-music text-green-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">Audio Only</p>
                                        <p class="text-sm text-gray-500">MP3 • 320kbps</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500 size-estimate">~2 MB</span>
                                    <i class="fas fa-download text-green-600"></i>
                                </div>
                            </button>
                        </div>

                        <!-- Stats -->
                        <div class="mt-4 flex gap-4 text-sm text-gray-500">
                            <span><i class="fas fa-heart text-red-500 mr-1"></i> <span id="likes-count">0</span>
                                likes</span>
                            <span><i class="fas fa-comment text-blue-500 mr-1"></i> <span id="comments-count">0</span>
                                comments</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-white rounded-xl border border-gray-200">
                    <p class="text-sm text-gray-600 font-medium mb-2">Caption:</p>
                    <p id="post-caption" class="text-gray-800 text-sm line-clamp-3">Caption will appear here...</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Why Choose Our Reels Downloader?</h2>
            </div>
            <div class="grid md:grid-cols-4 gap-8">
                @foreach ([['icon' => 'fa-mobile-alt', 'title' => 'Full HD 1080p', 'desc' => 'Original vertical format'], ['icon' => 'fa-music', 'title' => 'With Audio', 'desc' => 'Keep original sound'], ['icon' => 'fa-fire', 'title' => 'Trending Content', 'desc' => 'Save viral reels'], ['icon' => 'fa-bolt', 'title' => 'Super Fast', 'desc' => 'Download in seconds']] as $f)
                    <div class="card-hover bg-gray-50 rounded-2xl p-6 text-center">
                        <div class="w-16 h-16 feature-icon rounded-2xl flex items-center justify-center mx-auto mb-4"><i
                                class="fas {{ $f['icon'] }} text-white text-2xl"></i></div>
                        <h3 class="font-semibold text-gray-900 mb-2">{{ $f['title'] }}</h3>
                        <p class="text-gray-600 text-sm">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 theme-light-bg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How to Download Instagram Reels</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                @foreach ([['num' => '1', 'title' => 'Find the Reels', 'desc' => 'Open Instagram, find the Reels, tap ⋯ and copy link'], ['num' => '2', 'title' => 'Paste & Analyze', 'desc' => 'Paste the link above and click Analyze button'], ['num' => '3', 'title' => 'Download & Save', 'desc' => 'Select quality and download to your device']] as $step)
                    <div class="text-center bg-white rounded-2xl p-8 shadow-sm">
                        <div
                            class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4 text-white text-2xl font-bold">
                            {{ $step['num'] }}</div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-gray-600 text-sm">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Frequently Asked Questions</h2>
            </div>
            <div class="space-y-4">
                @foreach ([['q' => 'How do I copy an Instagram Reels link?', 'a' => 'Open the Instagram app, go to the Reels you want, tap the three dots (⋯) and select "Copy Link".'], ['q' => 'Can I download Reels with trending audio?', 'a' => 'Yes! We preserve the original audio including trending sounds. You can also extract audio only as MP3.'], ['q' => 'What\'s the maximum Reels length supported?', 'a' => 'We support all Reels lengths from 15 seconds up to 90 seconds.'], ['q' => 'Is the quality preserved?', 'a' => 'Yes! We download in the original quality - Full HD 1080x1920 for the best experience.']] as $faq)
                    <div class="faq-item bg-gray-50 rounded-xl shadow-sm">
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

    <!-- Other Tools -->
    <section class="py-16 theme-light-bg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900">Other Instagram Tools</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <a href="{{ route('video.downloader') }}"
                    class="card-hover block bg-white rounded-2xl p-8 border border-purple-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-purple-500 to-blue-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-video text-white text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Video Downloader</h3>
                            <p class="text-gray-600 text-sm">Download feed videos & IGTV</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-2 text-purple-600 font-medium">Try Now <i
                            class="fas fa-arrow-right"></i></span>
                </a>
                <a href="{{ route('photo.downloader') }}"
                    class="card-hover block bg-white rounded-2xl p-8 border border-orange-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-image text-white text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Photo Downloader</h3>
                            <p class="text-gray-600 text-sm">Download HD photos</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-2 text-orange-600 font-medium">Try Now <i
                            class="fas fa-arrow-right"></i></span>
                </a>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        let currentData = null;

        const urlInput = document.getElementById('instagram-url');
        const downloadBtn = document.getElementById('download-btn');
        const previewSection = document.getElementById('preview-section');
        const loadingState = document.getElementById('loading-state');
        const errorState = document.getElementById('error-state');
        const errorMessage = document.getElementById('error-message');
        const urlTypeIndicator = document.getElementById('url-type-indicator');
        const detectedType = document.getElementById('detected-type');

        urlInput.addEventListener('input', (e) => {
            const url = e.target.value.trim();
            errorState.classList.add('hidden');

            if (url.includes('instagram.com')) {
                const type = detectContentType(url);
                if (type) {
                    urlTypeIndicator.classList.remove('hidden');
                    detectedType.textContent = type.label;
                } else {
                    urlTypeIndicator.classList.add('hidden');
                }
            } else {
                urlTypeIndicator.classList.add('hidden');
            }
        });

        if (urlInput.value.trim()) {
            setTimeout(() => downloadBtn.click(), 500);
        }

        downloadBtn.addEventListener('click', async () => {
            const url = urlInput.value.trim();

            errorState.classList.add('hidden');
            previewSection.classList.add('hidden');

            if (!url) {
                showError('Please enter an Instagram URL');
                return;
            }

            if (!isValidInstagramUrl(url)) {
                showError('Please enter a valid Instagram Reels URL');
                return;
            }

            loadingState.classList.remove('hidden');
            downloadBtn.disabled = true;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analyzing...';

            try {
                const result = await apiRequest('{{ route('api.analyze') }}', {
                    url
                });

                if (result.success && result.data) {
                    currentData = result.data;
                    showPreview(result.data);
                } else {
                    showError(result.message || 'Failed to analyze URL');
                }
            } catch (error) {
                showError(error.message || 'Failed to analyze URL. Please try again.');
            } finally {
                loadingState.classList.add('hidden');
                downloadBtn.disabled = false;
                downloadBtn.innerHTML = '<i class="fas fa-search"></i> Analyze';
            }
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorState.classList.remove('hidden');
        }

        function showPreview(data) {
            document.getElementById('user-avatar').src = data.user_avatar ||
                'https://ui-avatars.com/api/?name=IG&background=f5576c&color=fff';
            document.getElementById('username').textContent = data.username || '@unknown';
            document.getElementById('post-date').textContent = 'Posted on ' + (data.post_date || 'Unknown date');
            document.getElementById('preview-image').src = data.preview_url || data.media?.[0]?.preview_url || '';
            document.getElementById('post-caption').textContent = data.caption || 'No caption';
            document.getElementById('post-link').href = 'https://instagram.com/reel/' + data.shortcode;

            if (data.duration || data.media?.[0]?.duration) {
                document.getElementById('duration-text').textContent = data.duration || data.media[0].duration;
            }

            document.getElementById('likes-count').textContent = formatNumber(data.likes || 0);
            document.getElementById('comments-count').textContent = formatNumber(data.comments || 0);

            if (data.download_options) {
                const sizeElements = document.querySelectorAll('.size-estimate');
                data.download_options.forEach((opt, i) => {
                    if (sizeElements[i]) {
                        sizeElements[i].textContent = opt.estimated_size || '~10 MB';
                    }
                });
            }

            previewSection.classList.remove('hidden');
            previewSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        document.querySelectorAll('.download-option').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!currentData) {
                    showError('Please analyze a URL first');
                    return;
                }

                const quality = this.dataset.quality;
                const format = this.dataset.format;

                const originalContent = this.innerHTML;
                this.innerHTML =
                    '<div class="flex items-center justify-center gap-2 w-full"><i class="fas fa-spinner fa-spin"></i> Preparing...</div>';
                this.disabled = true;

                try {
                    const result = await apiRequest('{{ route('api.download.url') }}', {
                        url: urlInput.value.trim(),
                        quality: quality,
                        format: format,
                        media_index: 0
                    });

                    if (result.success && result.data) {
                        downloadFile(result.data.proxy_url || result.data.download_url, result.data
                            .filename);
                    } else {
                        showError(result.message || 'Failed to get download URL');
                    }
                } catch (error) {
                    showError(error.message || 'Download failed. Please try again.');
                } finally {
                    this.innerHTML = originalContent;
                    this.disabled = false;
                }
            });
        });

        function downloadFile(url, filename) {
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'instagram_reel';
            a.target = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        function formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        }
    </script>
@endsection
