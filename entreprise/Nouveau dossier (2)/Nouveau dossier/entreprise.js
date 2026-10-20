document.addEventListener('DOMContentLoaded', function () {

    /* =========================
       COMPTEURS ANIMÉS
    ========================= */

    const counters = document.querySelectorAll('.counter, [data-count]');

    counters.forEach(function (counter) {
        const target = Number(counter.dataset.count ?? counter.dataset.value ?? 0);
        const duration = 1000;
        const startTime = performance.now();

        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const value = Math.round(target * easeOut);

            counter.textContent = value;

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target;
            }
        }

        requestAnimationFrame(updateCounter);
    });


    /* =========================
       MENU MOBILE
    ========================= */

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    document.querySelectorAll('.sidebar .menu-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 800) {
                sidebar.classList.remove('open');
            }
        });
    });

    document.addEventListener('click', function (event) {
        if (
            window.innerWidth <= 800 &&
            sidebar &&
            menuToggle &&
            sidebar.classList.contains('open') &&
            !sidebar.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {
            sidebar.classList.remove('open');
        }
    });


    /* =========================
       RÉVÉLATION AU DÉFILEMENT
    ========================= */

    const revealElements = document.querySelectorAll('.reveal');

    if (!('IntersectionObserver' in window)) {
        revealElements.forEach(function (element) {
            element.classList.add('visible');
        });
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });

    revealElements.forEach(function (element) {
        observer.observe(element);
    });

});
