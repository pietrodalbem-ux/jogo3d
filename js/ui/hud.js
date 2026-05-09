import { WEAPONS } from '../utils/constants.js';

export function updateHUD(GAME, currentAmmo, isReloading) {
    const hpEl = document.getElementById('player-hp-text');
    const hpBarEl = document.getElementById('player-hp-bar');
    const scoreEl = document.getElementById('score-display');
    const levelEl = document.getElementById('level-display');
    const ammoEl = document.getElementById('ammo-display');

    if (hpEl) hpEl.innerText = `HP: ${Math.round(GAME.hp)} / ${GAME.maxHp}`;
    if (hpBarEl) {
        hpBarEl.style.width = (GAME.hp / GAME.maxHp * 100) + '%';
        hpBarEl.style.background = GAME.hp < 100 ? '#ff1744' : 'linear-gradient(90deg, #00b0ff, #00ffcc)';
    }
    if (scoreEl) scoreEl.innerText = GAME.dmgDealt;
    if (levelEl) levelEl.innerText = GAME.stage;
    
    if (ammoEl) {
        if (!isReloading) {
            ammoEl.classList.remove('reloading');
            ammoEl.innerText = GAME.specialTimeLeft > 0 ? "∞ / ∞" : `${currentAmmo} / ${WEAPONS[GAME.weapon].mag}`;
            ammoEl.style.color = (currentAmmo <= 5 && GAME.specialTimeLeft <= 0) ? "#ff1744" : "#fff";
        }
    }
}

export function showWaveText(t) {
    const wi = document.getElementById('wave-info');
    if (!wi) return;
    wi.innerText = t;
    wi.style.display = 'block';
    wi.style.animation = 'none';
    void wi.offsetWidth;
    wi.style.animation = 'popIn 0.3s ease-out';
    setTimeout(() => { wi.style.display = 'none'; }, 2000);
}

export function showHitMarker(text, color, pts, GAME) {
    const hc = document.getElementById('hit-crosshair');
    if (hc) {
        hc.style.display = 'block';
        hc.style.opacity = '1';
        setTimeout(() => hc.style.opacity = '0', 100);
    }

    const hm = document.getElementById('hit-marker');
    if (hm) {
        hm.innerText = text;
        hm.style.color = color;
        hm.style.display = 'block';
        hm.classList.remove('hit-anim');
        void hm.offsetWidth;
        hm.classList.add('hit-anim');
    }

    GAME.dmgDealt += Math.round(pts);
    const scoreEl = document.getElementById('score-display');
    if (scoreEl) scoreEl.innerText = GAME.dmgDealt;
}
