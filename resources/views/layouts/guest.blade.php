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
    </head>
    <body class="font-sans antialiased">
        <div class="relative min-h-screen overflow-hidden bg-slate-50">
            <div class="absolute inset-0 bg-gradient-to-br from-white via-sky-50 to-indigo-50"></div>
            <div class="absolute inset-0 opacity-60 [background:radial-gradient(circle_at_20%_20%,rgba(14,165,233,0.22),transparent_42%),radial-gradient(circle_at_85%_25%,rgba(99,102,241,0.18),transparent_40%),radial-gradient(circle_at_40%_95%,rgba(2,132,199,0.14),transparent_50%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl items-stretch px-4 py-10 sm:px-6 lg:px-8">
                <div class="grid w-full grid-cols-1 overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200/80 md:grid-cols-2">
                    <div class="relative hidden flex-col justify-between overflow-hidden p-10 text-white md:flex">
                        <div class="absolute inset-0 bg-gradient-to-br from-sky-600 via-blue-700 to-indigo-800"></div>
                        <div class="absolute inset-0 opacity-40 [background:radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.30),transparent_35%),radial-gradient(circle_at_80%_35%,rgba(255,255,255,0.18),transparent_40%),radial-gradient(circle_at_40%_95%,rgba(255,255,255,0.12),transparent_55%)]"></div>

                        <div>
                            <a href="/" class="relative inline-flex items-center gap-3">
                                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/25">
                                    <x-application-logo class="h-6 w-6 fill-current text-white" />
                                </span>
                                <div class="leading-tight">
                                    <div class="text-sm font-medium text-white/85">Incoming</div>
                                    <div class="text-lg font-semibold tracking-tight">Geum Cheon Indo</div>
                                </div>
                            </a>
                        </div>

                        <div class="relative space-y-4">
                            <h1 class="text-3xl font-semibold tracking-tight">Incoming Material Portal</h1>
                            <p class="max-w-md text-sm leading-relaxed text-white/75">
                                Catat kedatangan material, inspeksi, dan penerimaan barang dengan lebih rapi dan terstruktur.
                            </p>
                            <div class="grid gap-2 text-sm text-white/75">
                                <div class="flex items-start gap-2">
                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-white/70"></span>
                                    <span>Tracking arrival & item detail</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-white/70"></span>
                                    <span>Inspection status & dokumentasi foto</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-white/70"></span>
                                    <span>Data vendor & trucking terintegrasi</span>
                                </div>
                            </div>
                        </div>

                        <div class="relative text-xs text-white/60">
                            © {{ date('Y') }} Geum Cheon Indo
                        </div>
                    </div>

                    <div class="flex flex-col justify-center bg-white p-7 sm:p-10">
                        <div class="mb-7 flex items-center gap-3 md:hidden">
                            <a href="/" class="inline-flex items-center gap-3">
                                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-sky-700 text-white shadow-sm">
                                    <x-application-logo class="h-6 w-6 fill-current text-white" />
                                </span>
                                <div class="leading-tight">
                                    <div class="text-sm font-medium text-slate-600">Incoming</div>
                                    <div class="text-lg font-semibold tracking-tight text-slate-900">Geum Cheon Indo</div>
                                </div>
                            </a>
                        </div>

                        <div class="w-full">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
