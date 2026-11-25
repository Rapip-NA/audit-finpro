<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Laporan kekurangan dari web Final Project kalian!">
    <meta name="theme-color" content="#fafafa">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Audit Final Project">
    <meta property="og:description" content="Laporan kekurangan dari web Final Project kalian!">
    <meta property="og:image" content="{{ asset('og-image.jpg') }}">
    <link rel="icon" type="image/png" href="{{ asset('html-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>{{ $title ?? 'Audit Final Project' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-neutral-900 antialiased selection:bg-neutral-200">
    {{ $slot }}
    @livewireScripts
</body>
</html>
