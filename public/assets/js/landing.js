document.addEventListener('DOMContentLoaded', () => {
    // 1. Reveal on Scroll Animation using Intersection Observer
    const revealCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-active');
                observer.unobserve(entry.target);
            }
        });
    };

    const revealObserver = new IntersectionObserver(revealCallback, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    // Targets for reveal animation
    const revealTargets = document.querySelectorAll('.feature-card, section h2, .group.relative.rounded-\\[2\\.5rem\\]');
    revealTargets.forEach(target => {
        target.classList.add('reveal-hidden');
        revealObserver.observe(target);
    });

    // 2. Smooth Scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // 3. Hero images are now stationary (handled via CSS)

    console.log('Landing JS initialized successfully.');
});
