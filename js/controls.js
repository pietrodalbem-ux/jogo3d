export const keys = {
    w: false, a: false, s: false, d: false,
    space: false // Nova tecla para o pulo
};

window.addEventListener('keydown', (e) => {
    if (e.code === 'Space') keys.space = true;
    const key = e.key.toLowerCase();
    if (keys.hasOwnProperty(key)) keys[key] = true;
});

window.addEventListener('keyup', (e) => {
    if (e.code === 'Space') keys.space = false;
    const key = e.key.toLowerCase();
    if (keys.hasOwnProperty(key)) keys[key] = false;
});