/**
 * Shows the value of a slider next to it (A28: days per move are chosen with a slider).
 *
 * In a template:
 *   <output id="days-output">3</output>
 *   <input type="range" ... data-shows-in="days-output">
 * Without JavaScript the slider still works; only the number does not follow it.
 */

function connectSliderToOutput(slider) {
    const output = document.getElementById(slider.dataset.showsIn);
    if (!output) {
        return;
    }
    slider.addEventListener('input', () => {
        output.textContent = slider.value;
    });
}

document.querySelectorAll('input[type="range"][data-shows-in]').forEach(connectSliderToOutput);
