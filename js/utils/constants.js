// Game Constants and Configuration
export const GAME_CONFIG = {
    INITIAL_HP: 300,
    MAX_HP: 300,
    WAVES: {
        STAGE_1: { target: 20, maxActive: 5, bossHp: 10000 },
        STAGE_2: { target: 30, maxActive: 8, bossHp: 15000 },
        STAGE_3: { target: 40, maxActive: 12, bossHp: 20000 }
    }
};

export const WEAPONS = {
    assault: { 
        rate: 110, dmg: 40, head: 2.5, spread: 0.02, adsSpread: 0.005, 
        pellets: 1, mag: 30, reload: 1.5, recoil: 0.05, shake: 0.02, range: 100 
    },
    shotgun: { 
        rate: 800, dmg: 25, head: 2.0, spread: 0.12, adsSpread: 0.08, 
        pellets: 10, mag: 8, reload: 2.5, recoil: 0.3, shake: 0.15, range: 30 
    },
    sniper:  { 
        rate: 1200, dmg: 150, head: 1.6667, spread: 0.0, adsSpread: 0.0, 
        pellets: 1, mag: 5, reload: 3.0, recoil: 0.5, shake: 0.1, range: 200 
    }
};

// Shared Geometries and Materials
export let SHARED = {
    geo: {},
    mat: {}
};

export function initSharedResources() {
    if (typeof THREE === 'undefined') {
        console.error("Three.js not found!");
        return;
    }
    
    SHARED.geo = { 
        bullet: new THREE.CylinderGeometry(0.02, 0.02, 0.8, 4), 
        part: new THREE.BoxGeometry(0.15, 0.15, 0.15), 
        bazooka: new THREE.IcosahedronGeometry(0.8, 1), 
        tornado: new THREE.ConeGeometry(1.5, 8, 5), 
        skull: new THREE.DodecahedronGeometry(0.45, 1),
        bone: new THREE.CylinderGeometry(0.08, 0.06, 0.7, 8),
        joint: new THREE.SphereGeometry(0.12, 8, 8),
        sword: new THREE.BoxGeometry(0.15, 4.0, 0.4) 
    };
    
    SHARED.mat = { 
        bNorm: new THREE.MeshStandardMaterial({ color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 4.0 }), 
        bSpec: new THREE.MeshStandardMaterial({ color: 0xff00ff, emissive: 0xff00ff, emissiveIntensity: 4.0 }), 
        boneFor: new THREE.MeshStandardMaterial({ color: 0x2f3e46, roughness: 0.8, metalness: 0.2 }), 
        boneDun: new THREE.MeshStandardMaterial({ color: 0x94a3b8, roughness: 0.6, metalness: 0.5 }), 
        boneNeo: new THREE.MeshStandardMaterial({ color: 0x0f172a, emissive: 0x1e1e3f, metalness: 0.8 }), 
        boss: new THREE.MeshStandardMaterial({ color: 0x111111, metalness: 0.9, roughness: 0.1, emissive: 0x330000, emissiveIntensity: 0.5 }), 
        sword: new THREE.MeshStandardMaterial({ color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 4.0 }), 
        torn: new THREE.MeshStandardMaterial({ color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 2.0, transparent: true, opacity: 0.6, wireframe: true }),
        eyeNorm: new THREE.MeshStandardMaterial({ color: 0x00ffcc, emissive: 0x00ffcc, emissiveIntensity: 8.0 }), 
        eyeBoss: new THREE.MeshStandardMaterial({ color: 0xff1744, emissive: 0xff1744, emissiveIntensity: 8.0 }), 
        crown: new THREE.MeshStandardMaterial({ color: 0xffea00, metalness: 1.0, roughness: 0.1, emissive: 0x332200 })
    };
}

