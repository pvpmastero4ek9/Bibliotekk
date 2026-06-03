document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-slider]').forEach(initSlider);

    const toast = document.querySelector('[data-toast]');
    if (toast) {
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

    const dateInput = document.getElementById('start_date');
    if (dateInput) {
        dateInput.addEventListener('input', (event) => {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.slice(0, 2) + '.' + value.slice(2);
            }
            if (value.length > 5) {
                value = value.slice(0, 5) + '.' + value.slice(5, 9);
            }
            event.target.value = value;
        });
    }
});

function initSlider(container) {
    const slides = Array.from(container.querySelectorAll('.slide'));
    const prevBtn = container.querySelector('.slider-prev');
    const nextBtn = container.querySelector('.slider-next');
    const dotsContainer = container.querySelector('.slider-dots');
    if (!slides.length) {
        return;
    }

    let current = 0;
    let timer = null;

    slides.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'slider-dot' + (index === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Слайд ' + (index + 1));
        dot.addEventListener('click', () => goTo(index));
        dotsContainer.appendChild(dot);
    });

    const dots = Array.from(dotsContainer.querySelectorAll('.slider-dot'));

    function goTo(index) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
    }

    function next() {
        goTo(current + 1);
    }

    function prev() {
        goTo(current - 1);
    }

    function startAuto() {
        stopAuto();
        timer = setInterval(next, 3000);
    }

    function stopAuto() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    prevBtn.addEventListener('click', () => {
        prev();
        startAuto();
    });

    nextBtn.addEventListener('click', () => {
        next();
        startAuto();
    });

    container.addEventListener('mouseenter', stopAuto);
    container.addEventListener('mouseleave', startAuto);

    startAuto();
}
