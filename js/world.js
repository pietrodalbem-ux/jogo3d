import * as THREE from 'three';

export function setupWorld(scene) {
    // Luzes
    const dirLight = new THREE.DirectionalLight(0xffffff, 1.5);
    dirLight.position.set(50, 100, 50);
    dirLight.castShadow = true;
    scene.add(dirLight);
    scene.add(new THREE.AmbientLight(0x404040, 1.0));

    // Chão
    const floor = new THREE.Mesh(
        new THREE.PlaneGeometry(500, 500),
        new THREE.MeshStandardMaterial({ color: 0x2e8b57 })
    );
    floor.rotation.x = -Math.PI / 2;
    floor.receiveShadow = true;
    scene.add(floor);

    // FUNÇÃO PARA CRIAR UMA ÁRVORE
    const createTree = (x, z) => {
        const group = new THREE.Group();

        // Tronco
        const trunk = new THREE.Mesh(
            new THREE.CylinderGeometry(0.5, 0.5, 4),
            new THREE.MeshStandardMaterial({ color: 0x8b4513 })
        );
        trunk.position.y = 2;
        trunk.castShadow = true;
        group.add(trunk);

        // Folhas (Copa)
        const leaves = new THREE.Mesh(
            new THREE.ConeGeometry(3, 6, 8),
            new THREE.MeshStandardMaterial({ color: 0x006400 })
        );
        leaves.position.y = 6;
        leaves.castShadow = true;
        group.add(leaves);

        group.position.set(x, 0, z);
        scene.add(group);
    };

    // Espalhar 50 árvores aleatoriamente
    for (let i = 0; i < 50; i++) {
        const x = Math.random() * 400 - 200; // Entre -200 e 200
        const z = Math.random() * 400 - 200;
        // Evita criar árvore no centro onde o jogador nasce
        if (Math.abs(x) > 10 || Math.abs(z) > 10) {
            createTree(x, z);
        }
    }
}