document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mobile = document.getElementById('mobileMenu');
    if (mobile && sidebar) mobile.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.querySelectorAll('.menu-link').forEach(link =>
        link.addEventListener('click', () => {
            if (window.innerWidth <= 800 && sidebar) sidebar.classList.remove('open');
        })
    );
});
