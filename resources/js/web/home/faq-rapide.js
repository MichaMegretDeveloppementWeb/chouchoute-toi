/**
 * Accordéon FAQ rapide : page d'accueil
 */

document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('[data-home-faq-item]');

    items.forEach((item) => {
        const trigger = item.querySelector('[data-home-faq-trigger]');
        const content = item.querySelector('[data-home-faq-content]');
        const icon = item.querySelector('[data-home-faq-icon]');

        if (!trigger || !content) return;

        trigger.addEventListener('click', () => {
            const isOpen = content.classList.toggle('is-open');
            content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '0';
            if (icon) icon.classList.toggle('is-open', isOpen);
        });
    });
});
