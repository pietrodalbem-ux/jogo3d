import * as THREE from 'three';

export class Player {
    constructor(scene, camera) {
        this.camera = camera;
        this.mesh = new THREE.Mesh(
            new THREE.BoxGeometry(2, 4, 2),
            new THREE.MeshStandardMaterial({ color: 0x00e5ff })
        );
        this.mesh.position.set(0, 2, 0);
        this.mesh.castShadow = true;
        scene.add(this.mesh);

        // Propriedades de Física
        this.speed = 0.3;
        this.velocityY = 0; // Velocidade vertical (subida/descida)
        this.gravity = 0.015;
        this.jumpForce = 0.4;
        this.canJump = false;
    }

    update(keys) {
        // Movimento Horizontal
        if (keys.w) this.mesh.position.z -= this.speed;
        if (keys.s) this.mesh.position.z += this.speed;
        if (keys.a) this.mesh.position.x -= this.speed;
        if (keys.d) this.mesh.position.x += this.speed;

        // Lógica de Pulo e Gravidade
        this.velocityY -= this.gravity; // Aplica gravidade constantemente
        this.mesh.position.y += this.velocityY;

        // Colisão com o chão (Y = 2 é o nível dos pés no chão)
        if (this.mesh.position.y <= 2) {
            this.mesh.position.y = 2;
            this.velocityY = 0;
            this.canJump = true;
        }

        if (keys.space && this.canJump) {
            this.velocityY = this.jumpForce;
            this.canJump = false;
        }

        // Câmera segue o jogador
        this.camera.position.set(this.mesh.position.x, this.mesh.position.y + 10, this.mesh.position.z + 20);
        this.camera.lookAt(this.mesh.position);
    }
}