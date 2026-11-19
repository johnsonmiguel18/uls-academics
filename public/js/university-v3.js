// V3 motion: count-up numbers, staggered news reveals, banner parallax.
// Everything is skipped when the visitor prefers reduced motion.
document.addEventListener("DOMContentLoaded", () => {
    // Heavy things load only when the visitor gets close (for everyone, motion or not):
    // the footer's Google Map (~1.8 MB of Google scripts) and the quote band photo.
    const nearScreen = (el, load) => {
        if (!("IntersectionObserver" in window)) return load(el);
        const observer = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                load(el);
                observer.disconnect();
            }
        }, { rootMargin: "300px" });
        observer.observe(el);
    };

    document.querySelectorAll("iframe[data-src]").forEach(el => nearScreen(el, frame => {
        frame.src = frame.dataset.src;
    }));

    document.querySelectorAll("[data-quote-photo]").forEach(el => nearScreen(el, band => {
        const photo = window.innerWidth < 900 ? band.dataset.quotePhotoSmall : band.dataset.quotePhoto;
        band.style.setProperty("--quote-photo", `url('${photo}')`);
    }));

    // Hero slides after the first download once the page has loaded, or as soon as
    // someone uses the slideshow, so they don't compete with the first photo.
    const deferred = document.querySelectorAll("img[data-defer-src]");
    const loadDeferred = () => deferred.forEach(img => {
        if (!img.dataset.deferSrc) return;
        img.srcset = img.dataset.deferSrcset || "";
        img.src = img.dataset.deferSrc;
        delete img.dataset.deferSrc;
    });
    window.addEventListener("load", loadDeferred, { once: true });
    ["pointerdown", "focusin", "keydown"].forEach(type =>
        document.querySelector(".hero")?.addEventListener(type, loadDeferred, { once: true }));

    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    if (reduceMotion || !("IntersectionObserver" in window)) {
        document.querySelectorAll(".v3-reveal").forEach(el => el.classList.add("is-visible"));
        return;
    }

    const whenVisible = (elements, callback, threshold = 0.2) => {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold });

        elements.forEach(el => observer.observe(el));
    };

    // Numbers such as "1952" or "30+" count up from zero.
    whenVisible(document.querySelectorAll("[data-count]"), el => {
        const target = Number(el.dataset.count);
        const suffix = el.dataset.suffix || "";
        const start = target > 1000 ? target - 120 : 0; // years count from close by
        const duration = 1400;
        const began = performance.now();

        const tick = now => {
            const progress = Math.min((now - began) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(start + (target - start) * eased) + suffix;
            if (progress < 1) requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    }, 0.6);

    // News cards, lists and footer columns rise in one after another.
    const groups = document.querySelectorAll(".news-cards, .news-list, .announcement-cards, .news-layout, .footer-main");
    groups.forEach(group => {
        [...group.children].forEach((child, index) => {
            child.classList.add("v3-reveal");
            child.style.setProperty("--reveal-delay", `${(index % 4) * 90}ms`);
        });
    });
    whenVisible(document.querySelectorAll(".v3-reveal"), el => el.classList.add("is-visible"), 0.1);

    // Banner photos drift slower than the page (subtle parallax).
    const parallax = document.querySelectorAll(".v3-banner-image");
    if (parallax.length) {
        let ticking = false;
        window.addEventListener("scroll", () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                const offset = Math.min(window.scrollY, 600) * 0.25;
                parallax.forEach(img => img.style.setProperty("--parallax", `${offset}px`));
                ticking = false;
            });
        }, { passive: true });
    }
});
