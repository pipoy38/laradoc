@props(['title' => null, 'description' => null, 'canonical' => null, 'noindex' => false, 'image' => null, 'type' => 'website', 'jsonld' => null])
@php $site = setting('site_name', 'Laradoc'); $full = $title ? "$title · $site" : $site; $canonical = $canonical ?: url()->current();
     $image = $image ?: ((setting('logo_media_id') && ($l = \App\Models\Media::find(setting('logo_media_id')))) ? $l->url() : null); @endphp
<title>{{ $full }}</title>
@if($description)<meta name="description" content="{{ $description }}">@endif
<link rel="canonical" href="{{ $canonical }}">
@if($noindex)<meta name="robots" content="noindex, nofollow">@endif
<meta property="og:site_name" content="{{ $site }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?: $site }}">
@if($description)<meta property="og:description" content="{{ $description }}">@endif
<meta property="og:url" content="{{ $canonical }}">
@if($image)<meta property="og:image" content="{{ $image }}">@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
@if($jsonld)<script type="application/ld+json">{!! json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>@endif
