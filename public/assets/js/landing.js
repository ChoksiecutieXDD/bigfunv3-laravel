document.addEventListener('DOMContentLoaded', function () {
    const navbar = document.getElementById('navbar');

    const handleScroll = () => {
        if (window.scrollY > 50) {
            // Only update if it DOESN'T already have the scrolled class
            if (!navbar.classList.contains('nav-scrolled')) {
                navbar.classList.remove('nav-transparent');
                navbar.classList.add('nav-scrolled');
            }
        } else {
            // Only update if it DOESN'T already have the transparent class
            if (!navbar.classList.contains('nav-transparent')) {
                navbar.classList.remove('nav-scrolled');
                navbar.classList.add('nav-transparent');
            }
        }
    };

    // Run once immediately when the page loads
    handleScroll();

    // Listen for scroll events smoothly
    window.addEventListener('scroll', handleScroll);
});