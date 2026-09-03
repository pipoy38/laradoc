@props(['scope' => 'front'])
<button type="button" class="btn btn-ghost" aria-label="Toggle dark mode" title="Toggle dark mode"
    x-data="{ dark: document.documentElement.dataset.theme === 'dark' }"
    @click="dark = !dark; document.documentElement.dataset.theme = dark ? 'dark' : 'light'; try { localStorage.setItem('laradoc.theme.{{ $scope }}', dark ? 'dark' : 'light') } catch (e) {}; window.dispatchEvent(new CustomEvent('laradoc:theme'))">
    <span x-show="!dark" aria-hidden="true">🌙</span><span x-show="dark" aria-hidden="true" x-cloak>☀️</span>
</button>
