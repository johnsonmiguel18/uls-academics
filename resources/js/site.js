// Scripts for the public site (moved out of the Blade views). Loaded on every public page by
// layouts/app.blade.php through @vite; the production build obfuscates it (vite.config.js).
// Every part checks that its elements are on the page; without JavaScript the pages still work.

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Back to top: shows after 600 px, smooth scroll.
const backToTop = document.getElementById('backToTop');
if (backToTop) {
    backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
    const toggleBackToTop = () => backToTop.classList.toggle('is-visible', window.scrollY > 600);
    window.addEventListener('scroll', toggleBackToTop, { passive: true });
    toggleBackToTop();
}

// Long lists: preview with a "Show all" button; button texts come from Site settings (data-more / data-less).
document.querySelectorAll('[data-clamp]').forEach((box) => {
    box.classList.add('is-clamped');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'ac-button ac-button-ghost ac-clamp-toggle';
    button.setAttribute('aria-controls', box.id);
    button.setAttribute('aria-expanded', 'false');
    button.textContent = box.dataset.more;
    button.addEventListener('click', () => {
        const open = box.classList.toggle('is-clamped') === false;
        button.setAttribute('aria-expanded', open);
        button.textContent = open ? box.dataset.less : box.dataset.more;
        if (! open) box.parentElement.scrollIntoView({ block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' });
    });
    box.after(button);
});

// Mobile menu; the Colleges list is a native <details>, so it works without JavaScript.
document.querySelector('.ac-menu-toggle')?.addEventListener('click', (event) => {
    const open = document.body.classList.toggle('ac-nav-open');
    event.currentTarget.setAttribute('aria-expanded', open);
});

document.addEventListener('click', (event) => {
    // Open menus close on a click outside them: the Colleges list, and the news page's "Goals" menu.
    document.querySelectorAll('.ac-nav-colleges[open], .news-sdg-menu[open]').forEach((menu) => {
        if (! menu.contains(event.target)) menu.removeAttribute('open');
    });
});
document.addEventListener('keydown', (event) => {
    const menu = document.querySelector('.news-sdg-menu[open]');
    if (event.key === 'Escape' && menu) {
        menu.removeAttribute('open');
        menu.querySelector('summary').focus();
    }
});

// News page on phones: the story rail scrolls sideways. Arrow buttons (shown only while there is more in that
// direction) and dots make that visible; swiping and the keyboard keep working without them.
document.querySelectorAll('.news-rail-wrap').forEach((wrap) => {
    const rail = wrap.querySelector('.news-rail');
    const cards = [...rail.children];
    const prev = wrap.querySelector('[data-rail="prev"]');
    const next = wrap.querySelector('[data-rail="next"]');
    const dots = wrap.querySelector('.news-rail-dots');
    if (cards.length < 2 || ! prev || ! next || ! dots) return;

    cards.forEach(() => dots.append(document.createElement('span')));
    const step = () => cards[1].offsetLeft - cards[0].offsetLeft;
    const update = () => {
        const scrollable = rail.scrollWidth > rail.clientWidth + 2; // a grid (wider screens) does not scroll
        const atStart = rail.scrollLeft < 4;
        const atEnd = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 4;
        for (const [button, hide] of [[prev, ! scrollable || atStart], [next, ! scrollable || atEnd]]) {
            if (hide && document.activeElement === button) rail.focus(); // keep keyboard focus on the page
            button.hidden = hide;
        }
        dots.hidden = ! scrollable;
        rail.tabIndex = scrollable ? 0 : -1; // a Tab stop only where it scrolls (phones), not on the desktop grid
        const current = atEnd ? cards.length - 1 : Math.round(rail.scrollLeft / step());
        [...dots.children].forEach((dot, i) => dot.classList.toggle('is-current', i === current));
    };
    const move = (direction) => rail.scrollBy({ left: direction * step(), behavior: reduceMotion ? 'auto' : 'smooth' });
    prev.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    rail.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
});

// Program finder: filters apply as soon as a dropdown or checkbox changes; typing still waits for Enter.
// While the next page loads, the results fade to skeleton cards.
const finder = document.querySelector('[data-autosubmit]');
if (finder) {
    finder.querySelectorAll('select, input[type=checkbox]').forEach((el) => el.addEventListener('change', () => finder.requestSubmit()));
    finder.addEventListener('submit', () => document.querySelector('.ac-results')?.classList.add('is-loading'));
    document.querySelectorAll('.ac-pagination a').forEach((a) => a.addEventListener('click', () => document.querySelector('.ac-results')?.classList.add('is-loading')));
}

// Global engagement: partner filters (all partners are listed without JavaScript).
const partnerFilters = document.querySelector('[data-partner-filters]');
if (partnerFilters) {
    partnerFilters.hidden = false;
    const items = [...document.querySelectorAll('.gl-partners > li')];
    const showing = partnerFilters.querySelector('[data-showing]');
    const none = document.querySelector('[data-no-match]');
    partnerFilters.addEventListener('change', () => {
        const want = Object.fromEntries([...partnerFilters.querySelectorAll('select')].map((s) => [s.dataset.filter, s.value]));
        let shown = 0;
        items.forEach((li) => {
            const ok = (! want.college || li.dataset.college === want.college) && (! want.scope || li.dataset.scope === want.scope);
            li.hidden = ! ok;
            shown += ok;
        });
        showing.textContent = showing.dataset.showing.replace(':shown', shown).replace(':count', items.length);
        none.hidden = shown > 0;
    });
}

// Core Web Vitals from real visits: <body data-vitals-rate data-vitals-url> is set when a share is chosen in
// Site settings → Admin and performance. One small beacon when the visitor leaves; no cookies, no personal data.
const vitals = document.body.dataset;
if (vitals.vitalsUrl && Math.random() * 100 < Number(vitals.vitalsRate) && 'PerformanceObserver' in window) {
    const m = {};
    const observe = (type, cb) => {
        try { new PerformanceObserver((list) => list.getEntries().forEach(cb)).observe({ type, buffered: true, durationThreshold: 16 }); } catch (e) { /* not supported */ }
    };
    let cls = 0, win = 0, last = 0, first = 0, inp = 0;
    observe('largest-contentful-paint', (e) => { m.LCP = e.startTime; });
    observe('paint', (e) => { if (e.name === 'first-contentful-paint') m.FCP = e.startTime; });
    observe('layout-shift', (e) => { // CLS: largest burst of unexpected shifts
        if (e.hadRecentInput) return;
        if (win && e.startTime - last < 1000 && e.startTime - first < 5000) { win += e.value; } else { win = e.value; first = e.startTime; }
        last = e.startTime;
        cls = Math.max(cls, win);
        m.CLS = cls;
    });
    observe('event', (e) => { if (e.interactionId) { inp = Math.max(inp, e.duration); m.INP = inp; } }); // slowest interaction
    const nav = performance.getEntriesByType('navigation')[0];
    if (nav) m.TTFB = nav.responseStart;
    let sent = false;
    addEventListener('visibilitychange', () => {
        if (sent || document.visibilityState !== 'hidden') return;
        sent = true;
        navigator.sendBeacon(vitals.vitalsUrl, JSON.stringify({ path: location.pathname, device: matchMedia('(max-width: 760px)').matches ? 'mobile' : 'desktop', metrics: m }));
    });
}
