<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'WhatsML') }} - Admin Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Heroicons -->
    <script src="https://unpkg.com/heroicons@2.0.18/24/outline/index.js" type="module"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="w-64 bg-red-800 text-white flex flex-col">
            <div class="flex items-center justify-center h-16 bg-red-900">
                <span class="text-2xl font-bold">WhatsML Admin</span>
            </div>
            
            <nav class="flex-1 px-2 py-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}" 
                   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md hover:bg-red-700 {{ request()->routeIs('admin.dashboard') ? 'bg-red-700' : '' }}">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                    </svg>
                    Dashboard
                </a>
                
                <a href="{{ route('admin.users') }}" 
                   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md hover:bg-red-700 {{ request()->routeIs('admin.users*') ? 'bg-red-700' : '' }}">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                    </svg>
                    Users
                </a>
                
                <a href="{{ route('admin.subscriptions') }}" 
                   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md hover:bg-red-700 {{ request()->routeIs('admin.subscriptions*') ? 'bg-red-700' : '' }}">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    Subscriptions
                </a>
                
                <a href="{{ route('admin.settings') }}" 
                   class="group flex items-center px-2 py-2 text-sm font-medium rounded-md hover:bg-red-700 {{ request()->routeIs('admin.settings*') ? 'bg-red-700' : '' }}">
                    <svg class="mr-3 flex-shrink-0 h-6 w-6 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </nav>
            
            <div class="p-4 border-t border-red-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-8 w-8 bg-red-600 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-white">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-red-300">{{ auth()->user()->role }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-xs text-red-300 hover:text-white">
                        Sign out
                    </button>
                </form>
            </div>
        </div>

        <!-- Content area -->
        <div class="flex-1 flex flex-col">
            <header class="w-full bg-white shadow-sm h-16 flex items-center px-4 justify-between">
                <h1 class="text-xl font-semibold text-gray-900">@yield('page-title', 'Admin Panel')</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">Welcome, {{ auth()->user()->name }}</span>
                </div>
            </header>
            
            <main class="flex-1 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50"></div>

    <!-- Scripts -->
    <script>
        // Toast notification system
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg mb-2 transform transition-all duration-300 translate-x-full`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, 5000);
        }

        // CSRF token for AJAX requests
        window.csrfToken = '{{ csrf_token() }}';
    </script>

    @stack('scripts')
</body>
</html>
