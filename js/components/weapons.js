import { SHARED } from '../utils/constants.js';

export function buildWeaponMesh(wType, customColors) {
    let w = new THREE.Group();
    let colorWep = customColors ? customColors.weaponColor : '#1e293b';
    const matBody = new THREE.MeshStandardMaterial({ color: colorWep, roughness: 0.3, metalness: 0.8 });
    const matDark = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.4, metalness: 0.9 });
    const matGlow = new THREE.MeshStandardMaterial({ color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 2 });

    if (wType === 'assault') {
        const body = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.16, 0.5), matBody); w.add(body);
        const barrel = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.4, 8), matDark); barrel.rotation.x = Math.PI / 2; barrel.position.set(0, 0.04, -0.4); w.add(barrel);
        const mag = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.2, 0.08), matDark); mag.position.set(0, -0.15, -0.1); mag.rotation.x = 0.2; w.add(mag);
        const scope = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.2, 8), matDark); scope.rotation.x = Math.PI / 2; scope.position.set(0, 0.12, -0.05); w.add(scope);
        const glow = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.02, 0.2), matGlow); glow.position.set(0, 0.05, 0.1); w.add(glow);
    } 
    else if (wType === 'shotgun') {
        const body = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.15, 0.4), matBody); w.add(body);
        const barrel1 = new THREE.Mesh(new THREE.CylinderGeometry(0.025, 0.025, 0.4, 8), matDark); barrel1.rotation.x = Math.PI / 2; barrel1.position.set(0.03, 0.04, -0.4); w.add(barrel1);
        const barrel2 = new THREE.Mesh(new THREE.CylinderGeometry(0.025, 0.025, 0.4, 8), matDark); barrel2.rotation.x = Math.PI / 2; barrel2.position.set(-0.03, 0.04, -0.4); w.add(barrel2);
        const pump = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.08, 0.2), matDark); pump.position.set(0, 0.0, -0.4); w.add(pump);
    }
    else if (wType === 'sniper') {
        const body = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.12, 0.6), matBody); w.add(body);
        const barrel = new THREE.Mesh(new THREE.CylinderGeometry(0.015, 0.015, 0.7, 8), matDark); barrel.rotation.x = Math.PI / 2; barrel.position.set(0, 0.04, -0.6); w.add(barrel);
        const scope = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.3, 8), matDark); scope.rotation.x = Math.PI / 2; scope.position.set(0, 0.12, -0.1); w.add(scope);
        const bipod = new THREE.Mesh(new THREE.CylinderGeometry(0.01, 0.01, 0.2, 4), matDark); bipod.position.set(0, -0.1, -0.7); w.add(bipod);
    }
    
    const hm = new THREE.MeshStandardMaterial({ color: customColors ? customColors.skinColor : '#e2e8f0', roughness: 0.9 }); 
    const hand = new THREE.Mesh(new THREE.BoxGeometry(0.15, 0.15, 0.15), hm); hand.position.set(0.08, -0.08, 0.1); w.add(hand);

    return w;
}
