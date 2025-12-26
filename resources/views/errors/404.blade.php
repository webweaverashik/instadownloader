<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | InstaDownloader</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #833ab4 0%, #fd1d1d 50%, #fcb045 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="text-center">
        <div class="w-24 h-24 gradient-bg rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-exclamation-triangle text-white text-4xl"></i>
        </div>
        <h1 class="text-6xl font-bold text-gray-800 mb-4">404</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Page Not Found</h2>
        <p class="text-gray-600 mb-8 max-w-md mx-auto">
            The page you're looking for doesn't exist or has been moved.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ url('/') }}" class="gradient-bg text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition-all">
                <i class="fas fa-home mr-2"></i>Go Home
            </a>
            <a href="{{ url('/instagram-video-downloader') }}" class="bg-white text-gray-700 px-6 py-3 rounded-xl font-semibold border border-gray-200 hover:border-gray-300 transition-all">
                <i class="fas fa-video mr-2"></i>Video Downloader
            </a>
        </div>
    </div>
</body>
</html>