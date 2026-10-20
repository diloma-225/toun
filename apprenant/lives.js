document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');

            if (overlay) {
                overlay.classList.toggle('active');
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    document.querySelectorAll('.sidebar .menu-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 800) {
                sidebar?.classList.remove('open');
                overlay?.classList.remove('active');
            }
        });
    });

    const cards = document.querySelectorAll('.live-card');

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.12
        }
    );

    cards.forEach(card => observer.observe(card));

    document.querySelectorAll('[data-live-link]').forEach(button => {
        button.addEventListener('click', () => {
            const link = button.dataset.liveLink;

            if (link) {
                window.location.href = link;
            }
        });
    });
});
