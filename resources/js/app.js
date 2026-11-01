const zoomContainers = document.querySelectorAll('[data-zoom-container]');

zoomContainers.forEach((container) => {
    const image = container.querySelector('[data-zoom-image]');

    if (! image) {
        return;
    }

    const scale = Number(container.dataset.zoomScale) || 2;

    const move = (event) => {
        if (event.pointerType !== 'mouse') {
            return;
        }

        const bounds = container.getBoundingClientRect();
        const x = Math.max(0, Math.min(100, ((event.clientX - bounds.left) / bounds.width) * 100));
        const y = Math.max(0, Math.min(100, ((event.clientY - bounds.top) / bounds.height) * 100));

        image.style.transformOrigin = `${x}% ${y}%`;
        image.style.transform = `scale(${scale})`;
    };

    const reset = () => {
        image.style.transformOrigin = 'center';
        image.style.transform = 'scale(1)';
    };

    container.addEventListener('pointermove', move);
    container.addEventListener('pointerleave', reset);
});
