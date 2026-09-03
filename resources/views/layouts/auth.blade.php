@props(['title' => 'Log in'])
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }} · {{ setting('site_name', 'Laradoc') }}</title>
<meta name="robots" content="noindex">
<x-theme-script scope="admin"/>
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
<style>[x-cloak]{display:none!important}</style>
</head>
<body class="flex min-h-dvh items-center justify-center p-4">
<div class="card w-full max-w-sm p-6">
  <div class="mb-4 flex items-center justify-between"><h1 class="text-lg font-semibold">{{ $title }}</h1><x-theme-toggle scope="admin"/></div>
  @if(session('ok'))<div class="flash flash-ok">{{ session('ok') }}</div>@endif
  {{ $slot }}
</div>
</body>
</html>
