<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Softphone - {{ config('app.name') }}</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-gray-900 text-gray-100">
    <div class="h-screen flex flex-col items-center justify-center p-8 text-center">
        <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-6">
            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold mb-2">No Extension Assigned</h1>
        <p class="text-gray-400 mb-6">Contact your administrator to assign an extension to your account.</p>
        <button onclick="window.close()" class="px-6 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm">
            Close Window
        </button>
    </div>
</body>
</html>





