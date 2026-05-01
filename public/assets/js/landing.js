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

    // 3. Refined Parallax effect for hero images (avoids layout shifts)
    const heroImages = document.querySelectorAll('.img-card');
    const initialTransforms = new Map();
    
    heroImages.forEach(img => {
        initialTransforms.set(img, getComputedStyle(img).transform === 'none' ? '' : getComputedStyle(img).transform);
    });

    window.addEventListener('scroll', () => {
        const scrolled = window.scrollY;
        
        heroImages.forEach((img, index) => {
            if (scrolled > 600) return; // Stop after hero section
            const speed = (index + 1) * 0.1;
            const yPos = scrolled * speed;
            
            // Apply parallax while preserving initial rotation/state
            // If it's the bull card, we manually preserve the rotation to be safe
            let rotation = '';
            if (img.classList.contains('bull-card')) {
                rotation = 'rotate(-8deg)';
            }
            
            img.style.transform = `translate3d(0, ${yPos}px, 0) ${rotation}`;
        });
    }, { passive: true });

    console.log('Landing JS initialized successfully.');
});
