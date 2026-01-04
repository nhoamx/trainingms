<!DOCTYPE html>
<html class="h-full bg-gray-100">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
    <!-- Audio Configuration -->
    <script>
        window.__AUDIO_BASE_URL = '{{ config("audio.base_url") }}';
        window.__AUDIO_ENABLED = {{ config("audio.enabled") ? "true" : "false" }};
    </script>
</head>
<body class="h-full">
@inertia
@routes
</body>
</html>
