/**
 * Accordéon témoignages
 */

document.addEventListener('DOMContentLoaded', () => {
    const items = document.querySelectorAll('[data-accordion-item]');

    items.forEach((item) => {
        const trigger = item.querySelector('[data-accordion-trigger]');
        const content = item.querySelector('[data-accordion-content]');
        const icon = item.querySelector('[data-accordion-icon]');

        if (!trigger || !content) return;

        trigger.addEventListener('click', () => {
            const isOpen = content.classList.toggle('is-open');
            content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '0';
            if (icon) icon.classList.toggle('is-open', isOpen);
        });
    });
});
