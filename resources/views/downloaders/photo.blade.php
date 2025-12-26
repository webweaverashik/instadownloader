@extends('layouts.app')

@section('theme-styles')
    :root {
    --primary-start: #f6d365;
    --primary-middle: #fda085;
    --primary-end: #f093fb;
    }

    .theme-light-bg { background-color: #fffbeb; }
    .theme-text { color: #f97316; }
    .feature-icon { background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); }
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="gradient-bg py-16 lg:py-24">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <div
                class="inline-flex items-center gap-2 bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium mb-6">
                <i class="fas fa-image"></i>
                <span>Instagram Photo Downloader</span>
            </div>

            <h1 class="text-3xl md:text-5xl font-bold text-white mb-4">Download Instagram Photos in Original Quality</h1>
            <p class="text-lg md:text-xl text-white/90 mb-8">Save Instagram photos in JPG, PNG, or WebP format. Download
                single photos or entire carousel albums.</p>

            <!-- Main Download Form -->
            <div class="bg-white rounded-2xl shadow-2xl p-6 md:p-8 max-w-3xl mx-auto">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <i class="fas fa-link absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="instagram-url" value="{{ $prefilledUrl ?? '' }}"
                            placeholder="Paste Instagram photo URL here..."
                            class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl text-lg input-glow focus:border-orange-500 focus:outline-none transition-all">
                    </div>
                    <button id="download-btn"
                        class="gradient-btn text-white font-semibold px-8 py-4 rounded-xl hover:opacity-90 transition-all flex items-center justify-center gap-2 pulse-animation">
                        <i class="fas fa-search"></i>
                        <span>Analyze</span>
                    </button>
                </div>

                <div id="url-type-indicator" class="mt-4 text-sm hidden">
                    <span class="inline-flex items-center gap-2 bg-orange-100 text-orange-700 px-3 py-1 rounded-full">
                        <i class="fas fa-check-circle"></i>
                        <span id="detected-type">Photo detected</span>
                    </span>
                </div>

                <div id="loading-state" class="hidden mt-6">
                    <div class="flex flex-col items-center gap-4">
                        <div class="loader"></div>
                        <p class="text-gray-600">Fetching photo content...</p>
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
                        class="fas fa-check mr-2"></i>Single Photos</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>Carousel Albums</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>Original Quality</span>
                <span class="bg-white/20 text-white px-4 py-2 rounded-full text-sm font-medium"><i
                        class="fas fa-check mr-2"></i>Multiple Formats</span>
            </div>
        </div>
    </section>

    <!-- Preview Result Section -->
    <section id="preview-section" class="hidden py-12 bg-white">
        <div class="max-w-5xl mx-auto px-4">
            <div class="bg-gray-50 rounded-2xl p-6 md:p-8 fade-in">
                <div class="flex items-center gap-4 mb-6">
                    <img id="user-avatar" src="" alt="User Avatar"
                        class="w-12 h-12 rounded-full object-cover border-2 border-orange-200">
                    <div>
                        <h3 id="username" class="font-semibold text-gray-900">@username</h3>
                        <p id="post-date" class="text-sm text-gray-500">Posted on Jan 1, 2024</p>
                    </div>
                    <a id="post-link" href="#" target="_blank" class="ml-auto theme-text hover:opacity-80 text-sm"><i
                            class="fas fa-external-link-alt mr-1"></i> View Post</a>
                </div>

                <!-- Carousel Preview (when multiple images) -->
                <div id="carousel-preview" class="hidden mb-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-900"><i class="fas fa-images mr-2 text-orange-500"></i>Carousel
                            Album (<span id="carousel-count">0</span> items)</h4>
                        <button id="download-all-btn"
                            class="gradient-btn text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-all">
                            <i class="fas fa-download mr-1"></i> Download All
                        </button>
                    </div>
                    <div id="carousel-thumbnails" class="flex gap-3 overflow-x-auto pb-3">
                        <!-- Thumbnails will be inserted here -->
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="relative">
                        <div id="media-preview" class="rounded-xl overflow-hidden bg-gray-200 aspect-square">
                            <img id="preview-image" src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <span id="media-type-badge"
                            class="absolute top-3 left-3 bg-black/70 text-white px-3 py-1 rounded-full text-sm">
                            <i class="fas fa-image mr-1"></i> <span id="media-type-text">Photo</span>
                        </span>
                        <div id="carousel-nav" class="hidden absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                            <!-- Dots will be inserted here -->
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-4">Download Options</h4>

                        <!-- Resolution Info -->
                        <div
                            class="bg-gradient-to-r from-orange-50 to-yellow-50 rounded-xl p-4 mb-4 border border-orange-100">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center"><i
                                        class="fas fa-expand-arrows-alt theme-text"></i></div>
                                <div>
                                    <p class="text-sm text-gray-600">Resolution</p>
                                    <p id="image-resolution" class="font-semibold text-gray-900">1080 × 1350</p>
                                </div>
                            </div>
                        </div>

                        <div id="image-options" class="space-y-3">
                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-orange-500 hover:bg-orange-50 transition-all group"
                                data-format="jpg">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition-all">
                                        <i class="fas fa-file-image text-orange-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">JPG Format</p>
                                        <p class="text-sm text-gray-500">Original • Compressed</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500">~500 KB</span>
                                    <i class="fas fa-download theme-text"></i>
                                </div>
                            </button>

                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all group"
                                data-format="png">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-all">
                                        <i class="fas fa-file-image text-blue-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">PNG Format</p>
                                        <p class="text-sm text-gray-500">Lossless • Best Quality</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500">~1.2 MB</span>
                                    <i class="fas fa-download text-blue-600"></i>
                                </div>
                            </button>

                            <button
                                class="download-option w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all group"
                                data-format="webp">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-all">
                                        <i class="fas fa-file-image text-green-600"></i>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-medium text-gray-900">WebP Format</p>
                                        <p class="text-sm text-gray-500">Modern • Optimized</p>
                                    </div>
                                </div>
                                <div class="text-right flex items-center gap-2">
                                    <span class="text-sm text-gray-500">~300 KB</span>
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
                <h2 class="text-3xl font-bold text-gray-900">Why Choose Our Photo Downloader?</h2>
            </div>
            <div class="grid md:grid-cols-4 gap-8">
                @foreach ([['icon' => 'fa-expand', 'title' => 'Original Resolution', 'desc' => 'Full quality download'], ['icon' => 'fa-file-alt', 'title' => 'Multiple Formats', 'desc' => 'JPG, PNG, WebP'], ['icon' => 'fa-images', 'title' => 'Carousel Support', 'desc' => 'Download all at once'], ['icon' => 'fa-info-circle', 'title' => 'Resolution Preview', 'desc' => 'See dimensions first']] as $f)
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

    <!-- Format Comparison -->
    <section class="py-16 theme-light-bg">
        <div class="max-w-5xl mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Choose Your Format</h2>
                <p class="text-gray-600">Compare different image formats to pick the best one</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div
                    class="bg-white rounded-2xl p-6 shadow-sm border-2 border-transparent hover:border-orange-300 transition-all">
                    <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mb-4"><span
                            class="font-bold text-orange-600">JPG</span></div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">JPG Format</h3>
                    <p class="text-gray-600 text-sm mb-4">Best for sharing. Good balance between quality and size.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Smaller size</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Universal support
                        </li>
                        <li class="flex items-center gap-2"><i class="fas fa-times text-red-500"></i> Lossy compression
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border-2 border-orange-400 relative">
                    <span
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full">RECOMMENDED</span>
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-4"><span
                            class="font-bold text-blue-600">PNG</span></div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">PNG Format</h3>
                    <p class="text-gray-600 text-sm mb-4">Best quality with no compression loss.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Lossless quality
                        </li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Transparency
                            support</li>
                        <li class="flex items-center gap-2"><i class="fas fa-times text-red-500"></i> Larger size</li>
                    </ul>
                </div>

                <div
                    class="bg-white rounded-2xl p-6 shadow-sm border-2 border-transparent hover:border-orange-300 transition-all">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-4"><span
                            class="font-bold text-green-600">WebP</span></div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">WebP Format</h3>
                    <p class="text-gray-600 text-sm mb-4">Modern format with excellent compression.</p>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Smallest size</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Great quality</li>
                        <li class="flex items-center gap-2"><i class="fas fa-times text-red-500"></i> Limited support</li>
                    </ul>
                </div>
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
                @foreach ([['q' => 'What\'s the best format for downloading photos?', 'a' => 'PNG for best quality (lossless), JPG for smaller files, WebP for modern web use with great compression.'], ['q' => 'Can I download all photos from a carousel post?', 'a' => 'Yes! We detect carousels automatically and let you download all images at once or individually.'], ['q' => 'What resolution will the downloaded photos be?', 'a' => 'Original resolution as uploaded. Typically up to 1080x1350 for portrait, 1080x1080 for square.'], ['q' => 'Is the photo quality reduced after download?', 'a' => 'No! We download in original quality without any additional compression.']] as $faq)
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
                <a href="{{ route('reels.downloader') }}"
                    class="card-hover block bg-white rounded-2xl p-8 border border-pink-100">
                    <div class="flex items-center gap-4 mb-4">
                        <div
                            class="w-14 h-14 bg-gradient-to-br from-pink-500 to-red-500 rounded-xl flex items-center justify-center">
                            <i class="fas fa-film text-white text-xl"></i></div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Reels Downloader</h3>
                            <p class="text-gray-600 text-sm">Download trending Reels</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-2 text-pink-600 font-medium">Try Now <i
                            class="fas fa-arrow-right"></i></span>
                </a>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        let currentData = null;
        let currentMediaIndex = 0;

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
                showError('Please enter a valid Instagram photo URL');
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
                    currentMediaIndex = 0;
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
                'https://ui-avatars.com/api/?name=IG&background=f97316&color=fff';
            document.getElementById('username').textContent = data.username || '@unknown';
            document.getElementById('post-date').textContent = 'Posted on ' + (data.post_date || 'Unknown date');
            document.getElementById('post-caption').textContent = data.caption || 'No caption';
            document.getElementById('post-link').href = 'https://instagram.com/p/' + data.shortcode;

            document.getElementById('likes-count').textContent = formatNumber(data.likes || 0);
            document.getElementById('comments-count').textContent = formatNumber(data.comments || 0);

            // Handle carousel
            const carouselPreview = document.getElementById('carousel-preview');
            const carouselNav = document.getElementById('carousel-nav');
            const carouselThumbnails = document.getElementById('carousel-thumbnails');

            if (data.is_carousel && data.media && data.media.length > 1) {
                carouselPreview.classList.remove('hidden');
                document.getElementById('carousel-count').textContent = data.media.length;

                // Build thumbnails
                carouselThumbnails.innerHTML = '';
                carouselNav.innerHTML = '';

                data.media.forEach((media, index) => {
                    // Thumbnail
                    const thumb = document.createElement('div');
                    thumb.className =
                        `flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden cursor-pointer border-2 transition-all ${index === 0 ? 'border-orange-500' : 'border-transparent hover:border-orange-300'}`;
                    thumb.innerHTML = `
                    <img src="${media.preview_url || media.thumbnail_url}" alt="Media ${index + 1}" class="w-full h-full object-cover">
                    ${media.type === 'video' ? '<div class="absolute inset-0 flex items-center justify-center bg-black/30"><i class="fas fa-play text-white text-xs"></i></div>' : ''}
                `;
                    thumb.onclick = () => selectMedia(index);
                    carouselThumbnails.appendChild(thumb);

                    // Nav dot
                    const dot = document.createElement('span');
                    dot.className =
                        `w-2 h-2 rounded-full cursor-pointer transition-all ${index === 0 ? 'bg-white' : 'bg-white/50'}`;
                    dot.onclick = () => selectMedia(index);
                    carouselNav.appendChild(dot);
                });

                carouselNav.classList.remove('hidden');
            } else {
                carouselPreview.classList.add('hidden');
                carouselNav.classList.add('hidden');
            }

            // Show first media
            updateMediaDisplay(0);

            previewSection.classList.remove('hidden');
            previewSection.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function selectMedia(index) {
            currentMediaIndex = index;
            updateMediaDisplay(index);

            // Update thumbnail borders
            document.querySelectorAll('#carousel-thumbnails > div').forEach((thumb, i) => {
                thumb.className = thumb.className.replace(/border-(orange-500|transparent)/, i === index ?
                    'border-orange-500' : 'border-transparent');
            });

            // Update nav dots
            document.querySelectorAll('#carousel-nav > span').forEach((dot, i) => {
                dot.className = dot.className.replace(/bg-white(\/50)?/, i === index ? 'bg-white' : 'bg-white/50');
            });
        }

        function updateMediaDisplay(index) {
            if (!currentData || !currentData.media || !currentData.media[index]) return;

            const media = currentData.media[index];

            document.getElementById('preview-image').src = media.preview_url || '';
            document.getElementById('image-resolution').textContent = media.resolution || '1080 × 1080';
            document.getElementById('media-type-text').textContent = media.type === 'video' ? 'Video' : 'Photo';

            // Show/hide video badge
            const badge = document.getElementById('media-type-badge');
            badge.innerHTML = media.type === 'video' ?
                '<i class="fas fa-video mr-1"></i> Video' :
                '<i class="fas fa-image mr-1"></i> Photo';
        }

        // Download All button
        document.getElementById('download-all-btn')?.addEventListener('click', async function() {
            if (!currentData) return;

            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Preparing...';
            this.disabled = true;

            try {
                const result = await apiRequest('{{ route('api.carousel.downloads') }}', {
                    url: urlInput.value.trim(),
                    format: 'jpg'
                });

                if (result.success && result.data && result.data.downloads) {
                    // Download each file with a small delay
                    for (let i = 0; i < result.data.downloads.length; i++) {
                        const dl = result.data.downloads[i];
                        setTimeout(() => {
                            downloadFile(dl.proxy_url || dl.download_url, dl.filename);
                        }, i * 500);
                    }
                } else {
                    showError(result.message || 'Failed to prepare downloads');
                }
            } catch (error) {
                showError(error.message || 'Download failed');
            } finally {
                this.innerHTML = originalContent;
                this.disabled = false;
            }
        });

        // Download option clicks
        document.querySelectorAll('.download-option').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!currentData) {
                    showError('Please analyze a URL first');
                    return;
                }

                const format = this.dataset.format;

                const originalContent = this.innerHTML;
                this.innerHTML =
                    '<div class="flex items-center justify-center gap-2 w-full"><i class="fas fa-spinner fa-spin"></i> Preparing...</div>';
                this.disabled = true;

                try {
                    const result = await apiRequest('{{ route('api.download.url') }}', {
                        url: urlInput.value.trim(),
                        format: format,
                        media_index: currentMediaIndex
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
            a.download = filename || 'instagram_photo';
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
