// js/main.js
import * as THREE from 'three';
import { setupWorld } from './world.js';
import { Player } from './player.js';
import { keys } from './controls.js'; // Importa nosso detector de teclas

// 1. CONFIGURAÇÃO BÁSICA
const scene = new THREE.Scene();
scene.background = new THREE.Color(0x87CEEB); // Céu

const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);

const renderer = new THREE.WebGLRenderer({ antialias: true });
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.shadowMap.enabled = true;
document.body.appendChild(renderer.domElement);

// 2. CARREGAR MUNDO E JOGADOR
setupWorld(scene);
const player = new Player(scene, camera); // Cria o jogador passando a cena e a câmera

// 3. LOOP DE ANIMAÇÃO
function animate() {
    requestAnimationFrame(animate);

    // Atualiza a posição do jogador de acordo com as teclas pressionadas
    player.update(keys);

    renderer.render(scene, camera);
}

// 4. RESPONSIVIDADE DA TELA
window.addEventListener('resize', () => {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
});

// Inicia o jogo
animate();