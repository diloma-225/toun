document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    initHero();
    initReveal();
    initFaq();
    initFavorites();
    initMarquee();
});

function initHeader() {
    const header = document.querySelector('.site-header');
    const toggle = document.querySelector('.menu-toggle');
    const dropdown = document.querySelector('.nav-dropdown');
    const dropdownButton = document.querySelector('.nav-dropdown-btn');

    if (!header) {
        return;
    }

    const onScroll = () => header.classList.toggle('scrolled', window.scrollY > 10);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const setMenu = open => {
        header.classList.toggle('mobile-open', open);

        if (toggle) {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.querySelector('i').className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
        }
    };

    if (toggle) {
        toggle.addEventListener('click', () => setMenu(!header.classList.contains('mobile-open')));
    }

    header.querySelectorAll('.main-nav a').forEach(link => {
        link.addEventListener('click', () => setMenu(false));
    });

    if (dropdown && dropdownButton) {
        dropdownButton.addEventListener('click', event => {
            event.stopPropagation();
            const open = dropdown.classList.toggle('open');
            dropdownButton.setAttribute('aria-expanded', String(open));
        });

        document.addEventListener('click', event => {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('open');
                dropdownButton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) {
            setMenu(false);
        }
    });
}

function initHero() {
    const hero = document.querySelector('.hero');
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');

    if (!hero || slides.length < 2) {
        return;
    }

    const delay = 6500;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let current = 0;
    let timer = null;

    const show = index => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('active', i === current));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === current));
    };

    const stop = () => {
        clearInterval(timer);
        timer = null;
    };

    const start = () => {
        stop();

        if (!reduceMotion) {
            timer = setInterval(() => show(current + 1), delay);
        }
    };

    const go = index => {
        show(index);
        start();
    };

    hero.querySelector('.hero-prev').addEventListener('click', () => go(current - 1));
    hero.querySelector('.hero-next').addEventListener('click', () => go(current + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));

    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);

    let touchStart = 0;

    hero.addEventListener(
        'touchstart',
        event => {
            touchStart = event.changedTouches[0].clientX;
            stop();
        },
        { passive: true }
    );

    hero.addEventListener(
        'touchend',
        event => {
            const distance = event.changedTouches[0].clientX - touchStart;

            if (Math.abs(distance) > 50) {
                go(distance < 0 ? current + 1 : current - 1);
            } else {
                start();
            }
        },
        { passive: true }
    );

    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

    start();
}

function initReveal() {
    const targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom, .stagger');

    document.querySelectorAll('.stagger').forEach(group => {
        Array.from(group.children).forEach((child, index) => {
            child.style.transitionDelay = `${index * 90}ms`;
        });
    });

    const reveal = element => {
        element.classList.add('visible');

        if (element.classList.contains('stagger')) {
            const total = element.children.length * 90 + 800;
            setTimeout(() => {
                element.classList.add('done');
                Array.from(element.children).forEach(child => {
                    child.style.transitionDelay = '';
                });
            }, total);
        }
    };

    if (!('IntersectionObserver' in window)) {
        targets.forEach(reveal);
        return;
    }

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -6% 0px' }
    );

    targets.forEach(target => observer.observe(target));
}

function initFaq() {
    const items = document.querySelectorAll('.faq-item');

    const close = item => {
        item.classList.remove('open');
        item.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
        item.querySelector('.faq-answer').style.maxHeight = null;
    };

    items.forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');

        question.addEventListener('click', () => {
            const wasOpen = item.classList.contains('open');
            items.forEach(close);

            if (!wasOpen) {
                item.classList.add('open');
                question.setAttribute('aria-expanded', 'true');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
        });
    });
}

function initFavorites() {
    const storageKey = 'toun_favorites';
    let saved = [];

    try {
        saved = JSON.parse(localStorage.getItem(storageKey)) || [];
    } catch (error) {
        saved = [];
    }

    const paint = (button, active) => {
        const icon = button.querySelector('i');
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
        icon.classList.toggle('fa-solid', active);
        icon.classList.toggle('fa-regular', !active);
    };

    document.querySelectorAll('.favorite').forEach(button => {
        const id = button.dataset.id;
        paint(button, saved.includes(id));

        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();

            const active = !saved.includes(id);
            saved = active ? [...saved, id] : saved.filter(item => item !== id);
            paint(button, active);

            try {
                localStorage.setItem(storageKey, JSON.stringify(saved));
            } catch (error) {
                return;
            }
        });
    });
}

function initMarquee() {
    const marquee = document.querySelector('[data-marquee]');
    const track = marquee ? marquee.querySelector('.marquee-track') : null;

    if (!track || !track.children.length) {
        return;
    }

    const originals = Array.from(track.children);

    const build = () => {
        track.querySelectorAll('[data-clone]').forEach(clone => clone.remove());

        const copy = source => {
            const clone = source.cloneNode(true);
            clone.setAttribute('data-clone', '');
            clone.setAttribute('aria-hidden', 'true');
            return clone;
        };

        let groupWidth = track.scrollWidth;
        const groups = Math.max(1, Math.ceil(marquee.offsetWidth / groupWidth));

        for (let i = 1; i < groups; i++) {
            originals.forEach(item => track.appendChild(copy(item)));
        }

        groupWidth = track.scrollWidth;
        Array.from(track.children).forEach(item => track.appendChild(copy(item)));

        if (animation) {
            animation.cancel();
        }

        animation = track.animate([{ transform: 'translateX(0)' }, { transform: `translateX(-${groupWidth}px)` }], {
            duration: (groupWidth / 45) * 1000,
            iterations: Infinity,
            easing: 'linear'
        });
    };

    let animation = null;

    build();

    marquee.addEventListener('mouseenter', () => animation && animation.pause());
    marquee.addEventListener('mouseleave', () => animation && animation.play());

    let resizeTimer = null;
    let lastWidth = window.innerWidth;

    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth !== lastWidth) {
                lastWidth = window.innerWidth;
                build();
            }
        }, 250);
    });
}
