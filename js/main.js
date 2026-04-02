import * as THREE from 'three';
import { PointerLockControls } from 'three/addons/controls/PointerLockControls.js';
import { setupWorld, arenaSize } from './world.js';
import { Player } from './player.js';
import { keys, mouse, initControls, isLocked } from './controls.js';

const scene = new THREE.Scene();
scene.background = new THREE.Color(0x111111); // Fundo cinza super escuro
scene.fog = new THREE.Fog(0x111111, 20, 70); // Névoa afastada para não cegar

const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
document.body.appendChild(renderer.domElement);

setupWorld(scene);
const player = new Player(scene, camera);

const cameraControls = new PointerLockControls(camera, renderer.domElement);
scene.add(cameraControls.getObject());
initControls(camera, renderer.domElement);

// --- VARIÁVEIS DE JOGO ---
let score = 0;
const scoreDisplay = document.getElementById('score-display');
const finalScoreDisplay = document.getElementById('final-score');

// --- TIROS E COLISÕES ---
const bullets = [];
let shootCooldown = 0;

function handleShooting() {
    if (shootCooldown > 0) shootCooldown--;
    if (!isLocked || player.isDead) return;

    // Atirar
    if (mouse.click && shootCooldown <= 0) {
        const geometry = new THREE.SphereGeometry(0.2, 8, 8);
        const material = new THREE.MeshBasicMaterial({ color: 0xffea00 }); 
        const bulletMesh = new THREE.Mesh(geometry, material);

        bulletMesh.position.copy(camera.position);
        const direction = new THREE.Vector3();
        camera.getWorldDirection(direction);

        scene.add(bulletMesh);
        bullets.push({ mesh: bulletMesh, direction: direction, life: 100 }); 
        shootCooldown = 15;
    }

    // Atualizar balas e checar colisão
    for (let i = bullets.length - 1; i >= 0; i--) {
        let b = bullets[i];
        b.mesh.position.addScaledVector(b.direction, 2.0); // Velocidade do tiro
        b.life--;

        let hitEnemy = false;

        // Checa colisão com todos os inimigos vivos
        for (let j = enemies.length - 1; j >= 0; j--) {
            let en = enemies[j];
            
            // Calcula a distância 2D (apenas eixos X e Z) para acertar o "cilindro" do inimigo
            const dx = b.mesh.position.x - en.mesh.position.x;
            const dz = b.mesh.position.z - en.mesh.position.z;
            const distance2D = Math.sqrt(dx * dx + dz * dz);

            // O inimigo tem raio 1 e altura até 6. Se o tiro estiver dentro dessa área:
            if (distance2D < 1.5 && b.mesh.position.y >= 0 && b.mesh.position.y <= 6) {
                // ACERTOU!
                scene.remove(en.mesh); // Remove o inimigo da tela
                enemies.splice(j, 1);  // Remove da lista
                hitEnemy = true;
                
                // Aumenta a pontuação
                score += 10;
                scoreDisplay.innerText = "Pontos: " + score;
                finalScoreDisplay.innerText = "Pontuação Final: " + score;
                break; // A bala só acerta 1 inimigo por vez
            }
        }

        // Se a bala bateu no inimigo ou a vida dela acabou, ela some
        if (hitEnemy || b.life <= 0) {
            scene.remove(b.mesh);
            bullets.splice(i, 1);
        }
    }
}

// --- INIMIGOS ---
const enemies = [];
let spawnTimer = 0;

function spawnEnemy() {
    const group = new THREE.Group();

    // Corpo do Capanga
    const bodyMat = new THREE.MeshStandardMaterial({ color: 0x888888 }); 
    const body = new THREE.Mesh(new THREE.CylinderGeometry(1, 1, 6, 8), bodyMat);
    body.position.y = 3;
    group.add(body);

    // Olhos
    const eyeMat = new THREE.MeshBasicMaterial({ color: 0xff0000 });
    const eye1 = new THREE.Mesh(new THREE.SphereGeometry(0.3), eyeMat); eye1.position.set(0.4, 5, 0.9); group.add(eye1);
    const eye2 = new THREE.Mesh(new THREE.SphereGeometry(0.3), eyeMat); eye2.position.set(-0.4, 5, 0.9); group.add(eye2);

    const limit = (arenaSize / 2) - 2;
    const x = (Math.random() * limit * 2) - limit;
    const z = (Math.random() * limit * 2) - limit;

    group.position.set(x, -6, z); 
    scene.add(group);

    enemies.push({ mesh: group, state: 'emerging', speed: 0.08 }); // Velocidade de caminhada
}

function handleEnemies() {
    if (player.isDead) return; 

    spawnTimer++;
    // Nasce 1 inimigo a cada ~1.5 segundos (90 frames) para dar ação ao jogo!
    if (spawnTimer > 90) { 
        spawnEnemy();
        spawnTimer = 0;
    }

    for (let i = enemies.length - 1; i >= 0; i--) {
        let en = enemies[i];

        if (en.state === 'emerging') {
            en.mesh.position.y += 0.1;
            if (en.mesh.position.y >= 0) {
                en.mesh.position.y = 0;
                en.state = 'hunting'; 
            }
        } 
        else if (en.state === 'hunting') {
            const targetPos = player.mesh.position.clone();
            targetPos.y = en.mesh.position.y;
            en.mesh.lookAt(targetPos);

            const direction = new THREE.Vector3(0, 0, 1);
            direction.applyQuaternion(en.mesh.quaternion);
            en.mesh.position.addScaledVector(direction, en.speed);

            const distance = en.mesh.position.distanceTo(player.mesh.position);
            // Se o inimigo encostar no jogador (colisão de dano)
            if (distance < 2.5) { 
                player.takeDamage(); // Perde vida
                scene.remove(en.mesh); // Inimigo explode ao bater em você
                enemies.splice(i, 1);
            }
        }
    }
}

// --- LOOP PRINCIPAL ---
function animate() {
    requestAnimationFrame(animate);

    if (isLocked && !player.isDead) {
        player.update(keys);
        handleShooting();
    }
    
    handleEnemies();

    renderer.render(scene, camera);
}

window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

animate();