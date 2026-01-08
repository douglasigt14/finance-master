<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Master</title>
    <script>
        @auth
            window.location.href = '{{ route('dashboard') }}';
        @else
            window.location.href = '{{ route('login') }}';
        @endauth
    </script>
    </head>
<body>
    <p>Redirecting...</p>
    </body>
</html>
