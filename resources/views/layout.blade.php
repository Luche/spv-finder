<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cari Pembimbing Skripsi')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/htmx.org@2.0.4"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eff6ff', 100:'#dbeafe', 500:'#3b82f6', 600:'#2563eb', 700:'#1d4ed8' }
                    }
                }
            }
        }
    </script>
    <style>
        .htmx-indicator { opacity: 0; transition: opacity 200ms; }
        .htmx-request .htmx-indicator, .htmx-request.htmx-indicator { opacity: 1; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

<nav class="bg-white shadow-sm sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="{{ route('home') }}" class="font-bold text-brand-600 text-lg">📚 SpvFinder</a>
        <form id="nim-form" action="{{ route('identity.store') }}" method="POST" class="flex gap-2 items-center">
            @csrf
            <input type="text" name="student_id" placeholder="NIM (opsional)"
                   class="border rounded-lg px-3 py-1 text-sm w-36 focus:outline-none focus:ring-2 focus:ring-brand-400">
            <button type="submit" class="text-sm text-brand-600 hover:underline">Simpan</button>
        </form>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 py-8">
    @if(session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded px-4 py-2">
            {{ session('status') }}
        </div>
    @endif
    @yield('content')
</main>

<footer class="text-center text-xs text-gray-400 py-6">
    SpvFinder — Binus University
</footer>
</body>
</html>
