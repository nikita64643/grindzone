<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark'=> ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    {{-- Inline script to set theme before first paint (avoids flash) --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';
            const root = document.documentElement;

            if (appearance === 'light') {
                root.classList.remove('dark');
            } else if (appearance === 'dark') {
                root.classList.add('dark');
            } else {
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    root.classList.add('dark');
                } else {
                    root.classList.remove('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="shortcut icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">

    @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="relative font-sans antialiased overflow-x-hidden">
    <div class="pointer-events-none fixed inset-0 z-[9999] bg-black/10 dark:bg-black/25" aria-hidden="true"></div>
    @inertia
</body>

</html>