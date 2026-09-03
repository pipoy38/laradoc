import Alpine from 'alpinejs';
import hljs from 'highlight.js/lib/common';
import 'highlight.js/styles/github-dark.css';

window.Alpine = Alpine;

// Live search dropdown
Alpine.data('siteSearch', () => ({
    q: '', results: [], open: false, timer: null, active: -1,
    input() {
        clearTimeout(this.timer);
        if (this.q.trim().length < 2) { this.results = []; this.open = false; return; }
        this.timer = setTimeout(() => this.fetch(), 250);
    },
    async fetch() {
        const r = await fetch(`/api/search?q=${encodeURIComponent(this.q)}`, { headers: { Accept: 'application/json' } });
        this.results = r.ok ? await r.json() : [];
        this.open = this.results.length > 0; this.active = -1;
    },
    move(d) { if (!this.open) return; this.active = Math.max(-1, Math.min(this.results.length - 1, this.active + d)); },
    go() { if (this.active >= 0) { window.location = this.results[this.active].url; return false; } return true; },
}));

// Scroll-spy for "On this page"
Alpine.data('toc', () => ({
    current: '',
    init() {
        const heads = [...document.querySelectorAll('.prose-doc h2[id], .prose-doc h3[id]')];
        if (!heads.length) return;
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) this.current = e.target.id; });
        }, { rootMargin: '-80px 0px -70% 0px' });
        heads.forEach(h => io.observe(h));
    },
}));

Alpine.start();

// Code blocks: highlight + copy
document.querySelectorAll('.prose-doc pre code').forEach((code) => {
    hljs.highlightElement(code);
    const btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'copy-btn'; btn.textContent = 'Copy';
    btn.addEventListener('click', async () => {
        await navigator.clipboard.writeText(code.innerText);
        btn.textContent = 'Copied'; setTimeout(() => (btn.textContent = 'Copy'), 1500);
    });
    code.parentElement.appendChild(btn);
});
// Wrap tables for horizontal scroll
document.querySelectorAll('.prose-doc table').forEach((t) => {
    const w = document.createElement('div'); w.className = 'table-wrap';
    t.parentNode.insertBefore(w, t); w.appendChild(t);
});
