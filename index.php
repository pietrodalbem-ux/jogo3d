<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paralisia 3D - Arcade FPS</title>
    <!-- Importando fonte Pixelada estilo Fliperama -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <!-- Importando Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html {
            margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden;
            background-color: #000; font-family: 'Press Start 2P', cursive;
            user-select: none;
        }
        
        #game-container { position: relative; width: 100%; height: 100%; }
        #webgl-canvas { display: block; width: 100%; height: 100%; }

        /* UI Overlays */
        .screen {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            background: rgba(0,0,0,0.9); z-index: 10; color: white; text-align: center;
            transition: opacity 0.5s;
        }
        .hidden { display: none !important; }

        /* Botoes estilo Arcade */
        .btn {
            background: #ff1744; color: white; border: 4px solid #fff;
            padding: 15px 30px; font-family: 'Press Start 2P', cursive;
            font-size: 14px; cursor: pointer; margin: 10px;
            text-transform: uppercase; transition: transform 0.1s, background 0.2s;
        }
        .btn:hover { background: #ff5252; transform: scale(1.05); }
        .btn:active { transform: scale(0.95); }

        /* Mira (Crosshair) */
        #crosshair {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 20px; height: 20px; z-index: 5; pointer-events: none;
            display: none;
        }
        #crosshair::before, #crosshair::after {
            content: ''; position: absolute; background: #00ffcc;
        }
        #crosshair::before { top: 9px; left: 0; width: 20px; height: 2px; }
        #crosshair::after { top: 0; left: 9px; width: 2px; height: 20px; }

        /* HUD */
        #hud {
            position: absolute; bottom: 20px; left: 20px; z-index: 5;
            display: none; text-align: left; text-shadow: 2px 2px 0 #000;
        }
        .heart { color: #ff1744; font-size: 24px; margin-right: 5px; }
        #wave-info { position: absolute; top: 20px; left: 50%; transform: translateX(-50%); z-index: 5; display: none; color: #ffea00; text-shadow: 2px 2px 0 #000;}
        
        /* Efeito de Dano */
        #damage-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle, transparent 50%, rgba(255,0,0,0.8) 100%);
            opacity: 0; z-index: 4; pointer-events: none; transition: opacity 0.2s;
        }

        /* Forms Customização */
        .custom-box { border: 2px solid #555; padding: 20px; margin: 10px; background: #111; }
        select, input[type="color"] { background: #222; color: white; font-family: inherit; border: 2px solid #ff1744; padding: 5px; }
    </style>
</head>
<body>

<div id="game-container">
    <canvas id="webgl-canvas"></canvas>

    <!-- Overlay de Dano -->
    <div id="damage-overlay"></div>

    <!-- HUD do Jogo -->
    <div id="crosshair"></div>
    <div id="hud">
        <div id="lives-display">❤️❤️❤️</div>
        <div id="ammo-display" class="mt-4 text-white">∞ / ∞</div>
    </div>
    <div id="wave-info">ONDA 1</div>

    <!-- Tela de Splash -->
    <div id="screen-splash" class="screen" style="background: #000;">
        <h1 class="text-6xl mb-4 text-red-600 drop-shadow-[4px_4px_0_#fff]">PARALISIA 3D</h1>
        <h2 class="text-xl text-gray-400 mb-12">O Jogo Definitivo</h2>
        <p class="animate-pulse text-yellow-400 text-sm">Pressione QUALQUER TECLA ou CLIQUE para iniciar</p>
    </div>

    <!-- Tela de Customização -->
    <div id="screen-customize" class="screen hidden">
        <h2 class="text-3xl text-yellow-400 mb-8">Personalizar Equipamento</h2>
        
        <div class="flex flex-wrap justify-center max-w-3xl">
            <div class="custom-box w-full sm:w-1/2 md:w-1/3">
                <h3 class="text-sm mb-4">Arma Primária</h3>
                <select id="weapon-type" class="w-full text-xs mb-4">
                    <option value="assault">Fuzil de Assalto</option>
                    <option value="shotgun">Escopeta (Perto)</option>
                    <option value="sniper">Rifle (Longe)</option>
                </select>
                <div class="flex items-center justify-between text-xs">
                    <span>Cor da Arma:</span>
                    <input type="color" id="weapon-color" value="#333333">
                </div>
            </div>

            <div class="custom-box w-full sm:w-1/2 md:w-1/3">
                <h3 class="text-sm mb-4">Seu Personagem</h3>
                <div class="flex items-center justify-between text-xs mb-4">
                    <span>Cor da Pele/Luva:</span>
                    <input type="color" id="player-color" value="#ffcc99">
                </div>
            </div>
        </div>

        <button class="btn mt-8" onclick="goToMapSelect()">Confirmar</button>
    </div>

    <!-- Tela de Seleção de Mapa -->
    <div id="screen-map" class="screen hidden">
        <h2 class="text-3xl text-yellow-400 mb-8">Escolha a Arena</h2>
        
        <div class="flex gap-8">
            <div class="custom-box cursor-pointer hover:border-red-500 transition-colors" onclick="selectMap('dungeon')">
                <h3 class="mb-4">Catacumbas</h3>
                <p class="text-xs text-gray-400 line-clamp-3">O lar sombrio dos esqueletos perdidos.</p>
            </div>
            <div class="custom-box cursor-pointer hover:border-blue-500 transition-colors" onclick="selectMap('neon')">
                <h3 class="mb-4">Cyber Neon</h3>
                <p class="text-xs text-gray-400 line-clamp-3">Uma arena virtual corrompida.</p>
            </div>
        </div>
    </div>

    <!-- Tela de História / Intro -->
    <div id="screen-story" class="screen hidden bg-black">
        <div class="max-w-2xl text-left">
            <h2 id="story-title" class="text-2xl text-red-500 mb-6">...</h2>
            <p id="story-text" class="text-sm leading-8 text-gray-300 min-h-[150px]"></p>
        </div>
        <button id="btn-start-game" class="btn mt-12 hidden" onclick="startGame()">ENTRAR NA ARENA</button>
    </div>

    <!-- Tela de Game Over -->
    <div id="screen-gameover" class="screen hidden bg-red-900 bg-opacity-90">
        <h1 class="text-6xl text-black drop-shadow-[4px_4px_0_#fff] mb-8">VOCÊ MORREU</h1>
        <p class="mb-8">Os capangas dominaram a arena.</p>
        <button class="btn" onclick="location.reload()">Tentar Novamente</button>
    </div>
    
    <!-- Tela de Vitória -->
    <div id="screen-win" class="screen hidden bg-green-900 bg-opacity-90">
        <h1 class="text-5xl text-white drop-shadow-[4px_4px_0_#000] mb-8">CHEFÃO DERROTADO!</h1>
        <p class="mb-8">Você limpou a arena com sucesso.</p>
        <button class="btn" onclick="location.reload()">Jogar Novamente</button>
    </div>

</div>

<script>
    // --- ESTADOS GLOBAIS ---
    const GAME = {
        state: 'SPLASH', // SPLASH, CUSTOMIZE, MAP, STORY, PLAYING, GAMEOVER
        lives: 3,
        map: 'dungeon', // dungeon ou neon
        custom: { weaponType: 'assault', weaponColor: '#333333', playerColor: '#ffcc99' },
        bossActive: false,
        enemiesDefeated: 0,
        requiredToBoss: 10,
        wave: 1
    };

    // --- TRANSIÇÕES DE TELA UI ---
    function hideAllScreens() { document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden')); }
    
    document.addEventListener('click', () => {
        if(GAME.state === 'SPLASH') {
            GAME.state = 'CUSTOMIZE';
            hideAllScreens();
            document.getElementById('screen-customize').classList.remove('hidden');
        }
    });
    document.addEventListener('keydown', () => {
        if(GAME.state === 'SPLASH') {
            GAME.state = 'CUSTOMIZE';
            hideAllScreens();
            document.getElementById('screen-customize').classList.remove('hidden');
        }
    });

    function goToMapSelect() {
        GAME.custom.weaponType = document.getElementById('weapon-type').value;
        GAME.custom.weaponColor = document.getElementById('weapon-color').value;
        GAME.custom.playerColor = document.getElementById('player-color').value;
        
        GAME.state = 'MAP';
        hideAllScreens();
        document.getElementById('screen-map').classList.remove('hidden');
    }

    function selectMap(mapType) {
        GAME.map = mapType;
        GAME.state = 'STORY';
        hideAllScreens();
        document.getElementById('screen-story').classList.remove('hidden');
        
        const title = mapType === 'dungeon' ? "As Catacumbas Esquecidas" : "Setor Neon-7";
        const text = mapType === 'dungeon' 
            ? "Você adentrou as ruínas antigas.\n\nO Rei Caveira enviou sua legião de ossos para te impedir de alcançar a câmara principal.\n\nEles rastejam do solo. Sobreviva aos capangas para desafiar o mestre."
            : "Conexão estabelecida.\n\nVírus detectado no mainframe. Entidades corrompidas estão sendo materializadas no grid.\n\nLimpe o setor de avatares infectados para expor o Core.";
        
        document.getElementById('story-title').innerText = title;
        typeWriterEffect(text, document.getElementById('story-text'), () => {
            document.getElementById('btn-start-game').classList.remove('hidden');
        });
    }

    function typeWriterEffect(text, element, callback) {
        element.innerText = '';
        let i = 0;
        function type() {
            if (i < text.length) {
                element.innerHTML += text.charAt(i) === '\n' ? '<br>' : text.charAt(i);
                i++;
                setTimeout(type, 30); // Velocidade de digitação
            } else {
                if(callback) callback();
            }
        }
        type();
    }

    // --- ENGINE 3D (THREE.JS) ---
    let scene, camera, renderer;
    let playerGroup, gunMesh;
    let bullets = [], enemies = [];
    
    // Controles e Movimento
    let moveForward = false, moveBackward = false, moveLeft = false, moveRight = false;
    let velocity = new THREE.Vector3(), direction = new THREE.Vector3();
    let prevTime = performance.now();
    let isAiming = false; // ADS
    
    // Configurações da Arma (Recuo e ADS)
    const gunPosNormal = new THREE.Vector3(0.3, -0.3, -0.5);
    const gunPosAim = new THREE.Vector3(0, -0.2, -0.4);
    let currentGunPos = new THREE.Vector3().copy(gunPosNormal);
    let recoilTarget = 0, currentRecoil = 0;
    
    function startGame() {
        GAME.state = 'PLAYING';
        hideAllScreens();
        document.getElementById('crosshair').style.display = 'block';
        document.getElementById('hud').style.display = 'block';
        document.getElementById('wave-info').style.display = 'block';
        
        init3D();
        animate();
        
        // Solicita trava de ponteiro no canvas
        document.getElementById('webgl-canvas').requestPointerLock = document.getElementById('webgl-canvas').requestPointerLock || document.getElementById('webgl-canvas').mozRequestPointerLock;
        document.getElementById('webgl-canvas').requestPointerLock();
    }

    function init3D() {
        const canvas = document.getElementById('webgl-canvas');
        scene = new THREE.Scene();
        
        // Neblina e fundo baseado no mapa
        const bgColor = GAME.map === 'dungeon' ? 0x050505 : 0x000510;
        scene.background = new THREE.Color(bgColor);
        scene.fog = new THREE.Fog(bgColor, 10, 50);

        camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        
        renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.shadowMap.enabled = true;

        // --- ILUMINAÇÃO ---
        const ambientLight = new THREE.AmbientLight(0xffffff, GAME.map === 'dungeon' ? 0.2 : 0.4);
        scene.add(ambientLight);
        
        const dirLight = new THREE.DirectionalLight(GAME.map === 'dungeon' ? 0xffaa00 : 0x00ffcc, 0.8);
        dirLight.position.set(20, 50, 20);
        dirLight.castShadow = true;
        scene.add(dirLight);

        // --- MAPA ---
        createMap();

        // --- JOGADOR (Câmera + Arma) ---
        playerGroup = new THREE.Group();
        playerGroup.position.y = 2; // Altura dos olhos
        scene.add(playerGroup);
        playerGroup.add(camera);

        createWeapon();

        // --- EVENTOS DO JOGO ---
        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('keyup', onKeyUp);
        document.addEventListener('mousedown', onMouseDown);
        document.addEventListener('mouseup', onMouseUp);
        window.addEventListener('resize', onWindowResize);

        // Sistema de Pointer Lock
        document.addEventListener('pointerlockchange', lockChangeAlert, false);

        // Inicia Spawns
        setInterval(spawnEnemy, 3000); // Nasce um capanga a cada 3s
    }

    function createMap() {
        // Chão
        const floorGeo = new THREE.PlaneGeometry(100, 100);
        const floorColor = GAME.map === 'dungeon' ? 0x222222 : 0x0a0a0a;
        const floorMat = new THREE.MeshStandardMaterial({ color: floorColor, roughness: 0.8 });
        const floor = new THREE.Mesh(floorGeo, floorMat);
        floor.rotation.x = -Math.PI / 2;
        floor.receiveShadow = true;
        scene.add(floor);

        // Paredes (Arena fechada)
        const wallMat = new THREE.MeshStandardMaterial({ 
            color: GAME.map === 'dungeon' ? 0x332211 : 0x112233,
            wireframe: GAME.map === 'neon' // Mapa neon parece uma grade
        });
        const wallGeo = new THREE.BoxGeometry(100, 20, 2);
        
        const w1 = new THREE.Mesh(wallGeo, wallMat); w1.position.set(0, 10, -50);
        const w2 = new THREE.Mesh(wallGeo, wallMat); w2.position.set(0, 10, 50);
        const w3 = new THREE.Mesh(wallGeo, wallMat); w3.position.set(-50, 10, 0); w3.rotation.y = Math.PI/2;
        const w4 = new THREE.Mesh(wallGeo, wallMat); w4.position.set(50, 10, 0); w4.rotation.y = Math.PI/2;
        
        scene.add(w1, w2, w3, w4);

        // Alguns obstáculos
        for(let i=0; i<10; i++) {
            const boxGeo = new THREE.BoxGeometry(4, 4, 4);
            const boxMat = new THREE.MeshStandardMaterial({ color: GAME.map === 'dungeon' ? 0x444444 : 0x00ffff });
            const box = new THREE.Mesh(boxGeo, boxMat);
            box.position.set(Math.random() * 80 - 40, 2, Math.random() * 80 - 40);
            box.castShadow = true; box.receiveShadow = true;
            scene.add(box);
        }
    }

    function createWeapon() {
        gunMesh = new THREE.Group();
        
        // Corpo da arma
        const bodyGeo = new THREE.BoxGeometry(0.1, 0.15, 0.5);
        const bodyMat = new THREE.MeshStandardMaterial({ color: GAME.custom.weaponColor });
        const body = new THREE.Mesh(bodyGeo, bodyMat);
        body.castShadow = true;
        gunMesh.add(body);

        // Cano
        const barrelGeo = new THREE.CylinderGeometry(0.02, 0.02, 0.4);
        const barrelMat = new THREE.MeshStandardMaterial({ color: 0x111111 });
        const barrel = new THREE.Mesh(barrelGeo, barrelMat);
        barrel.rotation.x = Math.PI / 2;
        barrel.position.set(0, 0.05, -0.3);
        gunMesh.add(barrel);

        // Mão/Luva segurando
        const handGeo = new THREE.BoxGeometry(0.12, 0.12, 0.12);
        const handMat = new THREE.MeshStandardMaterial({ color: GAME.custom.playerColor });
        const hand = new THREE.Mesh(handGeo, handMat);
        hand.position.set(0.05, -0.05, 0.1);
        gunMesh.add(hand);

        // Se for sniper, adiciona luneta
        if(GAME.custom.weaponType === 'sniper') {
            const scopeGeo = new THREE.CylinderGeometry(0.03, 0.03, 0.2);
            const scope = new THREE.Mesh(scopeGeo, new THREE.MeshStandardMaterial({color:0x000000}));
            scope.rotation.x = Math.PI / 2;
            scope.position.set(0, 0.12, -0.1);
            gunMesh.add(scope);
        }

        camera.add(gunMesh); // Arma atrelada à câmera
        gunMesh.position.copy(gunPosNormal);
    }

    // --- CONTROLES DA CÂMERA (MOUSE LOOK) ---
    let euler = new THREE.Euler(0, 0, 0, 'YXZ');
    function onMouseMove(event) {
        if (document.pointerLockElement === document.getElementById('webgl-canvas')) {
            const movementX = event.movementX || event.mozMovementX || event.webkitMovementX || 0;
            const movementY = event.movementY || event.mozMovementY || event.webkitMovementY || 0;

            euler.setFromQuaternion(camera.quaternion);
            
            // Sensibilidade menor se estiver mirando (ADS)
            const sens = isAiming ? 0.001 : 0.002;
            
            euler.y -= movementX * sens;
            euler.x -= movementY * sens;
            euler.x = Math.max(-Math.PI/2, Math.min(Math.PI/2, euler.x)); // Trava visão cima/baixo

            camera.quaternion.setFromEuler(euler);
        }
    }

    function lockChangeAlert() {
        if (document.pointerLockElement === document.getElementById('webgl-canvas')) {
            document.addEventListener("mousemove", onMouseMove, false);
        } else {
            document.removeEventListener("mousemove", onMouseMove, false);
        }
    }

    // --- INPUTS ---
    function onKeyDown(event) {
        switch (event.code) {
            case 'KeyW': moveForward = true; break;
            case 'KeyA': moveLeft = true; break;
            case 'KeyS': moveBackward = true; break;
            case 'KeyD': moveRight = true; break;
        }
    }
    function onKeyUp(event) {
        switch (event.code) {
            case 'KeyW': moveForward = false; break;
            case 'KeyA': moveLeft = false; break;
            case 'KeyS': moveBackward = false; break;
            case 'KeyD': moveRight = false; break;
        }
    }
    function onMouseDown(event) {
        if(GAME.state !== 'PLAYING') return;
        if(document.pointerLockElement !== document.getElementById('webgl-canvas')) return;

        if (event.button === 0) { // Clique Esquerdo: Atirar
            shoot();
        } else if (event.button === 2) { // Clique Direito: Mirar (ADS)
            isAiming = true;
            document.getElementById('crosshair').style.display = 'none'; // Some mira da tela
        }
    }
    function onMouseUp(event) {
        if (event.button === 2) {
            isAiming = false;
            document.getElementById('crosshair').style.display = 'block';
        }
    }

    // Previne menu de contexto ao clicar com direito
    document.addEventListener('contextmenu', event => event.preventDefault());

    function onWindowResize() {
        if(!camera) return;
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }

    // --- LÓGICA DE TIRO E BALÍSTICA ---
    let lastShootTime = 0;
    function shoot() {
        const now = performance.now();
        // Fire rate dependendo da arma
        const fireRate = GAME.custom.weaponType === 'assault' ? 100 : (GAME.custom.weaponType === 'sniper' ? 1000 : 800);
        if(now - lastShootTime < fireRate) return;
        lastShootTime = now;

        // Recuo Visual
        recoilTarget = GAME.custom.weaponType === 'sniper' ? 0.2 : 0.05;

        // Cria o projétil (Laser/Bala)
        const bulletGeo = new THREE.SphereGeometry(0.1, 4, 4);
        const bulletColor = GAME.map === 'neon' ? 0x00ffcc : 0xffaa00;
        const bulletMat = new THREE.MeshBasicMaterial({ color: bulletColor });
        
        let numBullets = GAME.custom.weaponType === 'shotgun' ? 5 : 1;
        
        for(let i=0; i<numBullets; i++) {
            const bullet = new THREE.Mesh(bulletGeo, bulletMat);
            
            // Posição inicial: na arma
            bullet.position.copy(playerGroup.position);
            bullet.position.y += (isAiming ? 0 : -0.2); // Ajusta se não tiver mirando
            
            // Direção baseada na câmera
            const dir = new THREE.Vector3(0, 0, -1);
            dir.applyQuaternion(camera.quaternion);
            
            // Espalhamento da arma (spread)
            let spread = isAiming ? 0 : 0.02;
            if(GAME.custom.weaponType === 'shotgun') spread = 0.1;
            
            dir.x += (Math.random() - 0.5) * spread;
            dir.y += (Math.random() - 0.5) * spread;
            dir.normalize();

            bullet.userData = { direction: dir, speed: 2.0, distance: 0 };
            scene.add(bullet);
            bullets.push(bullet);
        }
    }

    // --- LÓGICA DOS INIMIGOS ---
    function spawnEnemy() {
        if(GAME.state !== 'PLAYING') return;
        if(GAME.bossActive) return; // Se chefe tá ativo, para de nascer capanga normal

        // Chefe nasce quando atinge a meta
        if(GAME.enemiesDefeated >= GAME.requiredToBoss && !GAME.bossActive) {
            spawnBoss();
            return;
        }

        // Esqueleto em blocos
        const enemyGroup = new THREE.Group();
        
        const isNeon = GAME.map === 'neon';
        const color = isNeon ? 0xff00ff : 0xdddddd;
        const mat = new THREE.MeshStandardMaterial({ color: color, roughness: 1 });
        
        // Corpo
        const bodyGeo = new THREE.BoxGeometry(1, 1.5, 0.5);
        const body = new THREE.Mesh(bodyGeo, mat);
        body.position.y = 0.75; body.castShadow = true;
        enemyGroup.add(body);
        
        // Cabeça (Crânio)
        const headGeo = new THREE.BoxGeometry(0.8, 0.8, 0.8);
        const head = new THREE.Mesh(headGeo, mat);
        head.position.y = 1.9; head.castShadow = true;
        
        // Olhos
        const eyeMat = new THREE.MeshBasicMaterial({color: isNeon ? 0x00ffff : 0xff0000});
        const eye1 = new THREE.Mesh(new THREE.BoxGeometry(0.2,0.2,0.1), eyeMat); eye1.position.set(0.2, 0, 0.4); head.add(eye1);
        const eye2 = new THREE.Mesh(new THREE.BoxGeometry(0.2,0.2,0.1), eyeMat); eye2.position.set(-0.2, 0, 0.4); head.add(eye2);
        
        enemyGroup.add(head);

        // Nascer do chão (posição inicial Y negativa)
        const angle = Math.random() * Math.PI * 2;
        const distance = 20 + Math.random() * 20;
        enemyGroup.position.set(
            playerGroup.position.x + Math.cos(angle) * distance,
            -2, // Debaixo da terra
            playerGroup.position.z + Math.sin(angle) * distance
        );

        enemyGroup.userData = { 
            hp: 3, speed: 0.05, 
            state: 'rising', // rising, chasing
            isBoss: false
        };

        scene.add(enemyGroup);
        enemies.push(enemyGroup);
    }

    function spawnBoss() {
        GAME.bossActive = true;
        document.getElementById('wave-info').innerText = "CHEFÃO!";
        document.getElementById('wave-info').style.color = "#ff1744";

        const bossGroup = new THREE.Group();
        const mat = new THREE.MeshStandardMaterial({ color: 0xff0000, metalness: 0.5 });
        
        // Corpo Gigante
        const body = new THREE.Mesh(new THREE.BoxGeometry(3, 4, 1.5), mat);
        body.position.y = 2; body.castShadow = true; bossGroup.add(body);
        
        // Cabeça Gigante com coroa
        const head = new THREE.Mesh(new THREE.BoxGeometry(2, 2, 2), mat);
        head.position.y = 5; bossGroup.add(head);

        const crown = new THREE.Mesh(new THREE.CylinderGeometry(1.2, 1, 0.5), new THREE.MeshStandardMaterial({color: 0xffea00}));
        crown.position.y = 1.2; head.add(crown);

        // Spawna longe
        bossGroup.position.set(playerGroup.position.x, -5, playerGroup.position.z - 40);
        
        bossGroup.userData = { hp: 30, speed: 0.03, state: 'rising', isBoss: true };
        scene.add(bossGroup);
        enemies.push(bossGroup);
    }

    function takeDamage() {
        GAME.lives--;
        updateHUD();
        
        // Efeito visual na tela
        const overlay = document.getElementById('damage-overlay');
        overlay.style.opacity = 1;
        setTimeout(() => overlay.style.opacity = 0, 300);

        // Repulsão (Knockback) para não tomar dano seguido muito rápido
        playerGroup.position.x += (Math.random() - 0.5) * 5;
        playerGroup.position.z += (Math.random() - 0.5) * 5;

        if(GAME.lives <= 0) {
            GAME.state = 'GAMEOVER';
            document.exitPointerLock();
            hideAllScreens();
            document.getElementById('screen-gameover').classList.remove('hidden');
        }
    }

    function updateHUD() {
        let hearts = "";
        for(let i=0; i<GAME.lives; i++) hearts += "❤️";
        document.getElementById('lives-display').innerText = hearts || "💀";
    }

    // --- GAME LOOP ---
    function animate() {
        if(GAME.state !== 'PLAYING') return;
        requestAnimationFrame(animate);

        const time = performance.now();
        const delta = (time - prevTime) / 1000;
        prevTime = time;

        // 1. FÍSICA DO JOGADOR
        velocity.x -= velocity.x * 10.0 * delta;
        velocity.z -= velocity.z * 10.0 * delta;

        direction.z = Number(moveForward) - Number(moveBackward);
        direction.x = Number(moveRight) - Number(moveLeft);
        direction.normalize(); // Garante que andar na diagonal não seja mais rápido

        const speedMulti = 40.0;
        if (moveForward || moveBackward) velocity.z -= direction.z * speedMulti * delta;
        if (moveLeft || moveRight) velocity.x -= direction.x * speedMulti * delta;

        // Pega direção atual que câmera está olhando (somente eixo Y)
        const eulerY = new THREE.Euler(0, camera.rotation.y, 0, 'YXZ');
        const vec = new THREE.Vector3(velocity.x, 0, velocity.z);
        vec.applyEuler(eulerY);

        playerGroup.position.x += vec.x * delta;
        playerGroup.position.z += vec.z * delta;

        // Limites da Arena (Colisão simples com paredes)
        if(playerGroup.position.x > 48) playerGroup.position.x = 48;
        if(playerGroup.position.x < -48) playerGroup.position.x = -48;
        if(playerGroup.position.z > 48) playerGroup.position.z = 48;
        if(playerGroup.position.z < -48) playerGroup.position.z = -48;

        // Head bobbing (balanço da câmera ao andar)
        if(moveForward || moveBackward || moveLeft || moveRight) {
            playerGroup.position.y = 2 + Math.sin(time * 0.01) * 0.1;
        } else {
            playerGroup.position.y = 2; // Volta ao normal
        }

        // 2. ANIMAÇÃO DA ARMA (ADS e Recuo)
        const targetPos = isAiming ? gunPosAim : gunPosNormal;
        currentGunPos.lerp(targetPos, 0.1); // Interpolação suave para posição
        
        // Aplica recuo
        currentRecoil += (recoilTarget - currentRecoil) * 0.2;
        recoilTarget *= 0.8; // Recuo volta a zero rápido
        
        gunMesh.position.copy(currentGunPos);
        gunMesh.position.z += currentRecoil; // Arma vai pra trás
        gunMesh.rotation.x = currentRecoil;  // Arma levanta

        // 3. ATUALIZA PROJÉTEIS
        for(let i = bullets.length - 1; i >= 0; i--) {
            let b = bullets[i];
            b.position.addScaledVector(b.userData.direction, b.userData.speed);
            b.userData.distance += b.userData.speed;

            // Remove bala se for muito longe
            if(b.userData.distance > 100) {
                scene.remove(b);
                bullets.splice(i, 1);
                continue;
            }

            // Colisão Bala -> Inimigo
            let hit = false;
            for(let j = enemies.length - 1; j >= 0; j--) {
                let e = enemies[j];
                // Checagem de colisão por distância (simples)
                const hitRadius = e.userData.isBoss ? 3 : 1.5;
                if(b.position.distanceTo(e.position) < hitRadius) {
                    e.userData.hp -= 1;
                    
                    // Piscar inimigo (Feedback de dano)
                    e.children[0].material.emissive.setHex(0xffffff);
                    setTimeout(() => { if(e.children[0]) e.children[0].material.emissive.setHex(0x000000); }, 100);

                    hit = true;
                    scene.remove(b);
                    bullets.splice(i, 1);

                    // Morte do inimigo
                    if(e.userData.hp <= 0) {
                        scene.remove(e);
                        enemies.splice(j, 1);
                        if(!e.userData.isBoss) {
                            GAME.enemiesDefeated++;
                        } else {
                            // VITÓRIA! Chefão morreu.
                            GAME.state = 'WIN';
                            document.exitPointerLock();
                            hideAllScreens();
                            document.getElementById('screen-win').classList.remove('hidden');
                        }
                    }
                    break; 
                }
            }
        }

        // 4. ATUALIZA INIMIGOS
        for(let i=0; i<enemies.length; i++) {
            let e = enemies[i];
            
            // Animação de sair do chão
            if(e.userData.state === 'rising') {
                e.position.y += delta * 2;
                if(e.position.y >= 0) {
                    e.position.y = 0;
                    e.userData.state = 'chasing';
                }
                continue; // Não persegue enquanto nasce
            }

            // Seguir o jogador
            const dirToPlayer = new THREE.Vector3().subVectors(playerGroup.position, e.position);
            dirToPlayer.y = 0; // Mantém no chão
            
            const dist = dirToPlayer.length();
            if(dist > 0) dirToPlayer.normalize();

            // Virar para o jogador
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z);

            // Mover
            if(dist > 1.5) { // Distância de ataque
                e.position.addScaledVector(dirToPlayer, e.userData.speed);
                // Animação de andar (wobble)
                e.children[0].rotation.z = Math.sin(time * 0.01) * 0.1;
            } else {
                // Ataque de Perto (Melee) - Atingiu o player
                takeDamage();
            }
        }

        renderer.render(scene, camera);
    }

</script>
</body>
</html>