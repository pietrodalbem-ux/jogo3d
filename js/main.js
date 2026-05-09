import * as Engine from './core/engine.js';
import { GAME, saveGameState, resetGameState } from './core/gameState.js';

// Expose necessary functions to the global scope for HTML event handlers
window.GAME = GAME;
window.startGame = Engine.startGame;
window.activateSpecial = Engine.activateSpecial;
window.updateLobbyModel = Engine.updateLobbyModel;
window.openCustomizeModal = Engine.openCustomizeModal;
window.closeCustomizeModal = Engine.closeCustomizeModal;

window.showLobby = () => {
    console.log("Sistema: Transição neural para LOBBY...");
    GAME.state = 'LOBBY';
    
    // Esconder todas as telas e mostrar o lobby
    document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden'));
    document.getElementById('screen-lobby').classList.remove('hidden');
    
    // Garantir que o HUD do jogo esteja oculto
    document.getElementById('hud-bottom-left').classList.add('hidden');
    document.getElementById('hud-top-right').classList.add('hidden');
    
    // Garantir que o mouse esteja visível
    document.exitPointerLock();
};


window.resumeFromPause = () => {
    const canvas = document.getElementById('webgl-canvas');
    if (canvas) canvas.requestPointerLock();
};


window.selectLobbyWeapon = (type) => {
    GAME.weapon = type;
    document.querySelectorAll('.weapon-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('btn-' + type).classList.add('selected');
    Engine.updateLobbyModel();
};

window.updateLobbyColors = () => {
    GAME.custom.skinColor = document.getElementById('color-skin').value;
    GAME.custom.clothesColor = document.getElementById('color-clothes').value;
    GAME.custom.weaponColor = document.getElementById('color-weapon').value;
    Engine.updateLobbyModel();
};

window.goToMapSelect = () => {
    GAME.state = 'MAP_SELECT';
    document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden'));
    updateTreeUI();
    document.getElementById('screen-map').classList.remove('hidden');
};

window.selectMapTree = (sn, mt) => {
    GAME.stage = sn;
    GAME.map = mt;
    GAME.hp = GAME.maxHp;
    if (sn === 1) {
        GAME.dmgDealt = 0;
        GAME.specialCharge = 0;
        GAME.power = 'none';
    }
    Engine.startGame();
};

window.selectPower = (p) => {
    GAME.power = p;
    GAME.specialCharge = 0;
    document.getElementById('special-bar').style.width = '0%';
    GAME.maxStageUnlocked = Math.max(GAME.maxStageUnlocked, GAME.stage + 1);
    saveGameState();
    window.goToMapSelect();
};

window.clearSaveAndStart = () => {
    resetGameState();
    document.getElementById('save-prompt').classList.add('hidden');
};

function updateTreeUI() {
    if (GAME.maxStageUnlocked >= 2) {
        document.getElementById('node-2').classList.replace('locked', 'unlocked');
        document.getElementById('line-1').setAttribute('stroke', '#00ffcc');
        document.getElementById('lock-2').style.display = 'none';
    }
    if (GAME.maxStageUnlocked >= 3) {
        document.getElementById('node-3').classList.replace('locked', 'unlocked');
        document.getElementById('line-2').setAttribute('stroke', '#00ffcc');
        document.getElementById('lock-3').style.display = 'none';
    }
}

// Initialize the game
Engine.init();
