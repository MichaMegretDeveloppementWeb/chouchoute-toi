/**
 * Animations d'entrée au scroll — IntersectionObserver
 * Chargé globalement via le layout pour toutes les pages.
 */

document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('[data-animate]');

    if (!animatedElements.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15 },
    );

    animatedElements.forEach((el) => observer.observe(el));
});
