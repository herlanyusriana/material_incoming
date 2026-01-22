<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Incoming Material | Geum Cheon Indo</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

	        <!-- Scripts -->
	        @vite(['resources/css/app.css', 'resources/js/app.js'])
	        <style>[x-cloak]{display:none !important;}</style>
	        <style>
	            input[type="text"],
	            input[type="search"],
	            textarea,
	            select {
	                text-transform: uppercase;
	            }
	        </style>
	    </head>
    <body class="font-sans antialiased bg-slate-50">
        <div
            class="min-h-screen flex"
            x-data="{
                sidebarCollapsed: false,
                mobileSidebarOpen: false,
                init() {
                    try {
                        this.sidebarCollapsed = JSON.parse(localStorage.getItem('sidebarCollapsed') ?? 'false');
                    } catch (e) {
                        this.sidebarCollapsed = false;
                    }
                },
                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', JSON.stringify(this.sidebarCollapsed));
                },
            }"
            x-init="init()"
            @keydown.escape.window="mobileSidebarOpen = false"
        >
            @auth
                @include('layouts.sidebar')
            @endauth

            <div class="flex-1 flex flex-col min-h-screen">
                <header class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-10">
                    <div class="w-full max-w-none mx-auto px-4 sm:px-6 lg:px-8 py-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                @auth
                                    <button
                                        type="button"
                                        class="inline-flex md:hidden items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
                                        @click="mobileSidebarOpen = true"
                                        aria-label="Open sidebar"
                                        title="Open sidebar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="hidden md:inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
                                        @click="toggleSidebar()"
                                        :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                                        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                                    >
                                        <svg x-show="!sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                        </svg>
                                        <svg x-show="sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5" />
                                        </svg>
                                    </button>
                                @endauth
                                <h1 class="text-xl font-bold bg-gradient-to-r from-slate-900 to-slate-700 bg-clip-text text-transparent">
                                    @isset($header)
                                        {{ $header }}
                                    @else
                                        {{ config('app.name', 'Laravel') }}
                                    @endisset
                                </h1>
                            </div>
                            @auth
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-3 px-4 py-2 bg-slate-50 rounded-lg border border-slate-200">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        </div>
                                        <div class="text-sm font-medium text-slate-700">{{ Auth::user()->name }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    </div>
                </header>

                <main class="flex-1 bg-slate-50">
                    <div class="w-full max-w-none mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        {{ $slot }}
                    </div>
                </main>
            </div>
	        </div>
	        <script>
	            (function () {
	                function parseWarehouseLocationPayload(raw) {
	                    const text = String(raw ?? '').trim();
	                    if (!text) return null;

	                    // Prefer JSON payloads (our default)
	                    if (text.startsWith('{') && text.endsWith('}')) {
	                        try {
	                            const data = JSON.parse(text);
	                            const type = String(data?.type ?? '').toUpperCase();
	                            const location =
	                                data?.location ??
	                                data?.location_code ??
	                                data?.code ??
	                                data?.lokasi ??
	                                null;

	                            if (!location) return null;

	                            // If type exists, validate it. If not, still accept when "location" present.
	                            if (type && type !== 'WAREHOUSE_LOCATION') return null;

	                            return {
	                                location: String(location).trim(),
	                                class: data?.class ? String(data.class).trim() : '',
	                                zone: data?.zone ? String(data.zone).trim() : '',
	                            };
	                        } catch (_) {
	                            return null;
	                        }
	                    }

	                    return null;
	                }

	                function applyLocationFromQr(inputEl) {
	                    if (!(inputEl instanceof HTMLInputElement)) return false;
	                    const parsed = parseWarehouseLocationPayload(inputEl.value);
	                    if (!parsed) return false;

	                    inputEl.value = parsed.location;
	                    if (parsed.class) inputEl.dataset.warehouseClass = parsed.class.toUpperCase();
	                    if (parsed.zone) inputEl.dataset.warehouseZone = parsed.zone.toUpperCase();
	                    return true;
	                }

	                function isWarehouseLocationInput(el) {
	                    return el instanceof HTMLInputElement && el.matches('[data-qr-location-input]');
	                }

	                // Barcode/QR scanners usually "type" then send Enter/Tab.
	                document.addEventListener(
	                    'keydown',
	                    (event) => {
	                        const el = event.target;
	                        if (!isWarehouseLocationInput(el)) return;
	                        if (event.key !== 'Enter' && event.key !== 'Tab') return;
	                        applyLocationFromQr(el);
	                    },
	                    true
	                );

	                // Pasting JSON payload should also work.
	                document.addEventListener(
	                    'paste',
	                    (event) => {
	                        const el = event.target;
	                        if (!isWarehouseLocationInput(el)) return;
	                        setTimeout(() => applyLocationFromQr(el), 0);
	                    },
	                    true
	                );

	                document.addEventListener(
	                    'change',
	                    (event) => {
	                        const el = event.target;
	                        if (!isWarehouseLocationInput(el)) return;
	                        applyLocationFromQr(el);
	                    },
	                    true
	                );
	            })();
	        </script>
	        <script>
	            (function () {
	                const NON_TEXT_INPUT_TYPES = new Set([
	                    'number',
	                    'date',
	                    'datetime-local',
	                    'time',
	                    'month',
	                    'week',
	                    'color',
	                    'range',
	                    'file',
	                    'hidden',
	                    'checkbox',
	                    'radio',
	                ]);

	                const PRESERVE_CASE_TYPES = new Set(['password', 'email', 'url']);

	                function shouldUppercase(el) {
	                    if (!el || !(el instanceof HTMLElement)) return false;
	                    if (el.matches('[data-no-uppercase], .no-uppercase')) return false;
	                    if (el.hasAttribute('readonly') || el.hasAttribute('disabled')) return false;

	                    const tag = el.tagName;
	                    if (tag === 'INPUT') {
	                        const type = (el.getAttribute('type') || 'text').toLowerCase();
	                        if (NON_TEXT_INPUT_TYPES.has(type)) return false;
	                        if (PRESERVE_CASE_TYPES.has(type)) return false;
	                        return true;
	                    }

	                    if (tag === 'TEXTAREA') return true;
	                    return false;
	                }

	                function applyUppercase(el) {
	                    const value = String(el.value ?? '');
	                    const upper = value.toUpperCase();
	                    if (value !== upper) el.value = upper;
	                }

	                document.addEventListener(
	                    'blur',
	                    (event) => {
	                        const el = event.target;
	                        if (!shouldUppercase(el)) return;
	                        applyUppercase(el);
	                    },
	                    true
	                );

	                document.addEventListener(
	                    'submit',
	                    (event) => {
	                        const form = event.target;
	                        if (!(form instanceof HTMLFormElement)) return;
	                        form.querySelectorAll('input, textarea').forEach((el) => {
	                            if (!shouldUppercase(el)) return;
	                            applyUppercase(el);
	                        });
	                    },
	                    true
	                );
	            })();
	        </script>
	        <script>
	            document.addEventListener('DOMContentLoaded', () => {
	                // Global loading function
	                window.showLoading = function(title = 'Processing...') {
	                    Swal.fire({
	                        title: title,
	                        html: 'Please wait while we process your request.',
	                        allowOutsideClick: false,
	                        showConfirmButton: false,
	                        willOpen: () => {
	                            Swal.showLoading();
	                        }
	                    });
	                };

                    // Handle session flash messages with SweetAlert
                    @if (session('success'))
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: "{{ session('success') }}",
                            confirmButtonColor: '#3085d6',
                        });
                    @endif

                    @if (session('error'))
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: "{{ session('error') }}",
                            confirmButtonColor: '#d33',
                        });
                    @endif
	            });
	        </script>
	    </body>
	</html>
