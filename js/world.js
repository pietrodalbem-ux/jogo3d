import * as THREE from 'three';

export const arenaSize = 60; 

export function setupWorld(scene) {
    // 1. Iluminação (Mais clara para podermos ver tudo)
    const ambientLight = new THREE.AmbientLight(0x444455, 3.0); // Bem mais forte
    scene.add(ambientLight);

    const pointLight = new THREE.PointLight(0x8888ff, 4, 100);
    pointLight.position.set(0, 20, 0);
    scene.add(pointLight);

    // 2. Chão (Cinza escuro visível)
    const floorGeo = new THREE.PlaneGeometry(arenaSize, arenaSize);
    const floorMat = new THREE.MeshStandardMaterial({ color: 0x333333, roughness: 0.8 });
    const floor = new THREE.Mesh(floorGeo, floorMat);
    floor.rotation.x = -Math.PI / 2;
    scene.add(floor);

    // 3. Paredes (Limites da Arena)
    const wallGeo = new THREE.BoxGeometry(arenaSize, 20, 2);
    const wallMat = new THREE.MeshStandardMaterial({ color: 0x222222 }); // Um pouco mais escura que o chão

    const wallN = new THREE.Mesh(wallGeo, wallMat); wallN.position.set(0, 10, -arenaSize/2); scene.add(wallN);
    const wallS = new THREE.Mesh(wallGeo, wallMat); wallS.position.set(0, 10, arenaSize/2); scene.add(wallS);
    
    const wallE = new THREE.Mesh(wallGeo, wallMat); wallE.rotation.y = Math.PI / 2; wallE.position.set(arenaSize/2, 10, 0); scene.add(wallE);
    const wallW = new THREE.Mesh(wallGeo, wallMat); wallW.rotation.y = Math.PI / 2; wallW.position.set(-arenaSize/2, 10, 0); scene.add(wallW);
}