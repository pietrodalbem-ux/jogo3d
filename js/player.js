import * as THREE from 'three';
import { arenaSize } from './world.js'; // Importa o tamanho da arena

export class Player {
    constructor(scene, camera) {
        this.camera = camera;
        this.height = 4;
        this.mesh = new THREE.Mesh(
            new THREE.CapsuleGeometry(1, this.height - 2),
            new THREE.MeshStandardMaterial({ visible: false }) 
        );
        this.mesh.position.set(0, this.height / 2, 0);
        scene.add(this.mesh);

        // Status do Jogador
        this.lives = 3;
        this.isDead = false;
        this.invincibleTimer = 0; // Tempo piscando para não tomar 2 danos seguidos

        // Física
        this.speed = 0.2;
        this.velocityY = 0;
        this.gravity = 0.015;
        this.jumpForce = 0.4;
        this.canJump = false;
    }

    takeDamage() {
        if (this.invincibleTimer > 0 || this.isDead) return; // Se está invencível, não toma dano

        this.lives--;
        this.invincibleTimer = 60; // Fica 1 segundo (60 frames) invulnerável
        
        // Atualiza UI
        let hearts = "";
        for(let i=0; i<this.lives; i++) hearts += "♥ ";
        document.getElementById('lives-display').innerText = "Vidas: " + hearts;

        // Se morrer, mostra a tela de fim de jogo
        if (this.lives <= 0) {
            this.isDead = true;
            document.getElementById('game-over').classList.remove('hidden');
            document.exitPointerLock(); // Solta o mouse
        }
    }

    update(keys) {
        if (this.isDead) return; // Não se move se estiver morto

        if (this.invincibleTimer > 0) this.invincibleTimer--;

        const forward = new THREE.Vector3();
        forward.setFromMatrixColumn(this.camera.matrix, 0);
        forward.crossVectors(this.camera.up, forward).normalize();

        const side = new THREE.Vector3();
        side.setFromMatrixColumn(this.camera.matrix, 0).normalize();

        // Salva a posição futura para checar colisão
        const nextPos = this.mesh.position.clone();

        if (keys.w) nextPos.addScaledVector(forward, this.speed);
        if (keys.s) nextPos.addScaledVector(forward, -this.speed);
        if (keys.a) nextPos.addScaledVector(side, -this.speed);
        if (keys.d) nextPos.addScaledVector(side, this.speed);

        // --- COLISÃO COM AS PAREDES ---
        // Calcula o limite (metade do tamanho da arena menos a grossura do jogador)
        const limit = (arenaSize / 2) - 1.5; 
        
        if (nextPos.x > limit) nextPos.x = limit;
        if (nextPos.x < -limit) nextPos.x = -limit;
        if (nextPos.z > limit) nextPos.z = limit;
        if (nextPos.z < -limit) nextPos.z = -limit;

        // Aplica a posição apenas se estiver dentro da arena
        this.mesh.position.x = nextPos.x;
        this.mesh.position.z = nextPos.z;

        // --- PULO ---
        this.velocityY -= this.gravity; 
        this.mesh.position.y += this.velocityY;

        if (this.mesh.position.y <= this.height / 2) {
            this.mesh.position.y = this.height / 2;
            this.velocityY = 0;
            this.canJump = true;
        }

        if (keys.space && this.canJump) {
            this.velocityY = this.jumpForce;
            this.canJump = false;
        }

        // Câmera nos olhos
        this.camera.position.copy(this.mesh.position);
        this.camera.position.y += (this.height / 2) - 0.5;
    }
}