// Applies the colour mode the visitor picked (Light or Dark) before the page paints, so it never flashes the
// other theme. Loaded in <head> on every public page; without a saved choice the device setting decides (CSS).
// A file rather than an inline script: cached pages keep an old CSP nonce, a file from this site is always allowed.
// The menu itself is in resources/js/site.js.
try {
    var theme = localStorage.getItem('uls-academics-theme');
    if (theme === 'light' || theme === 'dark') document.documentElement.dataset.theme = theme;
} catch (e) {}
