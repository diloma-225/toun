document.addEventListener('DOMContentLoaded', function () {
    var header = document.querySelector('.toun-header');
    var toggle = document.querySelector('.menu-mobile');
    if (toggle && header) {
        toggle.addEventListener('click', function () {
            header.classList.toggle('is-open');
            var expanded = header.classList.contains('is-open');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            toggle.innerHTML = expanded ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-bars"></i>';
        });
    }

    var dropBtn = document.querySelector('.dropdown .drop-btn');
    var dropdown = document.querySelector('.dropdown');
    if (dropBtn && dropdown) {
        dropBtn.addEventListener('click', function (e) {
            if (window.innerWidth <= 980) {
                e.preventDefault();
                dropdown.classList.toggle('is-open');
            }
        });
    }
});
