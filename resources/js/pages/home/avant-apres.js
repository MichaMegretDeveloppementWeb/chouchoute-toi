document.addEventListener('DOMContentLoaded', () => {
    const slider = document.querySelector('[data-avant-apres-slider]');

    if (!slider) {
        return;
    }

    const slides = slider.querySelectorAll('[data-avant-apres-slide]');
    const dots = slider.querySelectorAll('[data-avant-apres-dots] button');
    const prevBtn = slider.querySelector('[data-avant-apres-prev]');
    const nextBtn = slider.querySelector('[data-avant-apres-next]');
    const slideLabel = slider.querySelector('[data-avant-apres-label]');
    const slideNames = ['Naturelle', 'Volume Léger', 'Volume Mixte', 'Volume Intense'];
    let isDragging = false;
    let activeSlide = slides[0];
    let currentIndex = 0;

    /**
     * @param {HTMLElement} slide
     * @param {number} percentage 0–100
     */
    function setSplitPosition(slide, percentage) {
        const clamped = Math.max(0, Math.min(100, percentage));
        slide.style.setProperty('--split-position', `${clamped}%`);
    }

    /**
     * @param {HTMLElement} slide
     * @param {number} clientX
     * @returns {number}
     */
    function getPercentageFromX(slide, clientX) {
        const rect = slide.getBoundingClientRect();
        const x = clientX - rect.left;

        return (x / rect.width) * 100;
    }

    function onPointerDown() {
        isDragging = true;
        document.body.style.cursor = 'ew-resize';
        document.body.style.userSelect = 'none';
    }

    /** @param {number} clientX */
    function onPointerMove(clientX) {
        if (!isDragging || !activeSlide) {
            return;
        }

        const percentage = getPercentageFromX(activeSlide, clientX);
        setSplitPosition(activeSlide, percentage);
    }

    function onPointerUp() {
        if (!isDragging) {
            return;
        }

        isDragging = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
    }

    /** @param {number} index */
    function goToSlide(index) {
        currentIndex = index;

        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('avant-apres__dot--active', i === index);
        });

        if (slideLabel) {
            slideLabel.textContent = slideNames[index] || '';
        }

        activeSlide = slides[index];
        setSplitPosition(activeSlide, 50);
    }

    slides.forEach((slide) => {
        setSplitPosition(slide, 50);

        const handle = slide.querySelector('[data-avant-apres-handle]');

        if (handle) {
            handle.addEventListener('mousedown', (e) => {
                e.preventDefault();
                onPointerDown();
            });

            handle.addEventListener('touchstart', (e) => {
                e.preventDefault();
                onPointerDown();
            }, { passive: false });
        }

        slide.addEventListener('click', (e) => {
            if (!isDragging) {
                const percentage = getPercentageFromX(slide, e.clientX);
                setSplitPosition(slide, percentage);
            }
        });
    });

    document.addEventListener('mousemove', (e) => {
        onPointerMove(e.clientX);
    });

    document.addEventListener('mouseup', () => {
        onPointerUp();
    });

    document.addEventListener('touchmove', (e) => {
        if (isDragging && e.touches.length > 0) {
            onPointerMove(e.touches[0].clientX);
        }
    }, { passive: true });

    document.addEventListener('touchend', () => {
        onPointerUp();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const index = parseInt(dot.dataset.slideIndex, 10);
            goToSlide(index);
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            const index = (currentIndex - 1 + slides.length) % slides.length;
            goToSlide(index);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            const index = (currentIndex + 1) % slides.length;
            goToSlide(index);
        });
    }
});
