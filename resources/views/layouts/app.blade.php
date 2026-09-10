<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Smart Application System | Geum Cheon Indo</title>

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

                /* Tom Select Overrides */
                .ts-wrapper.single .ts-control {
                    border-radius: 0.75rem !important; /* Matches rounded-xl */
                    padding: 0.5rem 0.75rem !important;
                    background-color: white !important;
                    border: 1px solid #e2e8f0 !important; /* slate-200 */
                }
                .ts-wrapper.single.focus .ts-control {
                    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
                    border-color: #3b82f6 !important;
                }
                .ts-dropdown {
                    border-radius: 0.5rem !important;
                    margin-top: 4px !important;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                    border: 1px solid #e2e8f0 !important;
                    z-index: 99999 !important;
                }
                .ts-dropdown,
                .ts-wrapper .ts-dropdown {
                    max-height: 280px;
                    overflow-y: auto;
                }
                .ts-wrapper,
                .ts-control {
                    overflow: visible !important;
                }
                .ts-dropdown .active {
                    background-color: #f1f5f9 !important;
                    color: #1e293b !important;
                }
                .ts-control input {
                    text-transform: uppercase !important;
                }
	        </style>
	    </head>
    <body class="bg-slate-100/70 font-sans text-slate-800 antialiased">
        <a href="#main-content" class="skip-link">Skip ke konten utama</a>
	        <div class="flex min-h-screen flex-col">
                <header class="sticky top-0 z-20 border-b border-slate-200/90 bg-white/95 backdrop-blur">
                    <div class="mx-auto w-full max-w-[1440px] px-4 py-2 sm:px-6 lg:px-8">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" title="{{ __('nav.home') }}">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm shadow-indigo-600/20">
                                        <x-icon name="sparkles" class="h-4 w-4" />
                                    </span>
                                    <span class="hidden text-sm font-bold tracking-tight text-slate-900 sm:block">
                                        Smart Application <span class="text-indigo-600">System</span>
                                    </span>
                                </a>
                                @isset($header)
                                    <span class="hidden h-5 w-px bg-slate-200 md:block"></span>
                                    <h1 class="truncate text-base font-semibold text-slate-800">{{ $header }}</h1>
                                @endisset
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-lang-switcher />
                                @auth
                                    <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                                        <button type="button" @click="open = !open"
                                            class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white py-1 pl-1 pr-2 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold uppercase text-white">
                                                {{ substr(Auth::user()->name, 0, 1) }}
                                            </span>
                                            <span class="hidden max-w-[10rem] truncate text-sm font-medium text-slate-700 sm:block">{{ Auth::user()->name }}</span>
                                            <x-icon name="chevron-down" class="h-3.5 w-3.5 text-slate-400" />
                                        </button>
                                        <div x-show="open" x-transition.origin.top.right x-cloak
                                            class="absolute right-0 z-40 mt-1.5 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                                            <div class="border-b border-slate-100 px-3 py-2">
                                                <p class="truncate text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                                                <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>
                                            </div>
                                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50">
                                                <x-icon name="user" class="h-4 w-4 text-slate-400" /> {{ __('nav.profile') }}
                                            </a>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50">
                                                    <x-icon name="logout" class="h-4 w-4" /> {{ __('nav.logout') }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                </header>

                <main id="main-content" tabindex="-1" class="flex-1">
                    <div class="mx-auto w-full {{ ($fullWidth ?? false) ? 'max-w-none' : 'max-w-[1440px]' }} px-4 py-8 sm:px-6 lg:px-8">
                        @isset($slot)
                            {{ $slot }}
                        @else
                            @yield('content')
                        @endisset
                    </div>
                </main>
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
                    // Initialize Tom Select (opt-in only)
                    window.initTomSelect = function(container = document) {
                        const autoSelector = 'select[data-tomselect]:not([data-remote])';
                        const selects = container instanceof HTMLSelectElement 
                            ? [container] 
                            : (container.querySelectorAll ? container.querySelectorAll(autoSelector) : []);
                        
                        selects.forEach(el => {
                            if (el.tomselect || el.classList.contains('tomselected')) return;
                            if (el.multiple) return; 
                            if (container !== el && !el.matches(autoSelector)) return;
                            
                            new TomSelect(el, {
                                create: false,
                                sortField: {
                                    field: "text",
                                    direction: "asc"
                                },
                                allowEmptyOption: true,
                                dropdownParent: 'body',
                            });
                        });
                    };

                    window.initRemoteTomSelect = function(el, url, options = {}) {
                        if (el.tomselect || el.classList.contains('tomselected')) return;
                        
                        const classification = el.dataset.classification || '';
                        
                        return new TomSelect(el, {
                            valueField: options.valueField || 'id',
                            labelField: options.labelField || 'part_no',
                            searchField: options.searchField || ['part_no', 'part_name', 'model'],
                            dropdownParent: 'body',
                            closeAfterSelect: false,
                            load: function(query, callback) {
                                if (!query.length) return callback();
                                const searchUrl = new URL(url, window.location.origin);
                                searchUrl.searchParams.append('q', query);
                                if (classification) searchUrl.searchParams.append('classification', classification);
                                
                                fetch(searchUrl)
                                    .then(response => response.json())
                                    .then(json => {
                                        callback(json);
                                    }).catch(() => {
                                        callback();
                                    });
                            },
                            render: {
                                option: function(item, escape) {
                                    return `<div class="py-2 px-3">
                                        <div class="font-bold text-slate-900">${escape(item.part_no)}</div>
                                        <div class="text-xs text-slate-500">${escape(item.part_name || '')} ${item.model ? '&bull; ' + escape(item.model) : ''}</div>
                                    </div>`;
                                },
                                item: function(item, escape) {
                                    return `<div class="font-medium">${escape(item.part_no)} - ${escape(item.part_name || '')}</div>`;
                                }
                            },
                            placeholder: el.getAttribute('placeholder') || 'Type to search...',
                            allowEmptyOption: true,
                        });
                    };

                    initTomSelect();

                    // Watch for dynamic elements
                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.type === 'childList') {
                                mutation.addedNodes.forEach((node) => {
                                    if (node.nodeType === 1) { // ELEMENT_NODE
                                        if (node.tagName === 'SELECT') {
                                            if (node.matches('select[data-tomselect]:not([data-remote])')) {
                                                initTomSelect(node);
                                            }
                                        } else if (node.querySelectorAll) {
                                            const found = node.querySelectorAll('select[data-tomselect]:not([data-remote])');
                                            if (found.length > 0) {
                                                initTomSelect(node);
                                            }
                                        }
                                    }
                                });
                            }
                            
                            // Sync if options changed in an existing tomselect
                            if (mutation.target && mutation.target.tagName === 'SELECT' && mutation.target.tomselect) {
                                const sel = mutation.target;
                                clearTimeout(sel._ts_sync_timer);
                                sel._ts_sync_timer = setTimeout(() => {
                                    if (sel.tomselect) sel.tomselect.sync();
                                }, 50);
                            }
                        });
                    });
                    observer.observe(document.body, { childList: true, subtree: true });

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
            @stack('scripts')
	    </body>
	</html>
