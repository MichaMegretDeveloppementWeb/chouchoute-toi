/* The one accordion of the public site · a single listener on the document, so
   a row the server sends after the page opens like the ones drawn with it. */

function toggle(item) {
    const content = item.querySelector('[data-accordion-content]');
    const icon = item.querySelector('[data-accordion-icon]');

    if (!content) return;

    const isOpen = content.classList.toggle('is-open');
    content.style.maxHeight = isOpen ? content.scrollHeight + 'px' : '0';
    if (icon) icon.classList.toggle('is-open', isOpen);
}

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;

    const item = event.target.closest('[data-accordion-trigger]')?.closest('[data-accordion-item]');

    if (item) toggle(item);
});
