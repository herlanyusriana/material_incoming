<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50 flex">
            @auth
                @include('layouts.sidebar')
            @endauth

            <div class="flex-1 flex flex-col min-h-screen">
                <header class="bg-transparent">
                    <div class="max-w-7xl mx-auto px-10 pt-6">
                        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl px-6 py-4 flex items-center justify-between gap-6">
                            <div class="text-lg font-semibold text-gray-900">
                                @isset($header)
                                    {{ $header }}
                                @else
                                    {{ config('app.name', 'Laravel') }}
                                @endisset
                            </div>
                            @auth
                                <div class="flex items-center space-x-3">
                                    <div class="text-sm text-gray-700">Admin</div>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="text-sm text-gray-600 hover:text-gray-900">Logout</button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    </div>
                </header>

                <main class="flex-1">
                    <div class="max-w-7xl mx-auto px-10 py-10">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
