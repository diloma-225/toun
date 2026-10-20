document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.querySelector('.dropdown');
    if (!dropdown) return;

    const trigger = dropdown.querySelector('.dropdown-link');

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        dropdown.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove('open');
        }
    });

    dropdown.querySelectorAll('.dropdown-item').forEach(function (item) {
        item.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });
    });
});
