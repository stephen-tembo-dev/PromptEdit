<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Apply the saved theme before first paint, so dark mode never flashes light. Same storage key and values as Preline's theme switch. --}}
        <script>
            (function () {
                var stored = localStorage.getItem('hs_theme') || 'default';
                var dark = stored === 'dark' || (stored === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.add(dark ? 'dark' : 'light');
            })();
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="h-full bg-gray-50 font-sans antialiased dark:bg-neutral-900">
        @inertia
    </body>
</html>
