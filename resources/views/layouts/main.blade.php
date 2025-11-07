<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/1564a72969.js" crossorigin="anonymous"></script>
    <style>
        html, body {
            margin: 0;
            height: 100%;
            overflow-y: auto;
            font-family: 'Geist', sans-serif;
            scrollbar-gutter: stable both-edges;
        }
    </style>
    @stack('scripts')
</head>
    <body class="bg-gray-50">
        <div class="min-h-screen flex flex-col">
            <main class="flex-1">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="p-4 sm:px-0">
                        @yield('content')
                    </div>
                </div>
            </main>
            <footer class="bg-white border-t">
                <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>
