@props(['scope' => 'front'])
<script>
(function(){var k='laradoc.theme.{{ $scope }}';try{var t=localStorage.getItem(k);if(!t){t=window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'}document.documentElement.dataset.theme=t}catch(e){}})();
</script>
