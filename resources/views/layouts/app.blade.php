<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $companyName ?? config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.08); border-radius: 2px; }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.15); }
        .sidebar-scroll { scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, 0.08) transparent; }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dark-crystal font-sans antialiased">

    @auth
    <div x-data="{ sidebarOpen: false }">

        {{-- ── Desktop sidebar (always visible ≥ lg) ───────── --}}
        <aside class="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-64 lg:flex-col">
            <x-sidebar />
        </aside>

        {{-- ── Mobile top bar (< lg) ───────────────────────── --}}
        <div class="sticky top-0 z-40 flex items-center gap-x-3 h-14 px-4 lg:hidden" style="background: linear-gradient(180deg, rgba(20, 27, 45, 0.96), rgba(12, 17, 31, 0.94)); backdrop-filter: blur(24px) saturate(150%); -webkit-backdrop-filter: blur(24px) saturate(150%); border-bottom: 1px solid rgba(255,255,255,0.1); box-shadow: 0 8px 28px rgba(0,0,0,0.45);">
            <button type="button"
                    @click="sidebarOpen = true"
                    :aria-expanded="sidebarOpen.toString()"
                    aria-controls="mobile-sidebar"
                    class="-m-2.5 p-2.5 text-gray-400 hover:text-white transition-colors duration-200">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
                @if(!empty($companyLogoUrl))
                    <span class="flex h-10 w-14 items-center justify-center overflow-hidden rounded-2xl bg-slate-950/70 ring-1 ring-white/10 shrink-0 px-2 py-1.5">
                        <img src="{{ $companyLogoUrl }}" alt="{{ $companyName ?? config('app.name') }} logo" class="max-h-full max-w-full object-contain">
                    </span>
                @endif
                <span class="truncate text-lg font-semibold text-white">{{ $companyName ?? config('app.name') }}</span>
            </a>
        </div>

        {{-- ── Mobile sidebar overlay (< lg) ───────────────── --}}
        <div x-show="sidebarOpen"
             x-cloak
             class="relative z-50 lg:hidden"
             role="dialog"
             aria-modal="true">

            {{-- Backdrop --}}
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="fixed inset-0" style="background: rgba(2, 4, 10, 0.9); backdrop-filter: blur(16px) saturate(120%); -webkit-backdrop-filter: blur(16px) saturate(120%);"></div>

            {{-- Sidebar drawer --}}
            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     id="mobile-sidebar"
                     class="relative mr-16 flex w-full max-w-64 flex-1">

                    {{-- Close button --}}
                    <div x-show="sidebarOpen"
                         x-transition:enter="ease-in-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in-out duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <x-sidebar />
                </div>
            </div>
        </div>

        {{-- ── Main content ────────────────────────────────── --}}
        <main class="lg:pl-64">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>
    @else
        {{-- Guest layout (no sidebar) --}}
        <main class="min-h-screen">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    @endauth

    @stack('scripts')
</body>
</html>
