<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>Katalog Digital SiswaMart - SMKN 11 Bandung</title>

        <!-- META DESKRIPSI UMUM UNTUK GOOGLE & SEO -->
        <meta name="description" content="Platform e-commerce resmi warga SMKN 11 Bandung tempat jual beli produk kreatif, kuliner, dan karya siswa secara online dengan mudah dan aman.">
        <meta name="keywords" content="SiswaMart, SMKN 11 Bandung, Marketplace Sekolah, Jual Beli Siswa, Toko Online Sekolah">
        
        <!-- OPEN GRAPH (UNTUK PREVIEW SAAT DI-SHARE KE WHATSAPP / SOSMED) -->
        <meta property="og:title" content="SiswaMart - Marketplace Resmi SMKN 11 Bandung">
        <meta property="og:description" content="Platform e-commerce resmi warga SMKN 11 Bandung tempat jual beli produk kreatif, kuliner, dan karya siswa secara online dengan mudah dan aman.">
        <meta property="og:image" content="{{ asset('favicon-v2.png') }}">
        <meta property="og:url" content="https://siswamart.my.id">
        <meta property="og:type" content="website">

        <!-- Favicon / Logo Website Transparan Terbaru -->
        <link rel="icon" type="image/png" href="/favicon-v2.png">
        <link rel="shortcut icon" href="/favicon-v2.png" type="image/png">
        <link rel="apple-touch-icon" href="/favicon-v2.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>