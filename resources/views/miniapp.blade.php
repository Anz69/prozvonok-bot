<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0B0F14">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Радистка Cat</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    @vite(['resources/css/miniapp.css', 'resources/js/miniapp/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
