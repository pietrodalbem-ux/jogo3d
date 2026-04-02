// js/controls.js

export const keys = { w: false, a: false, s: false, d: false, space: false };
export const mouse = { click: false };
export let isLocked = false; // Define se o mouse está travado no jogo

// Função para iniciar o sistema de controles
export function initControls(camera, rendererElement) {
    const ui = document.getElementById('ui-layer');

    // Quando clicar em qualquer lugar, tenta travar o mouse
    document.addEventListener('click', () => {
        // Se já estiver travado ou se for um clique de tiro, não faz nada
        if (isLocked) return;
        rendererElement.requestPointerLock();
    });

    // Escuta mudanças no estado do Pointer Lock
    document.addEventListener('pointerlockchange', () => {
        if (document.pointerLockElement === rendererElement) {
            isLocked = true;
            ui.classList.add('hidden'); // Esconde as instruções
        } else {
            isLocked = false;
            ui.classList.remove('hidden'); // Mostra as instruções
        }
    });

    // Teclado
    window.addEventListener('keydown', (e) => {
        if (!isLocked) return; // Só move se o jogo estiver ativo
        if (e.code === 'Space') keys.space = true;
        const key = e.key.toLowerCase();
        if (keys.hasOwnProperty(key)) keys[key] = true;
    });

    window.addEventListener('keyup', (e) => {
        if (e.code === 'Space') keys.space = false;
        const key = e.key.toLowerCase();
        if (keys.hasOwnProperty(key)) keys[key] = false;
    });

    // Mouse
    window.addEventListener('mousedown', (e) => {
        if (isLocked && e.button === 0) mouse.click = true; // Tiro
    });

    window.addEventListener('mouseup', (e) => {
        if (e.button === 0) mouse.click = false;
    });
}