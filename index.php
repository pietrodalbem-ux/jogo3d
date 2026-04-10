<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paralisia 3D - Arcade FPS</title>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background-color: #000; font-family: 'Press Start 2P', cursive; user-select: none; }
        #game-container { position: relative; width: 100%; height: 100%; }
        #webgl-canvas { display: block; width: 100%; height: 100%; }
        
        .screen { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; background: rgba(0,0,0,0.85); z-index: 10; color: white; text-align: center; transition: opacity 0.5s; backdrop-filter: blur(5px); }
        .hidden { display: none !important; pointer-events: none; }
        
        .btn { background: #ff1744; color: white; border: 4px solid #fff; padding: 15px 30px; font-family: inherit; font-size: 14px; cursor: pointer; margin: 10px; text-transform: uppercase; transition: transform 0.1s; box-shadow: 4px 4px 0 #000; }
        .btn:hover { background: #ff5252; transform: scale(1.05); }
        .btn:active { transform: scale(0.95); box-shadow: 0px 0px 0 #000; }
        .btn-green { background: #00e676; } .btn-green:hover { background: #69f0ae; }
        .btn-blue { background: #00b0ff; } .btn-blue:hover { background: #40c4ff; }
        .btn-purple { background: #aa00ff; } .btn-purple:hover { background: #e040fb; }
        
        /* Estilo dos Cartões do Lobby */
        .weapon-btn { background: rgba(20,20,20,0.9); border: 2px solid #444; border-radius: 8px; padding: 15px; width: 220px; color: #fff; cursor: pointer; text-align: center; transition: all 0.2s; font-family: inherit; }
        .weapon-btn:hover { border-color: #ffea00; transform: translateY(-5px); background: rgba(40,40,40,0.9); }
        .weapon-btn.selected { border-color: #ff1744; background: rgba(50,10,10,0.9); box-shadow: 0 0 15px rgba(255,23,68,0.5); transform: translateY(-5px); }
        
        #crosshair { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 24px; height: 24px; z-index: 5; pointer-events: none; display: none; transition: transform 0.1s; }
        #crosshair::before, #crosshair::after { content: ''; position: absolute; background: #00ffcc; box-shadow: 0 0 8px #00ffcc; transition: background 0.2s; }
        #crosshair::before { top: 11px; left: 0; width: 24px; height: 2px; }
        #crosshair::after { top: 0; left: 11px; width: 2px; height: 24px; }
        
        #hud { position: absolute; bottom: 20px; left: 20px; z-index: 5; display: none; text-align: left; text-shadow: 3px 3px 0 #000; }
        .heart { color: #ff1744; font-size: 32px; margin-right: 5px; }
        #ammo-display { font-size: 24px; color: #fff; margin-top: 10px; }
        .reloading { color: #ff1744 !important; animation: blink 0.5s infinite; }
        
        #special-container { margin-top: 15px; width: 200px; height: 12px; background: #333; border: 2px solid #fff; position: relative; }
        #special-bar { width: 0%; height: 100%; background: #00ffcc; box-shadow: 0 0 10px #00ffcc; transition: width 0.2s; }
        #special-text { font-size: 10px; color: #fff; position: absolute; top: -15px; left: 0; display: none; animation: blink 0.5s infinite; }

        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0; } 100% { opacity: 1; } }

        #hud-top-right { position: absolute; top: 20px; right: 20px; z-index: 5; display: none; text-align: right; text-shadow: 3px 3px 0 #000; color: #fff; font-size: 16px; line-height: 2; }
        .score-text { color: #ffea00; font-size: 24px; }
        .level-text { color: #00ffcc; font-size: 18px; margin-bottom: 5px; }

        #wave-info { position: absolute; top: 35%; left: 50%; transform: translate(-50%, -50%); z-index: 5; display: none; color: #00ffcc; text-shadow: 6px 6px 0 #000; font-size: 48px; text-align: center; letter-spacing: 4px; pointer-events: none; animation: popIn 0.3s ease-out; }
        @keyframes popIn { 0% { transform: translate(-50%, -50%) scale(0.5); opacity: 0; } 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; } }
        
        #boss-ui { position: absolute; top: 80px; left: 50%; transform: translateX(-50%); width: 60%; max-width: 600px; z-index: 5; text-align: center; display: none; }
        #boss-name { color: #ff1744; text-shadow: 2px 2px 0 #000; font-size: 16px; margin-bottom: 5px; letter-spacing: 2px; }
        #boss-hp-bg { width: 100%; height: 25px; background: #222; border: 3px solid #fff; box-shadow: 4px 4px 0 #000; }
        #boss-hp-bar { width: 100%; height: 100%; background: #ff1744; transition: width 0.2s; }

        #hit-marker { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0); z-index: 5; color: #ff1744; font-size: 16px; display: none; pointer-events: none; text-shadow: 2px 2px 0 #000; opacity: 1; transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .hit-anim { transform: translate(-50%, -50%) scale(1.5) !important; opacity: 0 !important; }

        #damage-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, transparent 40%, rgba(255,0,0,0.8) 100%); opacity: 0; z-index: 4; pointer-events: none; transition: opacity 0.1s; }
        
        #special-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; box-shadow: inset 0 0 100px rgba(0, 255, 204, 0.0); pointer-events: none; z-index: 3; transition: box-shadow 0.3s; }
        .special-active-screen { box-shadow: inset 0 0 150px rgba(0, 255, 204, 0.6) !important; }
        .time-frozen-screen { background: rgba(0, 100, 255, 0.15) !important; filter: grayscale(50%); backdrop-filter: blur(1px); }

        .custom-box { border: 4px solid #444; padding: 20px; margin: 10px; background: #111; box-shadow: 4px 4px 0 #000; }
    </style>
</head>
<body>

<div id="game-container">
    <canvas id="webgl-canvas"></canvas>
    <div id="damage-overlay"></div>
    <div id="special-overlay"></div>
    <div id="crosshair"></div>
    <div id="hit-marker">HIT!</div>
    
    <div id="hud">
        <div id="lives-display">❤️❤️❤️</div>
        <div id="ammo-display">30/30</div>
        <div id="special-container">
            <div id="special-text">[Q] PODER MÁXIMO</div>
            <div id="special-bar"></div>
        </div>
    </div>
    
    <div id="hud-top-right">
        <div class="level-text">FASE <span id="level-display">1</span></div>
        <div>SCORE: <span id="score-display" class="score-text">0</span></div>
    </div>

    <div id="wave-info">INÍCIO DA FASE</div>

    <div id="boss-ui">
        <div id="boss-name">O REI CAVEIRA</div>
        <div id="boss-hp-bg"><div id="boss-hp-bar"></div></div>
    </div>

    <!-- TELA SPLASH -->
    <div id="screen-splash" class="screen" style="background: #0a0a0a; z-index: 20;">
        <h1 class="text-5xl md:text-7xl mb-4 text-red-600 drop-shadow-[6px_6px_0_#fff]">PARALISIA 3D</h1>
        <h2 class="text-lg md:text-2xl text-gray-400 mb-12">Arcade Edition</h2>
        <div id="save-prompt" class="hidden mb-8 p-6 bg-gray-900 border-4 border-yellow-500 rounded">
            <p class="text-yellow-400 text-sm mb-4">Jogo guardado encontrado!</p>
            <button class="btn btn-green" onclick="resumeGame()">Continuar</button>
            <button class="btn" onclick="clearSaveAndStart()">Novo Jogo</button>
        </div>
        <p id="start-prompt" class="animate-pulse text-yellow-400 text-sm">Pressione QUALQUER TECLA ou CLIQUE</p>
    </div>

    <!-- TELA LOBBY 3D REFORMULADA (CENTRALIZADA) -->
    <div id="screen-lobby" class="screen hidden" style="background: transparent; backdrop-filter: none; justify-content: flex-end; padding-bottom: 5vh; z-index: 15;">
        <h2 class="absolute top-10 text-4xl text-yellow-400 drop-shadow-[4px_4px_0_#000] tracking-widest">ARSENAL</h2>
        
        <div class="flex flex-row gap-4 max-w-4xl justify-center z-10 w-full mb-6">
            <button class="weapon-btn selected" id="btn-assault" onclick="selectLobbyWeapon('assault')">
                <h3 class="text-yellow-400 text-base mb-1">FUZIL</h3>
                <p class="text-[10px] text-gray-300">Dano: 40/100<br>Rápido | 30 Balas</p>
            </button>
            <button class="weapon-btn" id="btn-shotgun" onclick="selectLobbyWeapon('shotgun')">
                <h3 class="text-yellow-400 text-base mb-1">ESCOPETA</h3>
                <p class="text-[10px] text-gray-300">Dano: 250/500<br>Perto | 8 Balas</p>
            </button>
            <button class="weapon-btn" id="btn-sniper" onclick="selectLobbyWeapon('sniper')">
                <h3 class="text-yellow-400 text-base mb-1">SNIPER</h3>
                <p class="text-[10px] text-gray-300">Dano: 150/250<br>Longe | 5 Balas</p>
            </button>
        </div>

        <button class="btn btn-green text-lg px-8 py-3 z-10 shadow-[4px_4px_0_#000]" onclick="goToMapSelect()">SELECIONAR ARENA ></button>
    </div>

    <!-- TELA MAPA -->
    <div id="screen-map" class="screen hidden" style="z-index: 20;">
        <h2 class="text-3xl text-yellow-400 mb-8 drop-shadow-[3px_3px_0_#000]">Escolha a Arena</h2>
        <div class="flex flex-wrap justify-center gap-6 max-w-5xl">
            <div class="custom-box cursor-pointer hover:border-green-500 w-64" onclick="selectMap('forest')">
                <h3 class="mb-4 text-green-400">Floresta Densa</h3><p class="text-xs text-gray-300 leading-5">Árvores, pedras, arbustos e pirilampos à noite.</p>
            </div>
            <div class="custom-box cursor-pointer hover:border-red-500 w-64" onclick="selectMap('dungeon')">
                <h3 class="mb-4 text-red-400">Catacumbas</h3><p class="text-xs text-gray-300 leading-5">Masmorra antiga iluminada por tochas de fogo.</p>
            </div>
            <div class="custom-box cursor-pointer hover:border-blue-500 w-64" onclick="selectMap('neon')">
                <h3 class="mb-4 text-blue-400">Cyber Neon</h3><p class="text-xs text-gray-300 leading-5">Mundo digital com chão em grid brilhante.</p>
            </div>
        </div>
    </div>

    <!-- TELA SELEÇÃO DE PODER -->
    <div id="screen-power-select" class="screen hidden bg-black bg-opacity-95" style="z-index: 20;">
        <h1 class="text-4xl text-yellow-400 drop-shadow-[4px_4px_0_#000] mb-4">FASE 1 CONCLUÍDA!</h1>
        <p class="text-white mb-12">Escolha uma Habilidade Especial para a Fase 2:</p>
        <div class="flex flex-wrap justify-center gap-6 max-w-5xl">
            <div class="custom-box cursor-pointer hover:border-blue-500 w-64 flex flex-col justify-between" onclick="selectPower('kamehameha')">
                <div><h3 class="mb-4 text-blue-400 text-xl">KAMEHAMEHA</h3><p class="text-xs text-gray-300 leading-5">Dispara um raio destrutivo de energia pura que aniquila inimigos à frente.</p></div>
                <button class="btn btn-blue w-full mt-4" style="margin: 10px 0 0 0;">Escolher</button>
            </div>
            <div class="custom-box cursor-pointer hover:border-red-500 w-64 flex flex-col justify-between" onclick="selectPower('supernova')">
                <div><h3 class="mb-4 text-red-400 text-xl">SUPER NOVA</h3><p class="text-xs text-gray-300 leading-5">Explosão massiva à tua volta, desintegrando os capangas num raio de 30m.</p></div>
                <button class="btn w-full mt-4" style="margin: 10px 0 0 0;">Escolher</button>
            </div>
            <div class="custom-box cursor-pointer hover:border-purple-500 w-64 flex flex-col justify-between" onclick="selectPower('relativity')">
                <div><h3 class="mb-4 text-purple-400 text-xl">RELATIVIDADE</h3><p class="text-xs text-gray-300 leading-5">Congela o tempo e imobiliza todos os inimigos durante 5 segundos.</p></div>
                <button class="btn btn-purple w-full mt-4" style="margin: 10px 0 0 0;">Escolher</button>
            </div>
        </div>
    </div>

    <!-- TELAS FINAIS -->
    <div id="screen-gameover" class="screen hidden bg-red-900 bg-opacity-95" style="z-index: 20;">
        <h1 class="text-6xl text-black drop-shadow-[4px_4px_0_#fff] mb-4">MORRESTE</h1>
        <p class="text-yellow-400 text-2xl mb-8">SCORE FINAL: <span id="final-score">0</span></p>
        <button class="btn" onclick="location.reload()">Menu Principal</button>
    </div>
    
    <div id="screen-win" class="screen hidden bg-green-900 bg-opacity-95" style="z-index: 20;">
        <h1 class="text-6xl text-white drop-shadow-[4px_4px_0_#000] mb-4">VITÓRIA!</h1>
        <p class="text-white mb-2">Eliminaste a Dupla Ameaça e limpaste a arena.</p>
        <p class="text-yellow-400 text-2xl mb-8">SCORE FINAL: <span id="win-score">0</span></p>
        <button class="btn btn-green" onclick="location.reload()">Jogar Novamente</button>
    </div>
</div>

<script>
    // --- ESTADOS GLOBAIS E SISTEMAS ---
    let GAME = {
        state: 'SPLASH', lives: 5, score: 0, stage: 1, map: 'forest',
        custom: { weaponType: 'assault' }, // Cores removidas do save, usaremos padrão
        power: 'none', specialCharge: 0, specialTimeLeft: 0, timeFrozen: false
    };

    const WEAPONS_STATS = {
        assault: { fireRate: 110, damage: 40, headMult: 2.5, pellets: 1, spread: 0.03, adsSpread: 0.005, range: 100, recoil: 0.05, shake: 0.02, magSize: 30, reloadTime: 1.5 },
        shotgun: { fireRate: 800, damage: 25, headMult: 2.0, pellets: 10, spread: 0.18, adsSpread: 0.08, range: 25, recoil: 0.3, shake: 0.15, magSize: 8, reloadTime: 2.5 },
        sniper:  { fireRate: 1200, damage: 150, headMult: 1.6667, pellets: 1, spread: 0.1, adsSpread: 0.0, range: 300, recoil: 0.5, shake: 0.1, magSize: 5, reloadTime: 3.0 }
    };

    // GESTÃO DE ONDAS
    let waveActive = false, capangasTarget = 0, capangasKilled = 0, enemiesSpawned = 0;
    let bossSpawned = false, spawnTimer = 0;

    // SISTEMA THREE.JS GLOBAL (Apenas 1 Renderer)
    let scene, camera, renderer;
    let lobbyScene, lobbyCamera, lobbyCharacter, lobbyRing, lobbyRing2;
    let playerGroup, gunMesh;
    let bullets = [], enemies = [], obstacles = [], particles = [], bossProjectiles = [], visualEffects = [], neonObjects = [];
    let prevTime = performance.now();
    let isInitGame = false;

    const SHARED = {
        geo: {
            bullet: new THREE.SphereGeometry(0.06, 4, 4),
            particle: new THREE.BoxGeometry(0.2, 0.2, 0.2),
            enemyBody: new THREE.BoxGeometry(1, 1.5, 0.8),
            enemyHead: new THREE.BoxGeometry(0.8, 0.8, 0.8),
            bossBody: new THREE.BoxGeometry(3, 4, 2),
            bossHead: new THREE.BoxGeometry(2, 2, 2),
            bazooka: new THREE.DodecahedronGeometry(1.2),
            leg: new THREE.BoxGeometry(0.4, 1.5, 0.4)
        },
        mat: {
            bulletNormal: new THREE.MeshBasicMaterial({ color: 0xffea00 }),
            bulletSpecial: new THREE.MeshBasicMaterial({ color: 0x00ffff })
        }
    };

    let currentAmmo = 0, isReloading = false, reloadTimer = 0;
    let moveForward = false, moveBackward = false, moveLeft = false, moveRight = false, jump = false, isSprinting = false;
    let velocity = new THREE.Vector3(), isAiming = false, isGrounded = true, playerInvulnerable = 0;
    let muzzleFlashLight, cameraShake = 0, currentRecoil = 0, recoilTarget = 0, gunSwayX = 0, gunSwayY = 0;
    const gunPosNormal = new THREE.Vector3(0.35, -0.25, -0.5), gunPosAim = new THREE.Vector3(0, -0.15, -0.3), gunPosSprint = new THREE.Vector3(0.4, -0.4, -0.4);
    let currentGunPos = new THREE.Vector3().copy(gunPosNormal);

    window.onload = () => {
        renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('webgl-canvas'), antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFSoftShadowMap;
        
        initLobby(); 
        animate(); // Inicia o loop Mestre de Renderização de forma contínua
        
        const savedData = localStorage.getItem('paralisia_save_v9');
        if (savedData) {
            const parsed = JSON.parse(savedData);
            if(parsed.lives > 0) {
                document.getElementById('save-prompt').classList.remove('hidden');
                document.getElementById('start-prompt').classList.add('hidden');
            } else localStorage.removeItem('paralisia_save_v9');
        }
    };

    function saveGameState() { if(GAME.state === 'PLAYING') localStorage.setItem('paralisia_save_v9', JSON.stringify(GAME)); }
    function resumeGame() { GAME = JSON.parse(localStorage.getItem('paralisia_save_v9')); startGame(); }
    function clearSaveAndStart() { localStorage.removeItem('paralisia_save_v9'); document.getElementById('save-prompt').classList.add('hidden'); document.getElementById('start-prompt').classList.remove('hidden'); }
    function hideAllScreens() { document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden')); }

    document.addEventListener('click', (e) => { 
        if(GAME.state === 'SPLASH' && !e.target.closest('#save-prompt')) { 
            GAME.state = 'LOBBY'; hideAllScreens(); document.getElementById('screen-lobby').classList.remove('hidden'); 
        } 
    });
    document.addEventListener('keydown', () => { 
        if(GAME.state === 'SPLASH' && document.getElementById('save-prompt').classList.contains('hidden')) { 
            GAME.state = 'LOBBY'; hideAllScreens(); document.getElementById('screen-lobby').classList.remove('hidden'); 
        } 
    });

    // --- CÓDIGO DO LOBBY 3D REFORMULADO (BEM NO CENTRO) ---
    function initLobby() {
        lobbyScene = new THREE.Scene();
        lobbyScene.background = new THREE.Color(0x050505);

        lobbyCamera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100);
        lobbyCamera.position.set(0, 1.4, 4.5); // Focado no centro do personagem

        const light = new THREE.DirectionalLight(0xffffff, 1.0);
        light.position.set(5, 10, 5); light.castShadow = true;
        lobbyScene.add(light);
        lobbyScene.add(new THREE.AmbientLight(0x444444));

        // Fundo Tech Atras do Personagem
        const wall = new THREE.Mesh(new THREE.PlaneGeometry(30, 20), new THREE.MeshStandardMaterial({color: 0x111111}));
        wall.position.set(0, 2, -4); lobbyScene.add(wall);
        
        lobbyRing = new THREE.Mesh(new THREE.TorusGeometry(2.5, 0.05, 16, 64), new THREE.MeshBasicMaterial({color: 0xff1744}));
        lobbyRing.position.set(0, 1.5, -2.5); lobbyScene.add(lobbyRing);
        
        lobbyRing2 = new THREE.Mesh(new THREE.TorusGeometry(2.3, 0.02, 16, 64), new THREE.MeshBasicMaterial({color: 0x00ffcc}));
        lobbyRing2.position.set(0, 1.5, -2.5); lobbyScene.add(lobbyRing2);

        // Holofote dramático
        const spot = new THREE.SpotLight(0xffffff, 2, 15, Math.PI/6, 0.5, 1);
        spot.position.set(0, 6, 2); spot.target.position.set(0, 1, 0);
        lobbyScene.add(spot); lobbyScene.add(spot.target);

        updateLobbyModel();
    }

    function selectLobbyWeapon(type) {
        GAME.custom.weaponType = type;
        document.querySelectorAll('.weapon-btn').forEach(b => b.classList.remove('selected'));
        document.getElementById('btn-' + type).classList.add('selected');
        updateLobbyModel();
    }

    function updateLobbyModel() {
        if(lobbyCharacter) lobbyScene.remove(lobbyCharacter);
        lobbyCharacter = new THREE.Group();
        
        const skinMat = new THREE.MeshStandardMaterial({color: 0xffcc99, roughness: 0.8}); 
        const clothesMat = new THREE.MeshStandardMaterial({color: 0x222222, roughness: 0.9});

        const body = new THREE.Mesh(SHARED.geo.enemyBody, clothesMat); body.position.y = 1.0; body.castShadow = true; lobbyCharacter.add(body);
        const head = new THREE.Mesh(SHARED.geo.enemyHead, skinMat); head.position.y = 2.2; head.castShadow = true; lobbyCharacter.add(head);
        const legL = new THREE.Mesh(SHARED.geo.leg, clothesMat); legL.position.set(-0.25, 0.1, 0); legL.castShadow = true; lobbyCharacter.add(legL);
        const legR = new THREE.Mesh(SHARED.geo.leg, clothesMat); legR.position.set(0.25, 0.1, 0); legR.castShadow = true; lobbyCharacter.add(legR);

        const weapon = buildWeaponMesh(); 
        weapon.position.set(0.4, 1.0, 0.5);
        weapon.rotation.y = -Math.PI / 8; 
        lobbyCharacter.add(weapon);
        
        lobbyCharacter.position.set(0, -0.5, 0); // Exatamente no centro
        lobbyScene.add(lobbyCharacter);
    }

    function buildWeaponMesh() {
        let w = new THREE.Group();
        const gunMat = new THREE.MeshStandardMaterial({ color: 0x444444, roughness: 0.2, metalness: 0.8 }); // Cor Padrão Cinza Tática
        const body = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.18, 0.6), gunMat); body.castShadow = true; w.add(body);
        const barrelMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.5, metalness: 0.8 });
        const barrel = new THREE.Mesh(new THREE.CylinderGeometry(0.025, 0.025, 0.5), barrelMat); barrel.rotation.x = Math.PI / 2; barrel.position.set(0, 0.05, -0.4); w.add(barrel);
        const handMat = new THREE.MeshStandardMaterial({ color: 0x111111, roughness: 0.9 }); // Luva Preta
        const hand = new THREE.Mesh(new THREE.BoxGeometry(0.2, 0.2, 0.2), handMat); hand.position.set(0.1, -0.1, 0.15); w.add(hand);

        if(GAME.custom.weaponType === 'shotgun') {
            const barrel2 = barrel.clone(); barrel2.position.set(0.04, 0.05, -0.4); w.add(barrel2); barrel.position.set(-0.04, 0.05, -0.4);
        } else if(GAME.custom.weaponType === 'sniper') {
            const scope = new THREE.Mesh(new THREE.CylinderGeometry(0.04, 0.04, 0.25), new THREE.MeshStandardMaterial({color:0x000000})); scope.rotation.x = Math.PI / 2; scope.position.set(0, 0.15, -0.1); w.add(scope);
        }
        return w;
    }

    function goToMapSelect() {
        GAME.state = 'MAP_SELECT'; hideAllScreens(); document.getElementById('screen-map').classList.remove('hidden');
    }

    function selectMap(mapType) {
        GAME.map = mapType; GAME.lives = 5; GAME.score = 0; GAME.stage = 1; GAME.specialCharge = 0; GAME.power = 'none';
        startGame();
    }

    // --- TRANSIÇÃO PARA O JOGO REAL (SEM TRAVAMENTOS) ---
    function startGame() {
        hideAllScreens();
        document.getElementById('crosshair').style.display = 'block';
        document.getElementById('hud').style.display = 'block';
        document.getElementById('hud-top-right').style.display = 'block';
        document.getElementById('boss-ui').style.display = 'none';
        
        currentAmmo = WEAPONS_STATS[GAME.custom.weaponType].magSize; isReloading = false;
        updatePowerText(); updateHUD();
        
        if(!isInitGame) { initGameScene(); isInitGame = true; }
        
        for(let e of enemies) disposeEnemy(e); enemies = [];
        for(let p of bossProjectiles) disposeMesh(p); bossProjectiles = [];
        
        startWaveLogic();
        document.getElementById('webgl-canvas').requestPointerLock();
        
        GAME.state = 'PLAYING'; 
        prevTime = performance.now(); // RESET DE TEMPO CRUCIAL
        saveGameState(); 
    }

    function startWaveLogic() {
        waveActive = true; enemiesSpawned = 0; capangasKilled = 0; bossSpawned = false;
        
        if(GAME.stage === 1) capangasTarget = 20;
        else if(GAME.stage === 2) capangasTarget = 30;

        document.getElementById('level-display').innerText = GAME.stage;
        showWaveText("FASE " + GAME.stage);
        spawnTimer = 1.0;
    }

    function selectPower(powerType) {
        GAME.power = powerType; GAME.specialCharge = 0; document.getElementById('special-bar').style.width = '0%';
        hideAllScreens();
        
        document.getElementById('webgl-canvas').requestPointerLock();
        GAME.stage = 2; updatePowerText(); updateHUD();
        startWaveLogic();

        GAME.state = 'PLAYING';
        prevTime = performance.now();
    }

    function updatePowerText() {
        if(GAME.power === 'none') { document.getElementById('special-text').style.display = 'none'; return; }
        let powerName = "";
        if(GAME.power === 'kamehameha') powerName = "[Q] KAMEHAMEHA";
        if(GAME.power === 'supernova') powerName = "[Q] SUPER NOVA";
        if(GAME.power === 'relativity') powerName = "[Q] RELATIVIDADE";
        document.getElementById('special-text').innerText = powerName;
    }

    function showWaveText(text) {
        const wi = document.getElementById('wave-info'); wi.innerText = text; wi.style.display = 'block';
        setTimeout(() => { wi.style.display = 'none'; }, 2000);
    }

    function initGameScene() {
        scene = new THREE.Scene();
        let bgColor = GAME.map === 'forest' ? 0x0a1505 : (GAME.map === 'dungeon' ? 0x1a0f0a : 0x050011); 
        scene.background = new THREE.Color(bgColor);
        let fogDist = GAME.map === 'dungeon' ? 40 : (GAME.map === 'forest' ? 100 : 70);
        scene.fog = new THREE.Fog(bgColor, 5, fogDist);

        camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        
        const hemiLight = new THREE.HemisphereLight(0xffffff, 0x222222, GAME.map === 'forest' ? 0.4 : (GAME.map === 'dungeon' ? 0.6 : 0.5));
        hemiLight.position.set(0, 50, 0); scene.add(hemiLight);

        const dirLightColor = GAME.map === 'forest' ? 0xaaccff : (GAME.map === 'dungeon' ? 0xffaa55 : 0xff00ff);
        const dirLight = new THREE.DirectionalLight(dirLightColor, 0.6);
        dirLight.position.set(20, 50, 20); dirLight.castShadow = true;
        dirLight.shadow.mapSize.width = 512; dirLight.shadow.mapSize.height = 512;
        scene.add(dirLight);

        if (GAME.map === 'dungeon') scene.add(new THREE.AmbientLight(0xff8844, 0.7));
        else if (GAME.map === 'neon') scene.add(new THREE.AmbientLight(0x00ffff, 0.4));

        createMapEnvironment();

        playerGroup = new THREE.Group(); playerGroup.position.set(0, 2, 0); scene.add(playerGroup); playerGroup.add(camera);
        const viewLight = new THREE.PointLight(0xffffff, GAME.map === 'dungeon' ? 0.8 : 0.6, 15); camera.add(viewLight);

        createGameWeapon();
    }

    function createMapEnvironment() {
        neonObjects = []; obstacles = [];
        const floorGeo = new THREE.PlaneGeometry(200, 200);
        let floorColor = GAME.map === 'forest' ? 0x1d3a1e : (GAME.map === 'dungeon' ? 0x3a2820 : 0x050011);
        const floorMat = new THREE.MeshStandardMaterial({ color: floorColor, roughness: 0.9 });
        const floor = new THREE.Mesh(floorGeo, floorMat); floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true; scene.add(floor);

        const wallMat = new THREE.MeshStandardMaterial({ color: GAME.map === 'dungeon' ? 0x221105 : (GAME.map === 'forest' ? 0x051105 : 0x110022) });
        const wallGeo = new THREE.BoxGeometry(200, 40, 2);
        const walls = [new THREE.Mesh(wallGeo, wallMat), new THREE.Mesh(wallGeo, wallMat), new THREE.Mesh(wallGeo, wallMat), new THREE.Mesh(wallGeo, wallMat)];
        walls[0].position.set(0, 20, -100); walls[1].position.set(0, 20, 100); walls[2].position.set(-100, 20, 0); walls[2].rotation.y = Math.PI/2; walls[3].position.set(100, 20, 0); walls[3].rotation.y = Math.PI/2;
        scene.add(...walls);

        if (GAME.map === 'neon') { const gridHelper = new THREE.GridHelper(200, 40, 0x00ffff, 0xff00ff); gridHelper.position.y = 0.1; scene.add(gridHelper); }

        for(let i=0; i<60; i++) {
            const px = (Math.random() - 0.5) * 180; const pz = (Math.random() - 0.5) * 180;
            if(Math.abs(px) < 15 && Math.abs(pz) < 15) continue; 

            if(GAME.map === 'forest') {
                if(Math.random() > 0.5) {
                    const tree = new THREE.Group();
                    const trunk = new THREE.Mesh(new THREE.CylinderGeometry(0.8, 1.2, 8), new THREE.MeshStandardMaterial({color: 0x3a2b12}));
                    trunk.position.y = 4; trunk.castShadow = true; tree.add(trunk);
                    const leaves = new THREE.Mesh(new THREE.ConeGeometry(4.5, 10, 8), new THREE.MeshStandardMaterial({color: 0x153c1b}));
                    leaves.position.y = 11; leaves.castShadow = true; tree.add(leaves);
                    tree.position.set(px, 0, pz); scene.add(tree); obstacles.push({x: px, z: pz, radius: 2.0}); 
                } else {
                    const bush = new THREE.Mesh(new THREE.DodecahedronGeometry(2), new THREE.MeshStandardMaterial({color: 0x1e4c2b, roughness: 1}));
                    bush.position.set(px, 1, pz); bush.castShadow = true; scene.add(bush); obstacles.push({x: px, z: pz, radius: 2.0});
                }
                if(i % 3 === 0) {
                    const firefly = new THREE.Mesh(new THREE.SphereGeometry(0.15, 4, 4), new THREE.MeshBasicMaterial({color: 0xaaff00}));
                    firefly.position.set(px, 2 + Math.random()*3, pz); scene.add(firefly);
                }
            } else if (GAME.map === 'dungeon') {
                const pillar = new THREE.Mesh(new THREE.BoxGeometry(4, 30, 4), new THREE.MeshStandardMaterial({color: 0x221a15, roughness: 1}));
                pillar.position.set(px, 15, pz); pillar.castShadow = true; pillar.receiveShadow = true; scene.add(pillar); obstacles.push({x: px, z: pz, radius: 2.5});
                const torchMesh = new THREE.Mesh(new THREE.BoxGeometry(0.5, 1, 0.5), new THREE.MeshBasicMaterial({color: 0xff6600}));
                torchMesh.position.set(px + 2.1, 5, pz); scene.add(torchMesh);
            } else if (GAME.map === 'neon') {
                if(i % 2 === 0) {
                    const pyramid = new THREE.Mesh(new THREE.ConeGeometry(3, 5, 4), new THREE.MeshStandardMaterial({color: 0x00ffff, emissive: 0x003333, wireframe: true}));
                    pyramid.position.set(px, 5 + Math.random() * 8, pz); pyramid.rotation.set(Math.random(), Math.random(), Math.random()); scene.add(pyramid); neonObjects.push(pyramid); 
                } else {
                    const ring = new THREE.Mesh(new THREE.TorusGeometry(2, 0.2, 8, 16), new THREE.MeshBasicMaterial({color: 0xff00ff}));
                    ring.position.set(px, 3 + Math.random() * 5, pz); scene.add(ring); neonObjects.push(ring);
                }
            }
        }
    }

    function createGameWeapon() {
        gunMesh = buildWeaponMesh();
        muzzleFlashLight = new THREE.PointLight(0xffea00, 3, 15); muzzleFlashLight.position.set(0, 0.05, -0.7); muzzleFlashLight.visible = false; gunMesh.add(muzzleFlashLight);
        camera.add(gunMesh); gunMesh.position.copy(gunPosNormal);
    }

    let euler = new THREE.Euler(0, 0, 0, 'YXZ');
    function onMouseMove(event) {
        if (document.pointerLockElement !== document.getElementById('webgl-canvas') || GAME.state !== 'PLAYING') return;
        euler.setFromQuaternion(camera.quaternion);
        const sens = isAiming ? 0.001 : 0.002;
        euler.y -= (event.movementX || 0) * sens; euler.x -= (event.movementY || 0) * sens;
        euler.x = Math.max(-Math.PI/2, Math.min(Math.PI/2, euler.x));
        camera.quaternion.setFromEuler(euler);
        gunSwayX = -(event.movementX || 0) * 0.001; gunSwayY = (event.movementY || 0) * 0.001;
    }

    function onKeyDown(event) {
        if(GAME.state !== 'PLAYING') return;
        switch (event.code) {
            case 'KeyW': moveForward = true; break; case 'KeyA': moveLeft = true; break; case 'KeyS': moveBackward = true; break; case 'KeyD': moveRight = true; break;
            case 'Space': if(isGrounded) jump = true; break; case 'ShiftLeft': case 'ShiftRight': isSprinting = true; break;
            case 'KeyR': startReload(); break; case 'KeyQ': activateSpecial(); break; 
        }
    }
    function onKeyUp(event) {
        if(GAME.state !== 'PLAYING') return;
        switch (event.code) {
            case 'KeyW': moveForward = false; break; case 'KeyA': moveLeft = false; break; case 'KeyS': moveBackward = false; break; case 'KeyD': moveRight = false; break;
            case 'ShiftLeft': case 'ShiftRight': isSprinting = false; break;
        }
    }
    function onMouseDown(event) {
        if(GAME.state !== 'PLAYING') return;
        if (document.pointerLockElement !== document.getElementById('webgl-canvas')) {
            document.getElementById('webgl-canvas').requestPointerLock();
            return;
        }
        if (event.button === 0) shoot();
        else if (event.button === 2) { isAiming = true; isSprinting = false; document.getElementById('crosshair').style.transform = 'translate(-50%, -50%) scale(0.5)'; document.getElementById('crosshair').style.opacity = '0.5'; }
    }
    function onMouseUp(event) {
        if(GAME.state !== 'PLAYING') return;
        if (event.button === 2) { isAiming = false; document.getElementById('crosshair').style.transform = 'translate(-50%, -50%) scale(1)'; document.getElementById('crosshair').style.opacity = '1'; }
    }
    window.addEventListener('resize', () => { 
        if(GAME.state === 'LOBBY') { lobbyCamera.aspect = window.innerWidth/window.innerHeight; lobbyCamera.updateProjectionMatrix(); }
        else if(GAME.state === 'PLAYING') { camera.aspect = window.innerWidth/window.innerHeight; camera.updateProjectionMatrix(); }
        renderer.setSize(window.innerWidth, window.innerHeight); 
    });
    document.addEventListener('contextmenu', e => e.preventDefault());

    function startReload() {
        if (isReloading || GAME.specialTimeLeft > 0 || currentAmmo === WEAPONS_STATS[GAME.custom.weaponType].magSize) return;
        isReloading = true; reloadTimer = WEAPONS_STATS[GAME.custom.weaponType].reloadTime;
        document.getElementById('ammo-display').innerText = "RECARREGANDO..."; document.getElementById('ammo-display').classList.add('reloading');
        gunMesh.rotation.x = -Math.PI / 4;
    }

    function addSpecialCharge(amount) {
        if(GAME.specialTimeLeft > 0 || GAME.power === 'none') return;
        GAME.specialCharge += amount;
        if(GAME.specialCharge >= 100) { GAME.specialCharge = 100; document.getElementById('special-text').style.display = 'block'; document.getElementById('crosshair').style.borderColor = '#00ffcc'; }
        document.getElementById('special-bar').style.width = GAME.specialCharge + '%';
    }

    function activateSpecial() {
        if(GAME.specialCharge >= 100 && GAME.specialTimeLeft <= 0 && GAME.power !== 'none') {
            GAME.specialCharge = 0; document.getElementById('special-bar').style.width = '0%'; document.getElementById('special-text').style.display = 'none'; cameraShake = 0.5;

            if (GAME.power === 'kamehameha') {
                const rayGeo = new THREE.CylinderGeometry(2, 2, 100, 16); rayGeo.translate(0, 50, 0);
                const rayMesh = new THREE.Mesh(rayGeo, new THREE.MeshBasicMaterial({color: 0x00ffff, transparent: true, opacity: 0.8})); rayMesh.rotation.x = -Math.PI/2; camera.add(rayMesh);
                visualEffects.push({mesh: rayMesh, life: 1.5, type: 'laser', parent: camera});
                
                let forward = new THREE.Vector3(0,0,-1).applyQuaternion(camera.quaternion).normalize();
                for(let j=enemies.length-1; j>=0; j--) {
                    let e = enemies[j]; let dirToE = new THREE.Vector3().subVectors(e.position, playerGroup.position);
                    let dist = dirToE.length(); dirToE.normalize();
                    if(dist < 100 && forward.dot(dirToE) > 0.9) applyDamageToEnemy(j, 1000, true); 
                }
            }
            else if (GAME.power === 'supernova') {
                const sphere = new THREE.Mesh(new THREE.SphereGeometry(1, 16, 16), new THREE.MeshBasicMaterial({color: 0xff3300, transparent: true, opacity: 0.7, wireframe: true}));
                sphere.position.copy(playerGroup.position); scene.add(sphere);
                visualEffects.push({mesh: sphere, life: 1.0, type: 'nova', maxSize: 30});
                
                for(let j=enemies.length-1; j>=0; j--) {
                    let e = enemies[j]; if(e.position.distanceTo(playerGroup.position) <= 30) applyDamageToEnemy(j, 800, true);
                }
            }
            else if (GAME.power === 'relativity') {
                GAME.timeFrozen = true; document.getElementById('special-overlay').classList.add('time-frozen-screen');
                setTimeout(() => { GAME.timeFrozen = false; document.getElementById('special-overlay').classList.remove('time-frozen-screen'); }, 5000);
            }
        }
    }

    let lastShootTime = 0;
    function shoot() {
        if (isReloading) return;
        if (currentAmmo <= 0) { startReload(); return; }

        const now = performance.now();
        const stats = WEAPONS_STATS[GAME.custom.weaponType];
        
        if(now - lastShootTime < stats.fireRate) return;
        lastShootTime = now;
        currentAmmo--; updateHUD();

        if(isSprinting) isSprinting = false; 
        recoilTarget = stats.recoil; cameraShake = stats.shake; 

        muzzleFlashLight.visible = true; muzzleFlashLight.color.setHex(0xffea00);
        setTimeout(() => { muzzleFlashLight.visible = false; }, 50);

        for(let i=0; i<stats.pellets; i++) {
            const bullet = new THREE.Mesh(SHARED.geo.bullet, SHARED.mat.bulletNormal);
            bullet.position.copy(playerGroup.position); bullet.position.y += (isAiming ? 0 : -0.15); 
            
            const dir = new THREE.Vector3(0, 0, -1).applyQuaternion(camera.quaternion);
            let spreadAmt = isAiming ? stats.adsSpread : stats.spread;
            dir.x += (Math.random() - 0.5) * spreadAmt; dir.y += (Math.random() - 0.5) * spreadAmt; dir.normalize();

            bullet.userData = { direction: dir, speed: 6.0, distance: 0, damage: stats.damage, headMult: stats.headMult, maxRange: stats.range };
            scene.add(bullet); bullets.push(bullet);
        }
        if (currentAmmo <= 0) startReload();
    }

    // --- IA E INIMIGOS ---
    function spawnEnemy() {
        if(!waveActive || GAME.state !== 'PLAYING') return;

        const enemyGroup = new THREE.Group();
        let eColor = GAME.map === 'forest' ? 0x3b5e2b : (GAME.map === 'neon' ? 0xff00ff : 0xdddddd);
        const mat = new THREE.MeshStandardMaterial({ color: eColor, roughness: 1 });
        
        const body = new THREE.Mesh(SHARED.geo.enemyBody, mat); body.position.y = 0.75; body.castShadow = true; enemyGroup.add(body);
        const head = new THREE.Mesh(SHARED.geo.enemyHead, mat); head.position.y = 1.9; head.castShadow = true; enemyGroup.add(head);

        const angle = Math.random() * Math.PI * 2;
        enemyGroup.position.set(playerGroup.position.x + Math.cos(angle) * (25 + Math.random()*15), -2, playerGroup.position.z + Math.sin(angle) * (25 + Math.random()*15));

        enemyGroup.userData = { hp: 200, speed: 0.05, state: 'rising', isBoss: false, bodyBox: new THREE.Box3(), headBox: new THREE.Box3(), colorBase: eColor, attackCooldown: 0 };
        scene.add(enemyGroup); enemies.push(enemyGroup);
    }

    function spawnBoss() {
        if(GAME.state !== 'PLAYING') return;
        document.getElementById('boss-ui').style.display = 'block'; cameraShake = 0.5; 

        const bossGroup = new THREE.Group();
        const mat = new THREE.MeshStandardMaterial({ color: 0xff0000, metalness: 0.5 });
        
        const body = new THREE.Mesh(SHARED.geo.bossBody, mat); body.position.y = 2; body.castShadow = true; bossGroup.add(body);
        const head = new THREE.Mesh(SHARED.geo.bossHead, mat); head.position.y = 5; head.castShadow = true; bossGroup.add(head);

        const angle = Math.random() * Math.PI * 2;
        bossGroup.position.set(playerGroup.position.x + Math.cos(angle) * 40, -5, playerGroup.position.z + Math.sin(angle) * 40);

        let hpBase = GAME.stage === 1 ? 2000 : 3000;
        bossGroup.userData = { 
            hp: hpBase, maxHp: hpBase, speed: 0.035, 
            state: 'rising', isBoss: true, attackTimer: 3.5,
            bodyBox: new THREE.Box3(), headBox: new THREE.Box3(), colorBase: 0xff0000, attackCooldown: 0
        };
        scene.add(bossGroup); enemies.push(bossGroup); updateBossHP();
    }

    function bossBazookaAttack(bossPos) {
        const projectile = new THREE.Mesh(SHARED.geo.bazooka, new THREE.MeshBasicMaterial({color: 0xff3300}));
        projectile.position.copy(bossPos); projectile.position.y += 4; 
        
        const targetPos = playerGroup.position.clone();
        const dir = new THREE.Vector3().subVectors(targetPos, projectile.position);
        dir.y += 10; dir.normalize();

        projectile.userData = { vel: dir.multiplyScalar(20), damageRadius: 6.0 };
        scene.add(projectile); bossProjectiles.push(projectile);
    }

    // --- FUNÇÕES AUXILIARES ---
    function disposeMesh(mesh) {
        if(mesh.geometry) mesh.geometry.dispose();
        if(mesh.material) {
            if(Array.isArray(mesh.material)) mesh.material.forEach(m => m.dispose());
            else mesh.material.dispose();
        }
    }
    
    function disposeEnemy(e) {
        e.children.forEach(c => disposeMesh(c)); scene.remove(e);
    }

    function spawnParticles(pos, colorHex, amount) {
        let realAmount = Math.max(1, Math.floor(amount / 2));
        const mat = new THREE.MeshBasicMaterial({color: colorHex});
        for(let i=0; i<realAmount; i++) {
            const p = new THREE.Mesh(SHARED.geo.particle, mat); p.position.copy(pos);
            p.userData = { vel: new THREE.Vector3((Math.random()-0.5)*20, Math.random()*20, (Math.random()-0.5)*20), life: 0.8 + Math.random() * 0.4 };
            scene.add(p); particles.push(p);
        }
    }

    function createExplosion(pos, radius) {
        const exp = new THREE.Mesh(new THREE.SphereGeometry(radius, 16, 16), new THREE.MeshBasicMaterial({color: 0xff5500, transparent: true, opacity: 0.8, wireframe: true}));
        exp.position.copy(pos); scene.add(exp);
        visualEffects.push({mesh: exp, life: 0.5, type: 'fade'});
        spawnParticles(pos, 0xff3300, 20); cameraShake = 0.5;
        if(playerGroup.position.distanceTo(pos) < radius) takeDamage();
    }

    function updateBossHP() {
        if(!GAME.bossActive && GAME.state === 'PLAYING') return;
        let totalHp = 0, totalMax = 0;
        for(let e of enemies) { if(e.userData.isBoss) { totalHp += e.userData.hp; totalMax += e.userData.maxHp; } }
        
        if(totalMax > 0) {
            const pct = Math.max(0, (totalHp / totalMax) * 100);
            document.getElementById('boss-hp-bar').style.width = pct + '%';
            if(GAME.stage === 2) document.getElementById('boss-name').innerText = "DUPLA AMEAÇA";
        }
    }

    function showHitMarker(text, color, points) {
        const marker = document.getElementById('hit-marker');
        marker.innerText = text; marker.style.color = color; marker.style.display = 'block';
        marker.classList.remove('hit-anim'); void marker.offsetWidth; marker.classList.add('hit-anim');
        GAME.score += points; document.getElementById('score-display').innerText = GAME.score;
    }

    function applyDamageToEnemy(index, amount, isSpecial) {
        let e = enemies[index]; e.userData.hp -= amount;
        if(e.userData.isBoss) updateBossHP();
        
        spawnParticles(e.position, e.userData.colorBase, 5);
        showHitMarker(isSpecial ? "ESPECIAL!" : "HIT", isSpecial ? "#00ffff" : "#ffffff", amount);

        e.children[0].material.emissive.setHex(0xffffff);
        setTimeout(() => { if(e.children[0]) e.children[0].material.emissive.setHex(0x000000); }, 100);

        if(e.userData.hp <= 0) {
            spawnParticles(e.position, e.userData.colorBase, 15);
            GAME.score += e.userData.isBoss ? 5000 : 500;
            addSpecialCharge(e.userData.isBoss ? 50 : 15); 
            
            if(!e.userData.isBoss) capangasKilled++;
            disposeEnemy(e); enemies.splice(index, 1);
        }
    }

    function takeDamage(attacker = null) {
        if(GAME.state !== 'PLAYING' || playerInvulnerable > 0) return;
        GAME.lives--; updateHUD(); cameraShake = 0.5;
        playerInvulnerable = 1.0; 

        if(attacker) attacker.userData.attackCooldown = 2.0; 
        
        const overlay = document.getElementById('damage-overlay'); overlay.style.opacity = 1; setTimeout(() => overlay.style.opacity = 0, 200);
        const pushDir = new THREE.Vector3(0,0,1).applyQuaternion(camera.quaternion);
        playerGroup.position.x += pushDir.x * 5; playerGroup.position.z += pushDir.z * 5;

        if(GAME.lives <= 0) {
            GAME.state = 'GAMEOVER'; document.exitPointerLock(); localStorage.removeItem('paralisia_save_v8');
            hideAllScreens(); document.getElementById('final-score').innerText = GAME.score; document.getElementById('screen-gameover').classList.remove('hidden');
        }
    }

    function updateHUD() {
        document.getElementById('lives-display').innerText = "❤️".repeat(GAME.lives) || "💀";
        document.getElementById('score-display').innerText = GAME.score;
        document.getElementById('level-display').innerText = GAME.stage;
        
        const ammoDisp = document.getElementById('ammo-display');
        if(!isReloading) {
            ammoDisp.classList.remove('reloading');
            ammoDisp.innerText = `${currentAmmo} / ${WEAPONS_STATS[GAME.custom.weaponType].magSize}`;
            ammoDisp.style.color = (currentAmmo <= 5) ? "#ff1744" : "#fff";
        }
    }

    // --- LOOP MESTRE (LOBBY + JOGO) ---
    function animate() {
        requestAnimationFrame(animate);

        const time = performance.now();
        const delta = Math.min((time - prevTime) / 1000, 0.05); 
        prevTime = time;

        if (GAME.state === 'LOBBY') {
            if(lobbyCharacter) lobbyCharacter.rotation.y += 0.5 * delta;
            if(lobbyRing) lobbyRing.rotation.z -= delta * 0.2;
            if(lobbyRing2) lobbyRing2.rotation.z += delta * 0.4;
            renderer.render(lobbyScene, lobbyCamera);
            return;
        }

        if(GAME.state !== 'PLAYING') return;

        // --- SISTEMA DE ONDAS ---
        if (waveActive && !bossSpawned) {
            spawnTimer -= delta;
            if (spawnTimer <= 0) {
                let alive = enemies.filter(e => !e.userData.isBoss).length;
                let maxAlive = GAME.stage === 1 ? 5 : 8; 
                
                if (alive < maxAlive && enemiesSpawned < capangasTarget) {
                    spawnEnemy();
                    enemiesSpawned++;
                    spawnTimer = Math.random() * 1.5 + 0.5; 
                }
                
                if (capangasKilled >= capangasTarget && alive === 0) {
                    bossSpawned = true;
                    if (GAME.stage === 1) {
                        showWaveText("O CHEFÃO CHEGOU!");
                        setTimeout(() => spawnBoss(), 2000);
                    } else if (GAME.stage === 2) {
                        showWaveText("A DUPLA AMEAÇA!");
                        setTimeout(() => { spawnBoss(); spawnBoss(); }, 2000);
                    }
                }
            }
        }

        // --- VITÓRIA DE FASE / JOGO ---
        if (bossSpawned && enemies.length === 0) {
            waveActive = false;
            if (GAME.stage === 1) {
                document.exitPointerLock(); document.getElementById('boss-ui').style.display = 'none';
                GAME.state = 'WIN'; hideAllScreens(); document.getElementById('screen-power-select').classList.remove('hidden');
                return;
            } else if (GAME.stage === 2) {
                document.exitPointerLock(); document.getElementById('boss-ui').style.display = 'none';
                GAME.state = 'WIN'; hideAllScreens(); document.getElementById('win-score').innerText = GAME.score; document.getElementById('screen-win').classList.remove('hidden');
                return;
            }
        }

        for(let obj of neonObjects) { obj.rotation.x += delta * 0.5; obj.rotation.y += delta; }

        if (isReloading) {
            reloadTimer -= delta;
            if (reloadTimer <= 0) { isReloading = false; currentAmmo = WEAPONS_STATS[GAME.custom.weaponType].magSize; updateHUD(); }
        }

        if (playerInvulnerable > 0) { playerInvulnerable -= delta; gunMesh.visible = Math.floor(time / 100) % 2 === 0; } else gunMesh.visible = true;

        velocity.x -= velocity.x * 10.0 * delta; velocity.z -= velocity.z * 10.0 * delta; velocity.y -= 40.0 * delta; 
        let forward = new THREE.Vector3(0, 0, -1).applyQuaternion(camera.quaternion); forward.y = 0; forward.normalize();
        let right = new THREE.Vector3(1, 0, 0).applyQuaternion(camera.quaternion); right.y = 0; right.normalize();

        let moveVec = new THREE.Vector3(0,0,0);
        if(moveForward) moveVec.add(forward); if(moveBackward) moveVec.sub(forward); if(moveRight) moveVec.add(right); if(moveLeft) moveVec.sub(right);        
        if(moveVec.lengthSq() > 0) moveVec.normalize(); 

        let currentSpeedMulti = isSprinting ? 140.0 : 80.0; if (isAiming) currentSpeedMulti = 40.0;
        velocity.x += moveVec.x * currentSpeedMulti * delta; velocity.z += moveVec.z * currentSpeedMulti * delta;
        if(jump) { velocity.y = 15; jump = false; isGrounded = false; }

        let nextX = playerGroup.position.x + velocity.x * delta; let nextZ = playerGroup.position.z + velocity.z * delta;

        let canMoveX = true; let canMoveZ = true;
        for(let obs of obstacles) {
            if(Math.hypot(nextX - obs.x, playerGroup.position.z - obs.z) < obs.radius) { canMoveX = false; velocity.x = 0; }
            if(Math.hypot(playerGroup.position.x - obs.x, nextZ - obs.z) < obs.radius) { canMoveZ = false; velocity.z = 0; }
        }

        if(canMoveX) playerGroup.position.x = nextX; if(canMoveZ) playerGroup.position.z = nextZ;
        playerGroup.position.y += velocity.y * delta;
        if(playerGroup.position.y < 2) { velocity.y = 0; playerGroup.position.y = 2; isGrounded = true; }
        
        const limit = 98;
        if(playerGroup.position.x < -limit) { playerGroup.position.x = -limit; velocity.x = 0; } if(playerGroup.position.x > limit) { playerGroup.position.x = limit; velocity.x = 0; }
        if(playerGroup.position.z < -limit) { playerGroup.position.z = -limit; velocity.z = 0; } if(playerGroup.position.z > limit) { playerGroup.position.z = limit; velocity.z = 0; }

        camera.fov += ((isSprinting ? 90 : 75) - camera.fov) * 0.1; camera.updateProjectionMatrix();

        if (cameraShake > 0) {
            camera.position.x = (Math.random() - 0.5) * cameraShake; camera.position.y = (Math.random() - 0.5) * cameraShake;
            cameraShake *= 0.8; if (cameraShake < 0.01) { cameraShake = 0; camera.position.set(0,0,0); }
        }

        let targetPos = isSprinting && !isAiming ? gunPosSprint : (isAiming ? gunPosAim : gunPosNormal);
        if(isReloading) targetPos = new THREE.Vector3(0.35, -0.6, -0.4); 
        
        currentGunPos.lerp(targetPos, 0.15); currentRecoil += (recoilTarget - currentRecoil) * 0.2; recoilTarget *= 0.6; 
        gunSwayX *= 0.8; gunSwayY *= 0.8;
        
        gunMesh.position.copy(currentGunPos); gunMesh.position.x += gunSwayX; gunMesh.position.y += gunSwayY; gunMesh.position.z += currentRecoil;
        if(!isReloading) { gunMesh.rotation.x = currentRecoil + gunSwayY * 2; gunMesh.rotation.y = -gunSwayX * 2; }
        if(isGrounded && moveVec.lengthSq() > 0 && !isAiming && !isReloading) {
            let bobSpeed = isSprinting ? 0.02 : 0.015;
            gunMesh.position.y += Math.sin(time * bobSpeed) * (isSprinting ? 0.02 : 0.01);
        }

        for(let i = bullets.length - 1; i >= 0; i--) {
            let b = bullets[i]; let ray = new THREE.Ray(b.position, b.userData.direction); let moveDist = b.userData.speed; 
            let hitObs = false;
            for(let obs of obstacles) {
                let obsSphere = new THREE.Sphere(new THREE.Vector3(obs.x, 2, obs.z), obs.radius);
                let hitPoint = new THREE.Vector3();
                if(ray.intersectSphere(obsSphere, hitPoint) && b.position.distanceTo(hitPoint) <= moveDist) { hitObs = true; break; }
            }
            if(hitObs) { spawnParticles(b.position, 0xffea00, 2); scene.remove(b); bullets.splice(i, 1); continue; }

            let hit = false;
            for(let j = enemies.length - 1; j >= 0; j--) {
                let e = enemies[j]; if(e.userData.state === 'rising') continue;
                e.userData.bodyBox.setFromObject(e.children[0]).expandByScalar(0.4); e.userData.headBox.setFromObject(e.children[1]).expandByScalar(0.4);

                let headTarget = new THREE.Vector3(), bodyTarget = new THREE.Vector3();
                let hitHead = ray.intersectBox(e.userData.headBox, headTarget) && b.position.distanceTo(headTarget) <= moveDist;
                let hitBody = !hitHead && ray.intersectBox(e.userData.bodyBox, bodyTarget) && b.position.distanceTo(bodyTarget) <= moveDist;

                if(hitHead || hitBody) {
                    let finalDamage = hitHead ? (b.userData.damage * b.userData.headMult) : b.userData.damage;
                    applyDamageToEnemy(j, finalDamage, false);
                    hit = true; scene.remove(b); bullets.splice(i, 1); break; 
                }
            }
            if(!hit) {
                b.position.addScaledVector(b.userData.direction, b.userData.speed); b.userData.distance += b.userData.speed;
                if(b.userData.distance > b.userData.maxRange) { scene.remove(b); bullets.splice(i, 1); }
            }
        }

        for(let i = bossProjectiles.length - 1; i >= 0; i--) {
            let p = bossProjectiles[i];
            p.userData.vel.y -= 30 * delta; 
            p.position.addScaledVector(p.userData.vel, delta);
            spawnParticles(p.position, 0xffaa00, 1); 
            if(p.position.y <= 0.5) {
                p.position.y = 0.5; createExplosion(p.position, p.userData.damageRadius);
                disposeMesh(p); scene.remove(p); bossProjectiles.splice(i, 1);
            }
        }

        for(let i=visualEffects.length-1; i>=0; i--) {
            let eff = visualEffects[i]; eff.life -= delta;
            if(eff.life <= 0) { 
                disposeMesh(eff.mesh); if(eff.parent) eff.parent.remove(eff.mesh); else scene.remove(eff.mesh); 
                visualEffects.splice(i,1); continue; 
            }
            if(eff.type === 'fade') eff.mesh.material.opacity = eff.life * 2;
            if(eff.type === 'nova') { let scale = 1.0 + (1.0 - eff.life) * eff.maxSize; eff.mesh.scale.set(scale, scale, scale); eff.mesh.material.opacity = eff.life; }
            if(eff.type === 'laser') eff.mesh.material.opacity = eff.life;
        }

        for(let i=particles.length-1; i>=0; i--) {
            let p = particles[i]; p.userData.life -= delta;
            if(p.userData.life <= 0) { disposeMesh(p); scene.remove(p); particles.splice(i,1); continue; }
            p.userData.vel.y -= 40 * delta; p.position.addScaledVector(p.userData.vel, delta);
            if(p.position.y < 0.1) { p.position.y = 0.1; p.userData.vel.y *= -0.5; p.userData.vel.x *= 0.8; p.userData.vel.z *= 0.8;} 
            p.scale.setScalar(p.userData.life);
        }

        if(!GAME.timeFrozen) {
            for(let i=0; i<enemies.length; i++) {
                let e = enemies[i];
                if(e.userData.state === 'rising') { e.position.y += delta * (e.userData.isBoss ? 2 : 5); if(e.position.y >= 0) { e.position.y = 0; e.userData.state = 'chasing'; } continue; }
                const dirToPlayer = new THREE.Vector3().subVectors(playerGroup.position, e.position);
                dirToPlayer.y = 0; const dist = dirToPlayer.length(); if(dist > 0) dirToPlayer.normalize();
                e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z);

                if (e.userData.attackCooldown > 0) {
                    e.userData.attackCooldown -= delta; e.children[0].rotation.z = 0; 
                } else {
                    if(e.userData.isBoss) {
                        e.userData.attackTimer -= delta;
                        if(e.userData.attackTimer <= 0) { bossBazookaAttack(e.position); e.userData.attackTimer = 3.5; }
                        if(dist > 15.0 || dist < 4.0) { if (dist > 4.0) e.position.addScaledVector(dirToPlayer, e.userData.speed); e.children[0].rotation.z = Math.sin(time * 0.01) * 0.1; }
                        if(dist < 4.0) takeDamage(e);
                    } else {
                        if(dist > 2.2) { e.position.addScaledVector(dirToPlayer, e.userData.speed); e.children[0].rotation.z = Math.sin(time * 0.01) * 0.1; } else { takeDamage(e); }
                    }
                }
            }
        }
        renderer.render(scene, camera);
    }
</script>
</body>
</html>