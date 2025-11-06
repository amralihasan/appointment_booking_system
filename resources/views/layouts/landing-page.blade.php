<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('landing.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [dir="rtl"] {
            direction: rtl;
        }
        [dir="ltr"] {
            direction: ltr;
        }
    </style>
</head>
<body class="bg-white">
    {{ $slot }}
</body>
</html>

