<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO Meta Tags -->
    <title>{{ $meta['title'] ?? config('app.name') }}</title>
    <meta name="description" content="{{ $meta['description'] ?? '' }}">
    <meta name="keywords" content="{{ $meta['keywords'] ?? '' }}">
    <link rel="canonical" href="{{ $meta['canonical'] ?? url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $meta['title'] ?? config('app.name') }}">
    <meta property="og:description" content="{{ $meta['description'] ?? '' }}">
    <meta property="og:type" content="{{ $meta['og_type'] ?? 'website' }}">
    <meta property="og:url" content="{{ $meta['canonical'] ?? url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $meta['description'] ?? '' }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Custom Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        /* Theme Variables */
        :root {
            --primary-start: #833ab4;
            --primary-middle: #fd1d1d;
            --primary-end: #fcb045;
        }

        @yield('theme-styles')

        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-middle) 50%, var(--primary-end) 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-middle) 50%, var(--primary-end) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-btn {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
        }

        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(131, 58, 180, 0.3);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.02);
            }
        }

        .loader {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-start);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 100%;
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @yield('additional-styles')
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    @include('partials.navigation')

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- Download Modal -->
    @include('partials.download-modal')

    <!-- Base Scripts -->
    <script>
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const answer = button.nextElementSibling;
                const icon = button.querySelector('i.fa-chevron-down');

                document.querySelectorAll('.faq-answer').forEach(a => {
                    if (a !== answer) a.classList.add('hidden');
                });
                document.querySelectorAll('.faq-question i.fa-chevron-down').forEach(i => {
                    if (i !== icon) i.style.transform = 'rotate(0deg)';
                });

                answer.classList.toggle('hidden');
                if (icon) {
                    icon.style.transform = answer.classList.contains('hidden') ? 'rotate(0deg)' :
                        'rotate(180deg)';
                }
            });
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                    if (mobileMenu) mobileMenu.classList.add('hidden');
                }
            });
        });

        // Download Modal Functions
        const downloadModal = document.getElementById('download-modal');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const closeModalBtn = document.getElementById('close-modal');

        function showDownloadModal() {
            if (!downloadModal) return;

            downloadModal.classList.remove('hidden');
            let progress = 0;

            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    if (closeModalBtn) closeModalBtn.classList.remove('hidden');
                    if (progressText) progressText.textContent = 'Download complete!';
                }
                if (progressBar) progressBar.style.width = progress + '%';
                if (progress < 100 && progressText) progressText.textContent = Math.round(progress) + '%';
            }, 300);
        }

        function hideDownloadModal() {
            if (!downloadModal) return;

            downloadModal.classList.add('hidden');
            if (closeModalBtn) closeModalBtn.classList.add('hidden');
            if (progressBar) progressBar.style.width = '0%';
            if (progressText) progressText.textContent = '0%';
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', hideDownloadModal);
        }

        if (downloadModal) {
            downloadModal.addEventListener('click', (e) => {
                if (e.target === downloadModal) {
                    hideDownloadModal();
                }
            });
        }

        // Instagram URL Helpers
        function detectContentType(url) {
            if (/instagram\.com\/(reel|reels)\//i.test(url)) {
                return {
                    type: 'reels',
                    label: 'Instagram Reels detected',
                    color: 'bg-pink-100 text-pink-700'
                };
            }
            if (/instagram\.com\/stories\//i.test(url)) {
                return {
                    type: 'story',
                    label: 'Instagram Story detected',
                    color: 'bg-purple-100 text-purple-700'
                };
            }
            if (/instagram\.com\/tv\//i.test(url)) {
                return {
                    type: 'video',
                    label: 'IGTV Video detected',
                    color: 'bg-purple-100 text-purple-700'
                };
            }
            if (/instagram\.com\/p\//i.test(url)) {
                return {
                    type: 'post',
                    label: 'Instagram Post detected',
                    color: 'bg-orange-100 text-orange-700'
                };
            }
            return null;
        }

        function isValidInstagramUrl(url) {
            return /^https?:\/\/(www\.)?instagram\.com\/(p|reel|reels|tv)\/[\w-]+\/?/i.test(url);
        }

        // API Helper
        async function apiRequest(endpoint, data = {}) {
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'An error occurred');
                }

                return result;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }
    </script>

    @yield('scripts')
</body>

</html>
