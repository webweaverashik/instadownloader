<!-- Download Progress Modal -->
<div id="download-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 fade-in">
        <div class="text-center">
            <div class="w-16 h-16 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-download text-white text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Preparing Download</h3>
            <p class="text-gray-600 mb-6">Your content is being prepared...</p>
            
            <div class="w-full bg-gray-200 rounded-full h-3 mb-4">
                <div id="progress-bar" class="gradient-bg h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <p id="progress-text" class="text-sm text-gray-500">0%</p>
        </div>
        
        <button id="close-modal" class="hidden w-full mt-6 gradient-btn text-white py-3 rounded-xl font-semibold hover:opacity-90 transition-all">
            <i class="fas fa-check mr-2"></i>Download Complete
        </button>
    </div>
</div>