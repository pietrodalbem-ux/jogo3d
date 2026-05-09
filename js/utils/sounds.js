// Sound synthesis for Synthetic Dawn
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playSynthSound(freq, type, duration, volume = 0.1) {
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    
    osc.type = type;
    osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(freq / 2, audioCtx.currentTime + duration);
    
    gain.gain.setValueAtTime(volume, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    
    osc.start();
    osc.stop(audioCtx.currentTime + duration);
}

let lastHitTime = 0;
let lastStepTime = 0;

export const SOUNDS = {
    shoot: (weapon) => {
        if (weapon === 'assault') playSynthSound(400, 'square', 0.1, 0.05);
        else if (weapon === 'shotgun') playSynthSound(200, 'sawtooth', 0.1, 0.1);
        else if (weapon === 'sniper') playSynthSound(800, 'triangle', 0.2, 0.1);
    },
    reload: () => playSynthSound(100, 'sine', 0.5, 0.03),
    step: () => {
        const now = Date.now();
        if (now - lastStepTime < 100) return;
        lastStepTime = now;
        playSynthSound(50, 'sine', 0.05, 0.02);
    },
    hit: () => {
        const now = Date.now();
        if (now - lastHitTime < 50) return; // Limitar sons de impacto
        lastHitTime = now;
        playSynthSound(150, 'square', 0.05, 0.05);
    },
    bossSpawn: () => {
        playSynthSound(100, 'sawtooth', 1.0, 0.2);
        setTimeout(() => playSynthSound(80, 'sawtooth', 1.0, 0.2), 200);
    },
    explosion: () => playSynthSound(60, 'sawtooth', 0.8, 0.2)
};

