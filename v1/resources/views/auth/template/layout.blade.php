<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ config('app.name') }} @yield('title')</title>
    @include('auth.template.parts.css-links')
    @yield('styles')
</head>

<body>
    <div class="form-body">
        <div class="notification-toast top-right" id="notification-toast"></div>
        <div class="website-logo">
            <a href="#">
                <div class="logo">
                    <img class="logo-size" src="{{ asset('auth/img/logo.svg') }}" alt="">
                </div>
            </a>
        </div>
        <div class="row">
            @include('auth.template.parts.side')

            <div class="form-holder">
                @yield('content')
            </div>

        </div>
    </div>

    @include('auth.template.parts.js-links')
    @stack('scripts')
</body>

</html>
