import { GAME, saveGameState, loadGameState } from './gameState.js';
import { GAME_CONFIG, WEAPONS, SHARED, initSharedResources } from '../utils/constants.js';
import { buildWeaponMesh } from '../components/weapons.js';
import { createMapEnvironment } from '../components/environment.js';
import { createSkeleton, getValidSpawnPosition } from '../components/enemies.js';
import { updateHUD, showWaveText, showHitMarker } from '../ui/hud.js';
import { disposeMesh, disposeEnemy, spawnParticles, createExplosion } from '../utils/helpers.js';
import { SOUNDS } from '../utils/sounds.js';

// Global variables (now scoped within this module)
let scene, camera, renderer, composer;
let lobbyScene, lobbyCamera, lobbyCharacter, lobbyRing, lobbyRing2;
let playerGroup, gunMesh, muzzleFlashLight;

let bullets = [], enemies = [], obstacles = [], particles = [], bossProjectiles = [], visualEffects = [], neonObjects = [];
let prevTime = performance.now();
let targetLobbyCharX = 0;

let currentAmmo = 0, isReloading = false, reloadTimer = 0, lastShootTime = 0;
let moveForward = false, moveBackward = false, moveLeft = false, moveRight = false, jump = false, isSprinting = false;
let velocity, isAiming = false, isGrounded = true, playerInvulnerable = 0;
let cameraShake = 0, currentRecoil = 0, recoilTarget = 0, gunSwayX = 0, gunSwayY = 0;

let gunPosNormal, gunPosAim, gunPosSprint, currentGunPos;


let waveActive = false, capangasTarget = 0, capangasKilled = 0, enemiesSpawned = 0, bossSpawned = false, isWaitingForBoss = false, spawnTimer = 0;

// Export functions to be used globally (mapped back to window for index.php compatibility)
export function init() {
    console.log("Iniciando Synthetic Dawn...");
    
    // Inicializar vetores e recursos
    velocity = new THREE.Vector3();
    gunPosNormal = new THREE.Vector3(0.35, -0.25, -0.5);
    gunPosAim = new THREE.Vector3(0, -0.15, -0.3);
    gunPosSprint = new THREE.Vector3(0.4, -0.4, -0.4);
    currentGunPos = new THREE.Vector3().copy(gunPosNormal);
    
    initSharedResources();
    
    renderer = new THREE.WebGLRenderer({ 
        canvas: document.getElementById('webgl-canvas'), 
        antialias: false, 
        powerPreference: "high-performance" 
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.shadowMap.enabled = true; 
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    
    const renderPass = new THREE.RenderPass(new THREE.Scene(), new THREE.PerspectiveCamera());
    const bloomPass = new THREE.UnrealBloomPass(new THREE.Vector2(window.innerWidth, window.innerHeight), 1.5, 0.4, 0.85);
    bloomPass.threshold = 0.25; bloomPass.strength = 0.6; bloomPass.radius = 0.5; 
    
    composer = new THREE.EffectComposer(renderer); 
    composer.addPass(renderPass); 
    composer.addPass(bloomPass);

    initLobby();
    animate();
    
    if (loadGameState()) {
        // Forçar estado inicial para evitar travar na tela de introdução se o save estava em 'PLAYING'
        GAME.state = 'INTRO'; 
        
        document.getElementById('color-skin').value = GAME.custom.skinColor;
        document.getElementById('color-clothes').value = GAME.custom.clothesColor;
        document.getElementById('color-weapon').value = GAME.custom.weaponColor;
        updateLobbyModel();
        document.getElementById('save-prompt').classList.remove('hidden'); 
    } else {
        GAME.state = 'INTRO';
    }
    
    console.log("Estado inicial definido como INTRO. Pronto para começar.");
    setupEventListeners();
}


function setupEventListeners() {
    window.addEventListener('resize', onWindowResize);
    document.addEventListener('keydown', onKeyDown);
    document.addEventListener('keyup', onKeyUp);
    document.addEventListener('mousedown', onMouseDown);
    document.addEventListener('mouseup', onMouseUp);
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('contextmenu', e => e.preventDefault());
    
    document.addEventListener('pointerlockchange', () => { 
        if (GAME.state === 'PLAYING') {
            const cv = document.getElementById('webgl-canvas');
            if (document.pointerLockElement === cv) {
                document.getElementById('screen-pause').classList.add('hidden'); 
            } else {
                document.getElementById('screen-pause').classList.remove('hidden'); 
                moveForward=false; moveBackward=false; moveLeft=false; moveRight=false; isSprinting=false;
            }
        }
    });
}

function initLobby() {
    lobbyScene = new THREE.Scene(); 
    lobbyScene.background = new THREE.Color(0x020408);
    lobbyCamera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 100); 
    lobbyCamera.position.set(0, 1.4, 4.5); 
    lobbyCamera.lookAt(0, 0.5, 0); 
    
    const dl = new THREE.DirectionalLight(0xffffff, 0.8); 
    dl.position.set(5, 10, 5); 
    lobbyScene.add(dl); 
    lobbyScene.add(new THREE.AmbientLight(0x223344, 0.5)); 
    
    const frontLight = new THREE.PointLight(0xffffff, 1.5, 10); 
    frontLight.position.set(0, 2, 3); 
    lobbyScene.add(frontLight);
    
    const wall = new THREE.Mesh(new THREE.PlaneGeometry(30, 20), new THREE.MeshStandardMaterial({color: 0x0f172a, roughness: 0.8})); 
    wall.position.set(0, 2, -4); 
    lobbyScene.add(wall);
    
    lobbyRing = new THREE.Mesh(new THREE.TorusGeometry(2.5, 0.02, 16, 64), new THREE.MeshStandardMaterial({color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 2.0})); 
    lobbyRing.position.set(0, 1.5, -2.5); 
    lobbyScene.add(lobbyRing);
    
    lobbyRing2 = new THREE.Mesh(new THREE.TorusGeometry(2.3, 0.01, 16, 64), new THREE.MeshStandardMaterial({color: 0x00b0ff, emissive: 0x00b0ff, emissiveIntensity: 1.0})); 
    lobbyRing2.position.set(0, 1.5, -2.5); 
    lobbyScene.add(lobbyRing2);
    
    composer.passes[0].scene = lobbyScene; 
    composer.passes[0].camera = lobbyCamera; 
    updateLobbyModel();
}

export function updateLobbyModel() {
    let currentRot = 0; 
    let currentX = targetLobbyCharX; 

    if (lobbyCharacter) { 
        currentRot = lobbyCharacter.rotation.y; 
        currentX = lobbyCharacter.position.x; 
        lobbyScene.remove(lobbyCharacter); 
    }
    lobbyCharacter = new THREE.Group();
    
    const skinMat = new THREE.MeshStandardMaterial({color: GAME.custom.skinColor, roughness: 0.5, metalness: 0.2}); 
    const clothesMat = new THREE.MeshStandardMaterial({color: GAME.custom.clothesColor, roughness: 0.9});
    
    const chest = new THREE.Mesh(new THREE.CylinderGeometry(0.3, 0.25, 0.6, 12), clothesMat); 
    chest.position.y = 1.2; 
    lobbyCharacter.add(chest); 
    
    const abdomen = new THREE.Mesh(new THREE.CylinderGeometry(0.22, 0.25, 0.4, 12), clothesMat); 
    abdomen.position.y = 0.7; 
    lobbyCharacter.add(abdomen); 
    
    const head = new THREE.Mesh(new THREE.SphereGeometry(0.25, 16, 16), skinMat); 
    head.position.y = 1.7; 
    lobbyCharacter.add(head);
    
    const visor = new THREE.Mesh(new THREE.BoxGeometry(0.35, 0.1, 0.25), new THREE.MeshStandardMaterial({color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 2.0}));
    visor.position.set(0, 1.75, 0.12); 
    lobbyCharacter.add(visor);

    const shoulderL = new THREE.Mesh(new THREE.SphereGeometry(0.12, 12, 12), clothesMat); 
    shoulderL.position.set(-0.35, 1.4, 0); 
    lobbyCharacter.add(shoulderL);
    
    const armL = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.06, 0.5, 8), clothesMat); 
    armL.position.set(-0.4, 1.1, 0); 
    lobbyCharacter.add(armL);
    
    const shoulderR = new THREE.Mesh(new THREE.SphereGeometry(0.12, 12, 12), clothesMat); 
    shoulderR.position.set(0.35, 1.4, 0); 
    lobbyCharacter.add(shoulderR);
    
    const armR = new THREE.Mesh(new THREE.CylinderGeometry(0.08, 0.06, 0.5, 8), clothesMat); 
    armR.position.set(0.4, 1.1, 0); 
    lobbyCharacter.add(armR);

    const legL = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.08, 0.6, 8), clothesMat); 
    legL.position.set(-0.15, 0.3, 0); 
    lobbyCharacter.add(legL);
    
    const legR = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.08, 0.6, 8), clothesMat); 
    legR.position.set(0.15, 0.3, 0); 
    lobbyCharacter.add(legR);

    const weapon = buildWeaponMesh(GAME.weapon, GAME.custom); 
    weapon.position.set(0.3, 1.0, 0.5); 
    weapon.rotation.y = -Math.PI / 8; 
    lobbyCharacter.add(weapon);
    
    lobbyCharacter.position.set(currentX, -0.5, 0); 
    lobbyCharacter.rotation.y = currentRot;
    lobbyScene.add(lobbyCharacter);
}

export function startGame() {
    hideAllScreens(); 
    document.getElementById('crosshair').style.display = 'block'; 
    document.getElementById('hud-bottom-left').classList.remove('hidden'); 
    document.getElementById('hud-top-right').classList.remove('hidden'); 
    document.getElementById('boss-ui').classList.add('hidden');

    
    currentAmmo = WEAPONS[GAME.weapon].mag; 
    isReloading = false; 
    updateHUDLocal();
    
    if (scene) {
        scene.traverse((child) => { 
            if(child.isMesh) { 
                child.geometry.dispose(); 
                if(Array.isArray(child.material)) child.material.forEach(m => m.dispose()); 
                else child.material.dispose(); 
            } 
        });
        while(scene.children.length > 0){ scene.remove(scene.children[0]); }
    }
    
    initGameScene(); 
    composer.passes[0].scene = scene; 
    composer.passes[0].camera = camera;
    
    enemies = []; bossProjectiles = []; bullets = []; particles = []; visualEffects = [];
    
    startWaveLogic(); 
    try { document.getElementById('webgl-canvas').requestPointerLock(); } catch(e) {}
    GAME.state = 'PLAYING'; 
    prevTime = performance.now(); 
    saveGameState(); 
}

function initGameScene() {
    scene = new THREE.Scene(); 
    let bgC = GAME.map === 'forest' ? 0x050a0a : (GAME.map === 'dungeon' ? 0x050508 : 0x020408); 
    scene.background = new THREE.Color(bgC); 
    let fogDist = GAME.map === 'dungeon' ? 45 : (GAME.map === 'forest' ? 80 : 60); 
    scene.fog = new THREE.Fog(bgC, 5, fogDist);
    
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    
    const hl = new THREE.HemisphereLight(0xffffff, 0x000000, 0.6); 
    hl.position.set(0, 50, 0); 
    scene.add(hl);
    
    const dlCol = GAME.map === 'forest' ? 0x88ccff : (GAME.map === 'dungeon' ? 0xff8844 : 0x00b0ff);
    const dl = new THREE.DirectionalLight(dlCol, 1.2); 
    dl.position.set(20, 40, 20); 
    dl.castShadow = true; 
    dl.shadow.mapSize.width = 1024; 
    dl.shadow.mapSize.height = 1024; 
    scene.add(dl);
    
    const al = new THREE.AmbientLight(0xffffff, 0.4); 
    scene.add(al);
    
    obstacles = []; neonObjects = [];
    createMapEnvironment(scene, GAME.map, obstacles, neonObjects);
    
    playerGroup = new THREE.Group(); 
    playerGroup.position.set(0, 2, 0); 
    scene.add(playerGroup); 
    playerGroup.add(camera);
    
    const vl = new THREE.PointLight(0xffffff, 0.4, 15); 
    camera.add(vl);
    
    gunMesh = buildWeaponMesh(GAME.weapon, GAME.custom); 
    muzzleFlashLight = new THREE.Mesh(new THREE.BoxGeometry(0.05, 0.05, 0.05), new THREE.MeshStandardMaterial({color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 8.0})); 
    muzzleFlashLight.position.set(0, 0.04, -0.6); 
    muzzleFlashLight.visible = false; 
    gunMesh.add(muzzleFlashLight); 
    camera.add(gunMesh); 
    gunMesh.position.copy(gunPosNormal);
}

function startWaveLogic() {
    waveActive = true; 
    enemiesSpawned = 0; 
    capangasKilled = 0; 
    bossSpawned = false; 
    isWaitingForBoss = false; 
    GAME.bossActive = false;
    
    if (GAME.stage === 1) capangasTarget = 20; 
    else if (GAME.stage === 2) capangasTarget = 30; 
    else if (GAME.stage === 3) capangasTarget = 40;
    
    document.getElementById('level-display').innerText = GAME.stage; 
    showWaveText("SETOR " + GAME.stage); 
    spawnTimer = 1.0;
}

function animate() {
    requestAnimationFrame(animate); 
    const t = performance.now();
    const dt = Math.min((t - prevTime) / 1000, 0.05); 
    prevTime = t;
    
    if (GAME.state === 'LOBBY' || GAME.state === 'INTRO') {
        if (lobbyCharacter) {
            lobbyCharacter.rotation.y += 0.5 * dt; 
            lobbyCharacter.position.x += (targetLobbyCharX - lobbyCharacter.position.x) * 5 * dt;
        }
        if (lobbyRing) lobbyRing.rotation.z -= dt * 0.2; 
        if (lobbyRing2) lobbyRing2.rotation.z += dt * 0.4; 
        composer.render(); 
        return;
    }
    
    if (GAME.state !== 'PLAYING') return;


    updateGameLogic(t, dt);
    composer.render();
}

function updateGameLogic(time, dt) {
    // 1. Wave System
    if (waveActive && !bossSpawned && !isWaitingForBoss) {
        spawnTimer -= dt; 
        if (spawnTimer <= 0) {
            let activeCount = enemies.filter(e => !e.userData.isB).length;
            let maxActive = GAME.stage === 1 ? 5 : (GAME.stage === 2 ? 8 : 12);
            if (activeCount < maxActive && enemiesSpawned < capangasTarget) {
                spawnEnemy(); 
                enemiesSpawned++; 
                spawnTimer = Math.random() * 1.0 + 0.5;
            }
            
            let targetKills = GAME.stage === 1 ? 17 : (GAME.stage === 2 ? 27 : 37);
            if (capangasKilled >= targetKills) { 
                isWaitingForBoss = true; 
                if (GAME.stage === 1) {
                    showWaveText("O CHEFÃO CHEGOU!");
                    setTimeout(() => { spawnBoss(); isWaitingForBoss = false; bossSpawned = true; }, 2000);
                } else if (GAME.stage === 2) {
                    showWaveText("A DUPLA AMEAÇA!");
                    setTimeout(() => { spawnBoss(); setTimeout(spawnBoss, 1000); isWaitingForBoss = false; bossSpawned = true; }, 2000);
                } else {
                    showWaveText("O TRIO MORTAL!");
                    setTimeout(() => { spawnBoss(); setTimeout(spawnBoss, 1000); setTimeout(spawnBoss, 2000); isWaitingForBoss = false; bossSpawned = true; }, 2000);
                } 
            }
        }
    }
    
    if (bossSpawned && enemies.length === 0 && !isWaitingForBoss) {
        waveActive = false; 
        document.getElementById('boss-ui').style.display = 'none'; 
        GAME.bossActive = false;
        GAME.state = 'WIN'; 
        hideAllScreens(); 
        document.exitPointerLock();
        if (GAME.stage === 1 || GAME.stage === 2) {
            document.getElementById('screen-power-select').classList.remove('hidden'); 
        } else {
            document.getElementById('win-score').innerText = GAME.dmgDealt;
            document.getElementById('screen-win').classList.remove('hidden');
        } 
        return;
    }

    // 2. Objects and Visuals
    for (let o of neonObjects) { o.rotation.x += dt * 0.5; o.rotation.y += dt; }
    if (isReloading) {
        reloadTimer -= dt;
        if (reloadTimer <= 0) {
            isReloading = false;
            currentAmmo = WEAPONS[GAME.weapon].mag;
            updateHUDLocal();
        }
    }
    if (playerInvulnerable > 0) {
        playerInvulnerable -= dt;
        gunMesh.visible = Math.floor(time / 100) % 2 === 0;
    } else {
        gunMesh.visible = true;
    }
    if (GAME.specialTimeLeft > 0) {
        GAME.specialTimeLeft -= dt;
        document.getElementById('special-bar').style.width = (GAME.specialTimeLeft / 5) * 100 + '%';
        if (GAME.specialTimeLeft <= 0) {
            document.getElementById('special-overlay').classList.remove('special-active-screen');
            document.getElementById('special-bar').style.width = '0%';
            updateHUDLocal();
        }
    }

    // 3. Player Movement
    updatePlayerMovement(time, dt);

    // 4. Combat Systems
    updateProjectiles(dt);
    updateEffects(dt);
    if (!GAME.timeFrozen) updateEnemies(dt);
}

function updatePlayerMovement(time, dt) {
    velocity.x -= velocity.x * 10 * dt; 
    velocity.z -= velocity.z * 10 * dt; 
    velocity.y -= 40 * dt;
    
    let fw = new THREE.Vector3(0, 0, -1).applyQuaternion(camera.quaternion); 
    fw.y = 0; fw.normalize(); 
    let rt = new THREE.Vector3(1, 0, 0).applyQuaternion(camera.quaternion); 
    rt.y = 0; rt.normalize();
    
    let mv = new THREE.Vector3(0, 0, 0); 
    if (moveForward) mv.add(fw); 
    if (moveBackward) mv.sub(fw); 
    if (moveRight) mv.add(rt); 
    if (moveLeft) mv.sub(rt); 
    if (mv.lengthSq() > 0) {
        mv.normalize();
        if (isGrounded && Math.floor(time / 250) % 2 === 0 && !isSprinting) SOUNDS.step();
        if (isGrounded && Math.floor(time / 150) % 2 === 0 && isSprinting) SOUNDS.step();
    }
    
    let sM = isSprinting ? 140 : 80; 
    if (isAiming) sM = 40; 
    velocity.x += mv.x * sM * dt; 
    velocity.z += mv.z * sM * dt; 
    
    if (jump) { velocity.y = 15; jump = false; isGrounded = false; }
    
    let nX = playerGroup.position.x + velocity.x * dt;
    let nZ = playerGroup.position.z + velocity.z * dt;
    let cX = true, cZ = true;
    
    for (let o of obstacles) {
        if (Math.hypot(nX - o.x, playerGroup.position.z - o.z) < o.radius) { cX = false; velocity.x = 0; }
        if (Math.hypot(playerGroup.position.x - o.x, nZ - o.z) < o.radius) { cZ = false; velocity.z = 0; }
    }
    
    if (cX) playerGroup.position.x = nX; 
    if (cZ) playerGroup.position.z = nZ; 
    
    playerGroup.position.y += velocity.y * dt; 
    if (playerGroup.position.y < 2) {
        velocity.y = 0;
        playerGroup.position.y = 2;
        isGrounded = true;
    }
    
    const lim = 98; 
    if (playerGroup.position.x < -lim) { playerGroup.position.x = -lim; velocity.x = 0; }
    if (playerGroup.position.x > lim) { playerGroup.position.x = lim; velocity.x = 0; }
    if (playerGroup.position.z < -lim) { playerGroup.position.z = -lim; velocity.z = 0; }
    if (playerGroup.position.z > lim) { playerGroup.position.z = lim; velocity.z = 0; }
    
    camera.fov += ((isSprinting ? 90 : 75) - camera.fov) * 0.1; 
    camera.updateProjectionMatrix();
    
    if (cameraShake > 0) {
        camera.position.x = (Math.random() - 0.5) * cameraShake;
        camera.position.y = (Math.random() - 0.5) * cameraShake;
        cameraShake *= 0.8;
        if (cameraShake < 0.01) { cameraShake = 0; camera.position.set(0, 0, 0); }
    }
    
    let targetP = isSprinting && !isAiming ? gunPosSprint : (isAiming ? gunPosAim : gunPosNormal); 
    if (isReloading) targetP = new THREE.Vector3(0.35, -0.6, -0.4);
    
    currentGunPos.lerp(targetP, 0.15); 
    currentRecoil += (recoilTarget - currentRecoil) * 0.2; 
    recoilTarget *= 0.6; 
    gunSwayX *= 0.8; 
    gunSwayY *= 0.8;
    
    gunMesh.position.copy(currentGunPos); 
    gunMesh.position.x += gunSwayX; 
    gunMesh.position.y += gunSwayY; 
    gunMesh.position.z += currentRecoil;
    
    if (!isReloading) {
        gunMesh.rotation.x = currentRecoil + gunSwayY * 2;
        gunMesh.rotation.y = -gunSwayX * 2;
    }
}

function updateProjectiles(dt) {
    for (let i = bullets.length - 1; i >= 0; i--) {
        let b = bullets[i], ray = new THREE.Ray(b.position, b.userData.direction), md = b.userData.speed, hitObstacle = false;
        for (let o of obstacles) {
            let oC = new THREE.Vector3(o.x, 2, o.z), hitPos = new THREE.Vector3(); 
            if (ray.intersectSphere(new THREE.Sphere(oC, o.radius), hitPos) && b.position.distanceTo(hitPos) <= md) {
                hitObstacle = true;
                spawnParticles(hitPos, 0xffffff, 8, scene, particles); 
                break;
            }
        }
        if (hitObstacle) { scene.remove(b); bullets.splice(i, 1); continue; }
        
        let hitEnemy = false;
        for (let j = enemies.length - 1; j >= 0; j--) {
            let e = enemies[j]; if (e.userData.state === 'rising') continue;
            e.userData.headBox.setFromObject(e.userData.head).expandByScalar(0.2); 
            e.userData.bodyBox.setFromObject(e.userData.torso).expandByScalar(0.4);
            let hT = new THREE.Vector3(), bT = new THREE.Vector3();
            let hitHead = ray.intersectBox(e.userData.headBox, hT) && b.position.distanceTo(hT) <= md;
            let hitBody = !hitHead && ray.intersectBox(e.userData.bodyBox, bT) && b.position.distanceTo(bT) <= md;
            
            if (hitHead || hitBody) {
                applyDamageToEnemy(e, hitHead ? b.userData.damage * b.userData.headMult : b.userData.damage, false);
                hitEnemy = true;
                scene.remove(b); bullets.splice(i, 1); 
                break;
            }
        }
        if (!hitEnemy) {
            b.position.addScaledVector(b.userData.direction, b.userData.speed);
            b.userData.distance += b.userData.speed;
            if (b.userData.distance > b.userData.maxRange) { scene.remove(b); bullets.splice(i, 1); }
        }
    }

    for (let i = bossProjectiles.length - 1; i >= 0; i--) {
        let p = bossProjectiles[i];
        if (p.userData.type === 'bazooka') {
            p.userData.vel.y -= 30 * dt;
            p.position.addScaledVector(p.userData.vel, dt);
            spawnParticles(p.position, 0xffaa00, 1, scene, particles);
            if (p.position.y <= 0.5) {
                p.position.y = 0.5;
                createExplosion(p.position, p.userData.damageRadius, scene, visualEffects, particles, s => cameraShake = s, (pos, rad) => {
                    if (playerGroup.position.distanceTo(pos) < rad) takeDamage(null, 50);
                });
                disposeMesh(p); scene.remove(p); bossProjectiles.splice(i, 1);
            }
        } else if (p.userData.type === 'tornado') {
            p.userData.life -= dt;
            p.rotation.y += 15 * dt;
            p.position.addScaledVector(p.userData.vel, dt);
            if (p.position.distanceTo(playerGroup.position) < p.userData.damageRadius) {
                takeDamage(null, 40);
                disposeMesh(p); scene.remove(p); bossProjectiles.splice(i, 1);
            } else if (p.userData.life <= 0) {
                disposeMesh(p); scene.remove(p); bossProjectiles.splice(i, 1);
            }
        }
    }
}

function updateEffects(dt) {
    for (let i = visualEffects.length - 1; i >= 0; i--) {
        let f = visualEffects[i]; f.life -= dt;
        if (f.life <= 0) {
            disposeMesh(f.mesh);
            if (f.parent) f.parent.remove(f.mesh); else scene.remove(f.mesh);
            visualEffects.splice(i, 1); continue;
        }
        if (f.type === 'fade') f.mesh.material.opacity = f.life * 2;
        if (f.type === 'nova') { let s = 1 + (1 - f.life) * f.maxSize; f.mesh.scale.set(s, s, s); f.mesh.material.opacity = f.life; }
        if (f.type === 'laser') f.mesh.material.opacity = f.life;
    }
    for (let i = particles.length - 1; i >= 0; i--) {
        let p = particles[i]; p.userData.life -= dt;
        if (p.userData.life <= 0) { disposeMesh(p); scene.remove(p); particles.splice(i, 1); continue; }
        p.userData.vel.y -= 40 * dt;
        p.position.addScaledVector(p.userData.vel, dt);
        if (p.position.y < 0.1) {
            p.position.y = 0.1;
            p.userData.vel.y *= -0.5; p.userData.vel.x *= 0.8; p.userData.vel.z *= 0.8;
        }
        p.scale.setScalar(p.userData.life);
    }
}

function updateEnemies(dt) {
    for (let i = enemies.length - 1; i >= 0; i--) {
        let e = enemies[i]; 
        if (e.userData.state === 'rising') {
            e.position.y += dt * (e.userData.isB ? 3 : 5);
            if (e.position.y >= 0) { e.position.y = 0; e.userData.state = 'chasing'; }
            continue;
        }
        const dp = new THREE.Vector3().subVectors(playerGroup.position, e.position); 
        dp.y = 0; const dist = dp.length(); 
        if (dist > 0) dp.normalize();
        
        if (e.userData.isB) {
            updateBossAI(e, dist, dt);
        } else {
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z);
            if (e.userData.attackCooldown > 0) {
                e.userData.attackCooldown -= dt;
                e.userData.armL.rotation.x = 0; e.userData.armR.rotation.x = 0;
                e.userData.legL.rotation.x = 0; e.userData.legR.rotation.x = 0;
            } else {
                if (dist > 2.2) {
                    moveEntitySmart(e, playerGroup.position, dt);
                    e.userData.walkCycle += dt * 15;
                    e.userData.armL.rotation.x = Math.sin(e.userData.walkCycle) * 0.8;
                    e.userData.armR.rotation.x = -Math.sin(e.userData.walkCycle) * 0.8;
                    e.userData.legL.rotation.x = -Math.sin(e.userData.walkCycle) * 0.8;
                    e.userData.legR.rotation.x = Math.sin(e.userData.walkCycle) * 0.8;
                } else takeDamage(e, 30);
            }
        }
    }
}

function updateBossAI(e, dist, dt) {
    if (e.userData.attackCooldown > 0) e.userData.attackCooldown -= dt;
    else {
        if (e.userData.bossState === 'ranged') {
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z);
            if (e.userData.wantsMelee || dist < 8) { 
                e.userData.bossState = 'grabbing_sword'; 
                e.userData.animTimer = 1; 
                e.userData.wantsMelee = false; 
            }
            else {
                e.userData.attackTimer -= dt;
                if (e.userData.attackTimer <= 0) { bossBazookaAttack(e.position); e.userData.attackTimer = 1.5; }
                moveEntitySmart(e, playerGroup.position, dt);
                e.userData.walkCycle += dt * 5;
                e.userData.armL.rotation.x = Math.sin(e.userData.walkCycle) * 0.5;
                e.userData.armR.rotation.x = -Math.sin(e.userData.walkCycle) * 0.5;
                e.userData.legL.rotation.x = -Math.sin(e.userData.walkCycle) * 0.5;
                e.userData.legR.rotation.x = Math.sin(e.userData.walkCycle) * 0.5;
            }
        } else if (e.userData.bossState === 'grabbing_sword') {
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z); 
            e.userData.animTimer -= dt; 
            e.userData.armR.rotation.x = Math.PI;
            if (e.userData.animTimer <= 0) {
                if (e.userData.sword && e.userData.torso) {
                    e.userData.torso.remove(e.userData.sword);
                    e.userData.armR.add(e.userData.sword);
                    e.userData.sword.position.set(0, -2.5, 0.5);
                    e.userData.sword.rotation.set(Math.PI / 2, 0, 0);
                }
                e.userData.bossState = 'chase_melee'; 
                e.userData.chaseTimer = 6;
            }
        } else if (e.userData.bossState === 'chase_melee') {
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z); 
            e.userData.speed = 0.25; 
            moveEntitySmart(e, playerGroup.position, dt);
            e.userData.walkCycle += dt * 15;
            e.userData.armL.rotation.x = Math.sin(e.userData.walkCycle) * 0.8;
            e.userData.legL.rotation.x = -Math.sin(e.userData.walkCycle) * 0.8;
            e.userData.legR.rotation.x = Math.sin(e.userData.walkCycle) * 0.8;
            e.userData.armR.rotation.x = Math.PI / 2 + Math.sin(e.userData.walkCycle) * 0.2;
            e.userData.chaseTimer -= dt; 
            if (dist < 4) { takeDamage(e, 80); e.userData.chaseTimer = 0; }
            if (e.userData.chaseTimer <= 0) {
                e.userData.bossState = 'spin_attack'; 
                e.userData.spinTimer = 4;
                e.userData.armR.rotation.x = Math.PI / 2; 
                e.userData.armL.rotation.x = -Math.PI / 2;
                e.userData.speed = 0.35; // Aumentado para ser ameaçador
            }
        } else if (e.userData.bossState === 'spin_attack') {
            e.userData.spinTimer -= dt; 
            e.rotation.y += 15 * dt; 
            // Durante o spin, ele persegue o jogador mais rápido
            moveEntitySmart(e, playerGroup.position, dt);
            
            e.userData.tornadoTimer = (e.userData.tornadoTimer || 0) - dt;
            if (e.userData.tornadoTimer <= 0) {
                const tn = new THREE.Mesh(SHARED.geo.tornado, SHARED.mat.torn);
                tn.position.copy(e.position); tn.position.y = 2;
                let an = Math.random() * Math.PI * 2;
                tn.userData = { type: 'tornado', vel: new THREE.Vector3(Math.cos(an), 0, Math.sin(an)).multiplyScalar(15), life: 5, damageRadius: 2.5 };
                scene.add(tn); bossProjectiles.push(tn);
                e.userData.tornadoTimer = 0.3;
            }
            if (dist < 6 && playerInvulnerable <= 0) takeDamage(e, 60);
            if (e.userData.spinTimer <= 0) { 
                e.userData.bossState = 'returning_sword'; 
                e.userData.animTimer = 1; 
                e.userData.speed = 0.1;
            }
        } else if (e.userData.bossState === 'returning_sword') {
            e.lookAt(playerGroup.position.x, e.position.y, playerGroup.position.z); 
            e.userData.animTimer -= dt; 
            e.userData.armR.rotation.x = Math.PI;
            if (e.userData.animTimer <= 0) {
                if (e.userData.sword && e.userData.torso) {
                    e.userData.armR.remove(e.userData.sword);
                    e.userData.torso.add(e.userData.sword);
                    e.userData.sword.position.set(0, 0, -1);
                    e.userData.sword.rotation.set(Math.PI / 8, 0, 0);
                }
                e.userData.bossState = 'ranged'; 
                e.userData.attackTimer = 1.5;
            }
        }
    }
}


// Combat Helpers
function shoot() {
    if (isReloading) return;
    if (currentAmmo <= 0 && GAME.specialTimeLeft <= 0) { startReload(); return; } 
    
    const n = performance.now();
    const s = WEAPONS[GAME.weapon]; 
    let coolRate = GAME.specialTimeLeft > 0 ? s.rate / 3 : s.rate; 
    if (n - lastShootTime < coolRate) return; lastShootTime = n; 
    
    if (GAME.specialTimeLeft <= 0) currentAmmo--; 
    updateHUDLocal(); 
    isSprinting = false; 
    recoilTarget = GAME.specialTimeLeft > 0 ? s.recoil * 0.5 : s.recoil; 
    cameraShake = s.shake;
    
    muzzleFlashLight.visible = true; 
    muzzleFlashLight.material.emissive.setHex(GAME.specialTimeLeft > 0 ? 0xff00ff : 0x00ffcc); 
    setTimeout(() => { muzzleFlashLight.visible = false; }, 50);
    
    let origin = new THREE.Vector3(); 
    camera.getWorldPosition(origin); 
    origin.y -= 0.1;
    
    for (let i = 0; i < s.pellets; i++) {
        const b = new THREE.Mesh(SHARED.geo.bullet, GAME.specialTimeLeft > 0 ? SHARED.mat.bSpec : SHARED.mat.bNorm); 
        b.position.copy(origin);
        const dir = new THREE.Vector3(0, 0, -1).applyQuaternion(camera.quaternion); 
        let spread = isAiming ? s.adsSpread : s.spread; 
        if (GAME.specialTimeLeft > 0) spread = 0; 
        dir.x += (Math.random() - 0.5) * spread; 
        dir.y += (Math.random() - 0.5) * spread; 
        dir.normalize();
        b.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), dir); 
        let dmg = GAME.specialTimeLeft > 0 ? s.dmg * 2 : s.dmg; 
        b.userData = { direction: dir, speed: 12, distance: 0, damage: dmg, headMult: s.head, maxRange: s.range }; 
        scene.add(b); 
        bullets.push(b);
    }
    SOUNDS.shoot(GAME.weapon);
    if (currentAmmo <= 0 && GAME.specialTimeLeft <= 0) startReload();
}

function startReload() { 
    if (isReloading || GAME.specialTimeLeft > 0 || currentAmmo === WEAPONS[GAME.weapon].mag) return; 
    isReloading = true; 
    reloadTimer = WEAPONS[GAME.weapon].reload; 
    SOUNDS.reload();
    document.getElementById('ammo-display').innerText = "RELOADING..."; 
    document.getElementById('ammo-display').classList.add('reloading'); 
    gunMesh.rotation.x = -Math.PI / 4; 
}

function takeDamage(attacker = null, amount = 30) {
    if (GAME.state !== 'PLAYING' || playerInvulnerable > 0) return; 
    GAME.hp -= amount; 
    if (GAME.hp < 0) GAME.hp = 0; 
    updateHUDLocal(); 
    cameraShake = 0.5; 
    playerInvulnerable = 1.0; 
    SOUNDS.hit();
    if (attacker && attacker.userData) attacker.userData.attackCooldown = 2.0;
    
    const overlay = document.getElementById('damage-overlay'); 
    overlay.style.opacity = 1; 
    setTimeout(() => overlay.style.opacity = 0, 200); 
    
    const pushDir = new THREE.Vector3(0, 0, 1).applyQuaternion(camera.quaternion); 
    playerGroup.position.x += pushDir.x * 5; 
    playerGroup.position.z += pushDir.z * 5;
    
    if (GAME.hp <= 0) {
        GAME.state = 'GAMEOVER'; 
        document.exitPointerLock(); 
        hideAllScreens(); 
        document.getElementById('final-score').innerText = GAME.dmgDealt; 
        document.getElementById('screen-gameover').classList.remove('hidden'); 
        GAME.hp = GAME.maxHp;
    } 
}

function applyDamageToEnemy(e, amount, isSpecial) {
    if (!e || e.userData.hp <= 0) return;
    e.userData.hp -= amount; 
    SOUNDS.hit();
    spawnParticles(e.position, e.userData.colorBase, 5, scene, particles); 
    showHitMarker(isSpecial ? "ESPECIAL!" : "HIT", isSpecial ? "#00ffcc" : "#fff", amount, GAME);
    
    e.visible = false; 
    setTimeout(() => { if (e) e.visible = true; }, 50);
    
    if (e.userData.isB && playerGroup.position.distanceTo(e.position) < 10) e.userData.wantsMelee = true; 
    if (e.userData.isB) updateBossHP();
    
    if (e.userData.hp <= 0) {
        if (e.userData.isB) SOUNDS.explosion();
        spawnParticles(e.position, e.userData.colorBase, 25, scene, particles);
        addSpecialCharge(e.userData.isB ? 50 : 15);
        if (!e.userData.isB) capangasKilled++;
        
        let idx = enemies.indexOf(e);
        if (idx !== -1) enemies.splice(idx, 1);
        disposeEnemy(e, scene); 
        updateBossHP();
    }
}


function updateBossHP() {
    if (!GAME.bossActive && GAME.state === 'PLAYING') return; 
    let total = 0, max = 0; 
    for (let e of enemies) if (e.userData.isB) { total += e.userData.hp; max += e.userData.maxHp; }
    
    const bossUI = document.getElementById('boss-ui');
    if (max > 0) {
        bossUI.style.display = 'block';
        document.getElementById('boss-hp-bar').style.width = Math.max(0, (total / max) * 100) + '%';
        const nameEl = document.getElementById('boss-name');
        if (GAME.stage === 2) nameEl.innerText = "DUPLA AMEAÇA";
        else if (GAME.stage === 3) nameEl.innerText = "O TRIO MORTAL";
    } else {
        bossUI.style.display = 'none';
        GAME.bossActive = false;
    }
}

function addSpecialCharge(amt) { 
    if (GAME.specialTimeLeft > 0 || GAME.power === 'none') return; 
    GAME.specialCharge += amt; 
    if (GAME.specialCharge >= 100) {
        GAME.specialCharge = 100;
        document.getElementById('special-text').style.display = 'block';
        document.getElementById('crosshair').style.borderColor = '#00ffcc';
    } 
    document.getElementById('special-bar').style.width = GAME.specialCharge + '%'; 
}

export function activateSpecial() {
    if (GAME.specialCharge >= 100 && GAME.specialTimeLeft <= 0 && GAME.power !== 'none') {
        GAME.specialCharge = 0; 
        document.getElementById('special-bar').style.width = '0%'; 
        document.getElementById('special-text').style.display = 'none'; 
        cameraShake = 0.5;
        
        if (GAME.power === 'overdrive') {
            GAME.specialTimeLeft = 5; 
            document.getElementById('special-overlay').classList.add('special-active-screen'); 
            currentAmmo = WEAPONS[GAME.weapon].mag; 
            isReloading = false; 
            updateHUDLocal();
        }
        else if (GAME.power === 'kamehameha') {
            const beam = new THREE.Mesh(new THREE.CylinderGeometry(1.5, 1.5, 100, 16), new THREE.MeshStandardMaterial({color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 8, transparent: true, opacity: 0.8})); 
            beam.translateY(50); beam.rotation.x = -Math.PI / 2; 
            camera.add(beam); 
            visualEffects.push({mesh: beam, life: 1.5, type: 'laser', parent: camera}); 
            
            let forward = new THREE.Vector3(0, 0, -1).applyQuaternion(camera.quaternion).normalize(); 
            for (let j = enemies.length - 1; j >= 0; j--) {
                let dir = new THREE.Vector3().subVectors(enemies[j].position, playerGroup.position); 
                let len = dir.length(); 
                if (len > 0) {
                    dir.normalize(); 
                    if (len < 100 && forward.dot(dir) > 0.9) applyDamageToEnemy(j, 1000, true);
                }
            }
        }
        else if (GAME.power === 'supernova') {
            const nova = new THREE.Mesh(new THREE.SphereGeometry(1, 32, 32), new THREE.MeshStandardMaterial({color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 5, transparent: true, opacity: 0.9})); 
            nova.position.copy(playerGroup.position); 
            scene.add(nova); 
            visualEffects.push({mesh: nova, life: 1, type: 'nova', maxSize: 40}); 
            for (let j = enemies.length - 1; j >= 0; j--) {
                if (enemies[j].position.distanceTo(playerGroup.position) <= 35) applyDamageToEnemy(j, 1000, true);
            }
        }
        else if (GAME.power === 'relativity') {
            GAME.timeFrozen = true; 
            document.getElementById('special-overlay').classList.add('time-frozen-screen'); 
            setTimeout(() => {
                GAME.timeFrozen = false; 
                document.getElementById('special-overlay').classList.remove('time-frozen-screen');
            }, 5000);
        }
    }
}

function bossBazookaAttack(bossPos) {
    const p = new THREE.Mesh(SHARED.geo.bazooka, new THREE.MeshStandardMaterial({color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 4})); 
    p.position.copy(bossPos); p.position.y += 6;
    const targetPos = playerGroup.position.clone();
    const dir = new THREE.Vector3().subVectors(targetPos, p.position); 
    dir.y += 2; dir.normalize(); 
    p.userData = { type: 'bazooka', vel: dir.multiplyScalar(40), damageRadius: 12 }; 
    scene.add(p); 
    bossProjectiles.push(p);
}

function spawnEnemy() {
    if (!waveActive || GAME.state !== 'PLAYING') return; 
    let col = GAME.map === 'forest' ? 0x3b5e2b : (GAME.map === 'neon' ? 0xff00ff : 0xdddddd); 
    const e = createSkeleton(false, GAME.map);
    const angle = Math.random() * Math.PI * 2; 
    let spawnPos = getValidSpawnPosition(playerGroup.position.x + Math.cos(angle) * (30 + Math.random() * 15), playerGroup.position.z + Math.sin(angle) * (30 + Math.random() * 15), 1, obstacles); 
    e.position.set(spawnPos.x, -2, spawnPos.z);
    e.userData = Object.assign(e.userData, { hp: 200, speed: 0.06, state: 'rising', isB: false, colorBase: col, attackCooldown: 0, walkCycle: Math.random() * 10 }); 
    scene.add(e); 
    enemies.push(e);
}

function spawnBoss() {
    if (GAME.state !== 'PLAYING') return; 
    document.getElementById('boss-ui').classList.remove('hidden'); 
    SOUNDS.bossSpawn();
    cameraShake = 0.5; 
    GAME.bossActive = true; 
    const b = createSkeleton(true, GAME.map);
    const angle = Math.random() * Math.PI * 2; 
    let spawnPos = getValidSpawnPosition(playerGroup.position.x + Math.cos(angle) * 40, playerGroup.position.z + Math.sin(angle) * 40, 2.5, obstacles); 
    b.position.set(spawnPos.x, -5, spawnPos.z);
    let hp = GAME.stage === 1 ? 10000 : (GAME.stage === 2 ? 15000 : 20000); 
    b.userData = Object.assign(b.userData, { hp: hp, maxHp: hp, speed: 0.1, state: 'rising', isB: true, attackTimer: 1.5, attackCooldown: 0, colorBase: 0xff0000, walkCycle: 0, bossState: 'ranged', wantsMelee: false, animTimer: 0, chaseTimer: 0, spinTimer: 0, tornadoTimer: 0 }); 
    scene.add(b); 
    enemies.push(b); 
    document.getElementById('boss-hp-bar').style.width = '100%';
    showWaveText("O CHEFÃO CHEGOU!");
}


function moveEntitySmart(e, targetPos, dt) {
    let r = e.userData.isB ? 2.5 : 1.0, speed = e.userData.speed;
    let dx = targetPos.x - e.position.x, dz = targetPos.z - e.position.z, dist = Math.hypot(dx, dz); 
    if (dist === 0 || isNaN(dist)) return;
    let mx = (dx / dist) * speed, mz = (dz / dist) * speed, ax = 0, az = 0;
    for (let o of obstacles) {
        let ox = e.position.x - o.x, oz = e.position.z - o.z, distOb = Math.hypot(ox, oz), safeDist = o.radius + r + 1.5; 
        if (distOb < safeDist) { 
            let factor = (safeDist - distOb) / safeDist; 
            ax += (ox / distOb) * factor; az += (oz / distOb) * factor; 
        }
    }
    let fx = mx + (ax * speed * 2), fz = mz + (az * speed * 2), fl = Math.hypot(fx, fz); 
    if (fl > speed) { fx = (fx / fl) * speed; fz = (fz / fl) * speed; }
    let nx = e.position.x + fx, nz = e.position.z + fz, lim = 95 - r; 
    if (nx < -lim) nx = -lim; if (nx > lim) nx = lim; if (nz < -lim) nz = -lim; if (nz > lim) nz = lim; 
    e.position.x = nx; e.position.z = nz;
}

// Event Handlers
function onWindowResize() {
    if (GAME.state === 'LOBBY' && lobbyCamera) {
        lobbyCamera.aspect = window.innerWidth / window.innerHeight;
        lobbyCamera.updateProjectionMatrix();
    } else if (GAME.state === 'PLAYING' && camera) {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
    }
    if (renderer) renderer.setSize(window.innerWidth, window.innerHeight);
    if (composer) composer.setSize(window.innerWidth, window.innerHeight);
}

function onMouseMove(e) {
    if (GAME.state !== 'PLAYING' || !camera) return;
    if (!document.getElementById('screen-pause').classList.contains('hidden')) return;
    
    let euler = new THREE.Euler(0, 0, 0, 'YXZ');
    euler.setFromQuaternion(camera.quaternion); 
    const sens = isAiming ? 0.001 : 0.002; 
    const mX = e.movementX || 0; 
    const mY = e.movementY || 0;
    
    euler.y -= mX * sens; 
    euler.x -= mY * sens; 
    euler.x = Math.max(-Math.PI / 2, Math.min(Math.PI / 2, euler.x)); 
    
    camera.quaternion.setFromEuler(euler); 
    gunSwayX = -mX * 0.001; 
    gunSwayY = mY * 0.001;
}

function onKeyDown(e) {
    if (GAME.state === 'INTRO' && (e.code === 'Space' || e.key === ' ')) { 
        window.showLobby();
        return;
    }
    if (GAME.state !== 'PLAYING') return;
    if (!document.getElementById('screen-pause').classList.contains('hidden')) return;
    
    switch (e.code) {
        case 'KeyW': moveForward = true; break;
        case 'KeyA': moveLeft = true; break;
        case 'KeyS': moveBackward = true; break;
        case 'KeyD': moveRight = true; break;
        case 'Space': if (isGrounded) jump = true; break;
        case 'ShiftLeft': case 'ShiftRight': isSprinting = true; break;
        case 'KeyR': startReload(); break;
        case 'KeyQ': activateSpecial(); break;
    }
}

function onKeyUp(e) {
    if (GAME.state !== 'PLAYING') return;
    switch (e.code) {
        case 'KeyW': moveForward = false; break;
        case 'KeyA': moveLeft = false; break;
        case 'KeyS': moveBackward = false; break;
        case 'KeyD': moveRight = false; break;
        case 'ShiftLeft': case 'ShiftRight': isSprinting = false; break;
    }
}

function onMouseDown(e) {
    if (GAME.state !== 'PLAYING') return;
    if (!document.getElementById('screen-pause').classList.contains('hidden')) return;
    
    const canvas = document.getElementById('webgl-canvas');
    if (document.pointerLockElement !== canvas) {
        try { canvas.requestPointerLock(); } catch (err) {}
    }
    
    if (e.button === 0) shoot();
    else if (e.button === 2) {
        isAiming = true; isSprinting = false; 
        const ch = document.getElementById('crosshair');
        ch.style.transform = 'translate(-50%,-50%) scale(0.5)';
        ch.style.opacity = '0.5';
    }
}

function onMouseUp(e) {
    if (GAME.state !== 'PLAYING') return;
    if (e.button === 2) {
        isAiming = false;
        const ch = document.getElementById('crosshair');
        ch.style.transform = 'translate(-50%,-50%) scale(1)';
        ch.style.opacity = '1';
    }
}

// UI Wrapper for consistency
function updateHUDLocal() {
    updateHUD(GAME, currentAmmo, isReloading);
}

function hideAllScreens() {
    document.querySelectorAll('.screen').forEach(el => el.classList.add('hidden'));
}

export function openCustomizeModal() {
    document.getElementById('screen-lobby').classList.add('hidden');
    document.getElementById('modal-customize').classList.remove('hidden');
    targetLobbyCharX = 1.2; 
}

export function closeCustomizeModal() {
    document.getElementById('modal-customize').classList.add('hidden');
    document.getElementById('screen-lobby').classList.remove('hidden');
    targetLobbyCharX = 0; 
}
