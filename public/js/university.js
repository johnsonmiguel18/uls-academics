
document.addEventListener("DOMContentLoaded", () => {
    // ==========================================
    // HERO SLIDESHOW AND HEADER
    // ==========================================

    const hero = document.getElementById("hero");
    const header = document.getElementById("siteHeader");
    const slides = Array.from(document.querySelectorAll(".hero-slide"));
    const heroDots = Array.from(document.querySelectorAll(".hero-dot"));
    const heroPrev = document.getElementById("heroPrev");
    const heroNext = document.getElementById("heroNext");
    const heroPause = document.getElementById("heroPause");

    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    const backToTop = document.getElementById("backToTop");

    backToTop?.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });

    // Solid header once the visitor scrolls away from the top;
    // back-to-top button once they are a screen or so down.
    function updateHeader() {
        header?.classList.toggle("is-scrolled", window.scrollY > 40);
        backToTop?.classList.toggle("is-visible", window.scrollY > 600);
    }

    window.addEventListener("scroll", updateHeader, { passive: true });
    updateHeader();

    const reduceMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;

    const slideDelay = 7000;
    let currentSlide = 0;
    let slideTimer = null;

    function showSlide(index) {
        slides[currentSlide].classList.remove("active");
        slides[currentSlide].inert = true;
        heroDots[currentSlide]?.removeAttribute("aria-current");

        currentSlide = (index + slides.length) % slides.length;

        slides[currentSlide].classList.add("active");
        slides[currentSlide].inert = false;
        heroDots[currentSlide]?.setAttribute("aria-current", "true");

        // Restart the timer so a manual change gets a full slide duration.
        if (slideTimer) play();
    }

    function play() {
        window.clearInterval(slideTimer);
        slideTimer = window.setInterval(
            () => showSlide(currentSlide + 1),
            slideDelay
        );
        hero.classList.add("is-playing");
        heroPause?.setAttribute("aria-label", "Pause slideshow");
    }

    function pause() {
        window.clearInterval(slideTimer);
        slideTimer = null;
        hero.classList.remove("is-playing");
        heroPause?.setAttribute("aria-label", "Play slideshow");
    }

    if (hero && slides.length > 1) {
        hero.style.setProperty("--slide-delay", `${slideDelay}ms`);

        heroPrev?.addEventListener("click", () => showSlide(currentSlide - 1));
        heroNext?.addEventListener("click", () => showSlide(currentSlide + 1));
        heroDots.forEach((dot, index) => {
            dot.addEventListener("click", () => showSlide(index));
        });
        heroPause?.addEventListener("click", () => {
            slideTimer ? pause() : play();
        });

        // Swipe left/right on touch screens.
        let touchStartX = null;

        hero.addEventListener("touchstart", event => {
            touchStartX = event.touches[0].clientX;
        }, { passive: true });

        hero.addEventListener("touchend", event => {
            if (touchStartX === null) return;

            const distance = event.changedTouches[0].clientX - touchStartX;
            touchStartX = null;

            if (Math.abs(distance) > 50) {
                showSlide(currentSlide + (distance < 0 ? 1 : -1));
            }
        });

        if (reduceMotion) {
            pause();
        } else {
            play();
        }
    }

    // ==========================================
    // MOBILE NAVIGATION AND DROPDOWNS
    // ==========================================

    const mobileQuery = window.matchMedia("(max-width: 1180px)");

    const dropdowns = document.querySelectorAll(
        ".dynamic-navigation .nav-dropdown"
    );

    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", () => {
            const isOpen = mainNav.classList.toggle("open");

            menuToggle.setAttribute("aria-expanded", String(isOpen));

            if (!isOpen) {
                dropdowns.forEach(dropdown => {
                    dropdown.classList.remove("is-mobile-open");

                    const trigger = dropdown.querySelector(
                        ":scope > .nav-dropdown-trigger"
                    );

                    if (trigger) {
                        trigger.setAttribute("aria-expanded", "false");
                    }
                });
            }
        });
    }

    dropdowns.forEach((dropdown, index) => {
        const trigger = dropdown.querySelector(
            ":scope > .nav-dropdown-trigger"
        );

        const menu = dropdown.querySelector(
            ":scope > .nav-dropdown-menu"
        );

        if (!trigger || !menu) return;

        if (!menu.id) {
            menu.id = `uls-nav-menu-${index}`;
        }

        trigger.setAttribute("aria-controls", menu.id);
        trigger.setAttribute("aria-expanded", "false");

        trigger.addEventListener("click", event => {
            if (!mobileQuery.matches) return;

            event.preventDefault();
            event.stopPropagation();

            const willOpen = !dropdown.classList.contains(
                "is-mobile-open"
            );

            dropdowns.forEach(otherDropdown => {
                otherDropdown.classList.remove("is-mobile-open");

                const otherTrigger = otherDropdown.querySelector(
                    ":scope > .nav-dropdown-trigger"
                );

                if (otherTrigger) {
                    otherTrigger.setAttribute("aria-expanded", "false");
                }
            });

            if (willOpen) {
                dropdown.classList.add("is-mobile-open");
                trigger.setAttribute("aria-expanded", "true");
            }
        });
    });

    if (mainNav && menuToggle) {
        mainNav.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", () => {
                if (
                    mobileQuery.matches &&
                    link.matches(".nav-dropdown-trigger")
                ) {
                    return;
                }

                mainNav.classList.remove("open");
                menuToggle.setAttribute("aria-expanded", "false");
            });
        });
    }

    mobileQuery.addEventListener("change", event => {
        if (!event.matches) {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove("is-mobile-open");

                const trigger = dropdown.querySelector(
                    ":scope > .nav-dropdown-trigger"
                );

                if (trigger) {
                    trigger.setAttribute("aria-expanded", "false");
                }
            });
        }
    });

    
    // ==========================================
    // NESTED SUBMENU MOBILE INTERACTION
    // ==========================================

    const nestedParents = document.querySelectorAll(
        ".dynamic-navigation .nav-dropdown-item.has-children"
    );

    nestedParents.forEach((parent, index) => {
        const trigger = parent.querySelector(
            ":scope > .nav-submenu-trigger"
        );

        const submenu = parent.querySelector(
            ":scope > .nav-submenu"
        );

        if (!trigger || !submenu) return;

        // Make the label accessible to keyboard and screen readers.
        trigger.setAttribute("role", "button");
        trigger.setAttribute("tabindex", "0");
        trigger.setAttribute("aria-expanded", "false");

        if (!submenu.id) {
            submenu.id = `uls-nested-submenu-${index}`;
        }

        trigger.setAttribute("aria-controls", submenu.id);

        function toggleNestedMenu() {
            // On desktop, CSS hover controls submenu visibility.
            if (!mobileQuery.matches) return;

            const willOpen = !parent.classList.contains(
                "is-mobile-open"
            );

            // Close nested siblings at this same level.
            const siblings = Array.from(
                parent.parentElement.children
            ).filter(item =>
                item.classList.contains("nav-dropdown-item")
            );

            siblings.forEach(sibling => {
                sibling.classList.remove("is-mobile-open");

                const siblingTrigger = sibling.querySelector(
                    ":scope > .nav-submenu-trigger"
                );

                if (siblingTrigger) {
                    siblingTrigger.setAttribute(
                        "aria-expanded",
                        "false"
                    );
                }
            });

            if (willOpen) {
                parent.classList.add("is-mobile-open");
                trigger.setAttribute("aria-expanded", "true");
            }
        }

        trigger.addEventListener("click", event => {
            if (!mobileQuery.matches) return;

            event.preventDefault();
            event.stopPropagation();
            toggleNestedMenu();
        });

        trigger.addEventListener("keydown", event => {
            if (
                !mobileQuery.matches ||
                (event.key !== "Enter" && event.key !== " ")
            ) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            toggleNestedMenu();
        });
    });

    // Reset nested menus when the mobile navigation is closed.
    if (menuToggle && mainNav) {
        menuToggle.addEventListener("click", () => {
            if (mainNav.classList.contains("open")) return;

            nestedParents.forEach(parent => {
                parent.classList.remove("is-mobile-open");

                const trigger = parent.querySelector(
                    ":scope > .nav-submenu-trigger"
                );

                if (trigger) {
                    trigger.setAttribute("aria-expanded", "false");
                }
            });
        });
    }

    // Reset mobile-only submenu states when returning to desktop.
    mobileQuery.addEventListener("change", event => {
        if (event.matches) return;

        nestedParents.forEach(parent => {
            parent.classList.remove("is-mobile-open");

            const trigger = parent.querySelector(
                ":scope > .nav-submenu-trigger"
            );

            if (trigger) {
                trigger.setAttribute("aria-expanded", "false");
            }
        });
    });


    // ==========================================
    // HOMEPAGE SCROLL REVEAL
    // ==========================================

    // Extend the existing animation to these sections.
    // College cards keep their existing reveal behavior.
    document.querySelectorAll(
        ".intro, .home-news"
    ).forEach(section => {
        section.classList.add("reveal-on-scroll");
    });

    const revealElements = document.querySelectorAll(
        ".reveal-on-scroll"
    );

    if (reduceMotion || !("IntersectionObserver" in window)) {
        revealElements.forEach(element => {
            element.classList.add("is-visible");
        });
    } else {
        const revealObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.12,
                rootMargin: "0px 0px -30px 0px"
            }
        );

        revealElements.forEach(element => {
            revealObserver.observe(element);
        });
    }

    
    // ==========================================
    // NAVBAR LIVE SEARCH DROPDOWN
    // ==========================================

    const searchToggle = document.getElementById("navSearchToggle");
    const searchForm = document.getElementById("navSearchForm");
    const searchInput = document.getElementById("navSearchInput");
    const searchResults = document.getElementById("navSearchResults");

    if (searchToggle && searchForm && searchInput && searchResults) {
        let searchTimer;
        let activeController = null;

        function closeSearch() {
            searchForm.hidden = true;
            searchToggle.setAttribute("aria-expanded", "false");
            searchResults.innerHTML = "";

            if (activeController) {
                activeController.abort();
                activeController = null;
            }
        }

        searchToggle.addEventListener("click", () => {
            const isOpening = searchForm.hidden;

            searchForm.hidden = !isOpening;
            searchToggle.setAttribute("aria-expanded", String(isOpening));

            if (isOpening) {
                searchInput.focus();
            } else {
                closeSearch();
            }
        });

        searchInput.addEventListener("input", () => {
            clearTimeout(searchTimer);

            if (activeController) {
                activeController.abort();
                activeController = null;
            }

            const term = searchInput.value.trim();
            searchResults.innerHTML = "";

            if (term.length < 2) {
                searchResults.textContent = "Type at least 2 characters to search.";
                return;
            }

            searchResults.textContent = "Searching...";

            searchTimer = setTimeout(async () => {
                activeController = new AbortController();

                try {
                    const response = await fetch(
                        `/search/suggestions?q=${encodeURIComponent(term)}`,
                        {
                            headers: { Accept: "application/json" },
                            signal: activeController.signal
                        }
                    );

                    if (!response.ok) {
                        throw new Error("Search request failed");
                    }

                    const results = await response.json();

                    searchResults.innerHTML = "";

                    if (!results.length) {
                        searchResults.textContent = "No matching pages or news found.";
                        return;
                    }

                    results.forEach(result => {
                        const link = document.createElement("a");
                        link.className = "nav-search-result";
                        link.href = result.url;

                        const title = document.createElement("span");
                        title.className = "nav-search-result-title";
                        title.textContent = result.title;

                        const type = document.createElement("small");
                        type.className = "nav-search-result-type";
                        type.textContent = result.type;

                        link.append(title, type);
                        searchResults.appendChild(link);
                    });
                } catch (error) {
                    if (error.name === "AbortError") return;

                    searchResults.textContent =
                        "Search is temporarily unavailable. Please try again.";
                }
            }, 250);
        });

        document.addEventListener("click", event => {
            if (
                !searchForm.contains(event.target) &&
                !searchToggle.contains(event.target)
            ) {
                closeSearch();
            }
        });

        searchInput.addEventListener("keydown", event => {
            if (event.key === "Escape") {
                closeSearch();
                searchToggle.focus();
            }
        });
    }
});
