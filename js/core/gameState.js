import { GAME_CONFIG } from '../utils/constants.js';

export let GAME = { 
    state: 'INTRO', 
    hp: GAME_CONFIG.INITIAL_HP, 
    maxHp: GAME_CONFIG.MAX_HP, 
    dmgDealt: 0, 
    stage: 1, 
    maxStageUnlocked: 1, 
    map: 'forest', 
    weapon: 'assault', 
    power: 'none', 
    specialCharge: 0, 
    specialTimeLeft: 0, 
    timeFrozen: false, 
    bossActive: false,
    custom: { skinColor: '#e2e8f0', clothesColor: '#1e293b', weaponColor: '#1e293b' }
};

export function saveGameState() { 
    if (GAME.state === 'PLAYING' || GAME.state === 'LOBBY') {
        localStorage.setItem('synthetic_dawn_v5', JSON.stringify(GAME)); 
    }
}

export function loadGameState() {
    const savedData = localStorage.getItem('synthetic_dawn_v5');
    if (savedData) {
        const parsed = JSON.parse(savedData);
        if (parsed.hp > 0 && parsed.maxStageUnlocked) { 
            Object.assign(GAME, parsed);
            return true;
        }
    }
    return false;
}

export function resetGameState() {
    localStorage.removeItem('synthetic_dawn_v5');
    GAME.hp = GAME_CONFIG.INITIAL_HP;
    GAME.dmgDealt = 0;
    GAME.stage = 1;
    GAME.specialCharge = 0;
}
