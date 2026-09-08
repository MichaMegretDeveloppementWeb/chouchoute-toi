/* Despite its name, this file carries the site-wide scroll-in observer that
   puts `is-visible` on every `[data-animate]`, not footer behaviour. The web
   layout loads it on every page. */

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
