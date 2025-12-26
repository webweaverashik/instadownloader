<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                        <i class="fab fa-instagram text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold">{{ config('app.name', 'InstaDownloader') }}</span>
                </div>
                <p class="text-gray-400 text-sm">The fastest and most reliable Instagram downloader. Download videos, reels, and photos in HD quality for free.</p>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Downloaders</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="{{ route('video.downloader') }}" class="hover:text-white transition-colors">Video Downloader</a></li>
                    <li><a href="{{ route('reels.downloader') }}" class="hover:text-white transition-colors">Reels Downloader</a></li>
                    <li><a href="{{ route('photo.downloader') }}" class="hover:text-white transition-colors">Photo Downloader</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Support</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="#faq" class="hover:text-white transition-colors">FAQ</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">How to Use</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Report Bug</a></li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-semibold mb-4">Legal</h4>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li><a href="{{ route('privacy') }}" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('dmca') }}" class="hover:text-white transition-colors">DMCA</a></li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-400 text-sm">© {{ date('Y') }} {{ config('app.name', 'InstaDownloader') }}. All rights reserved.</p>
            <div class="flex space-x-4 mt-4 md:mt-0">
                <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-twitter text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-facebook text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-instagram text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-youtube text-xl"></i></a>
            </div>
        </div>
    </div>
</footer>