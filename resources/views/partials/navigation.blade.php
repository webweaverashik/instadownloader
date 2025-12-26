<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-2">
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                        <i class="fab fa-instagram text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold gradient-text">{{ config('app.name', 'InstaDownloader') }}</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ route('video.downloader') }}"
                    class="nav-link {{ request()->routeIs('video.downloader') ? 'active' : '' }} text-gray-700 hover:text-purple-600 font-medium">Video
                    Downloader</a>
                <a href="{{ route('reels.downloader') }}"
                    class="nav-link {{ request()->routeIs('reels.downloader') ? 'active' : '' }} text-gray-700 hover:text-purple-600 font-medium">Reels
                    Downloader</a>
                <a href="{{ route('photo.downloader') }}"
                    class="nav-link {{ request()->routeIs('photo.downloader') ? 'active' : '' }} text-gray-700 hover:text-purple-600 font-medium">Photo
                    Downloader</a>
                <a href="#faq" class="nav-link text-gray-700 hover:text-purple-600 font-medium">FAQ</a>
            </div>

            <button id="mobile-menu-btn" class="md:hidden p-2">
                <i class="fas fa-bars text-gray-700 text-xl"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
        <div class="px-4 py-3 space-y-3">
            <a href="{{ route('video.downloader') }}"
                class="block {{ request()->routeIs('video.downloader') ? 'text-purple-600' : 'text-gray-700 hover:text-purple-600' }} font-medium py-2">Video
                Downloader</a>
            <a href="{{ route('reels.downloader') }}"
                class="block {{ request()->routeIs('reels.downloader') ? 'text-pink-600' : 'text-gray-700 hover:text-purple-600' }} font-medium py-2">Reels
                Downloader</a>
            <a href="{{ route('photo.downloader') }}"
                class="block {{ request()->routeIs('photo.downloader') ? 'text-orange-600' : 'text-gray-700 hover:text-purple-600' }} font-medium py-2">Photo
                Downloader</a>
            <a href="#faq" class="block text-gray-700 hover:text-purple-600 font-medium py-2">FAQ</a>
        </div>
    </div>
</nav>
