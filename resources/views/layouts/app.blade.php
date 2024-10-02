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
        <link rel="stylesheet" href="{{asset('css/core.min.css')}}">
        <link rel="stylesheet" href="{{asset('css/custom.css')}}">
        <link rel="stylesheet" href="{{asset('css/vendor_bundle.min.css')}}">
        <!--    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.8.0/dist/chartjs-plugin-datalabels.min.js"></script>-->

        <script src="{{asset('js/core.js')}}"></script>
        <script src="{{asset('js/vendor_bundle.js')}}"></script>
        <script src="{{asset('js/chart.min.js')}}"></script>
        <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css"/>
        <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/progressbar.js"></script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen{{-- bg-gray-100--}}">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            @yield('content')
        </div>
    </body>
</html>
