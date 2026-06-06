document.addEventListener("DOMContentLoaded", function() {
    const header = document.querySelector('.site-header');
    if (!header) return;
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add("header-shrink");
        } else {
            header.classList.remove("header-shrink");
        }
    });
});