/* The testimonials, asked once the page is drawn · the server sends the whole
   section, or nothing when there is no review to show. */

function showFailure(section) {
    section.querySelectorAll('[data-reviews-waiting]').forEach((placeholder) => placeholder.remove());
    section.querySelector('[data-reviews-failure]')?.removeAttribute('hidden');
    section.removeAttribute('aria-busy');
}

async function loadReviews(section) {
    try {
        const response = await fetch(section.dataset.reviewsSource, { headers: { Accept: 'text/html' } });

        if (response.status === 204) {
            section.remove();
            return;
        }

        if (!response.ok) throw new Error(`The reviews answered ${response.status}.`);

        const template = document.createElement('template');
        template.innerHTML = (await response.text()).trim();
        section.replaceWith(template.content);
    } catch {
        showFailure(section);
    }
}

const waiting = document.querySelector('[data-reviews-source]');

if (waiting) loadReviews(waiting);
