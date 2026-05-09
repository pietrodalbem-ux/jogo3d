import { SHARED } from './constants.js';

export function disposeMesh(m) { 
    if(!m) return; 
    if(m.geometry) m.geometry.dispose(); 
    if(m.material) {
        if(Array.isArray(m.material)) m.material.forEach(x => x.dispose());
        else m.material.dispose();
    } 
}

export function disposeEnemy(e, scene) { 
    if(!e) return; 
    e.traverse(c => { if(c.isMesh) disposeMesh(c); }); 
    scene.remove(e); 
}

export function spawnParticles(pos, col, amt, scene, particles) { 
    let r = Math.max(1, Math.floor(amt/2)); 
    const m = new THREE.MeshBasicMaterial({color: col}); 
    for(let i=0; i<r; i++) {
        const p = new THREE.Mesh(SHARED.geo.part, m); 
        p.position.copy(pos); 
        p.userData = {
            vel: new THREE.Vector3((Math.random()-0.5)*20, Math.random()*20, (Math.random()-0.5)*20), 
            life: 0.8 + Math.random()*0.4
        }; 
        scene.add(p); 
        particles.push(p);
    } 
}

export function createExplosion(pos, rad, scene, visualEffects, particles, cameraShakeCallback, takeDamageCallback) { 
    const ex = new THREE.Mesh(new THREE.SphereGeometry(rad,16,16), new THREE.MeshStandardMaterial({color:0xff1744, emissive:0xff1744, emissiveIntensity:2, transparent:true, opacity:0.8, wireframe:true})); 
    ex.position.copy(pos); 
    scene.add(ex); 
    visualEffects.push({mesh:ex, life:0.5, type:'fade'}); 
    spawnParticles(pos, 0xff1744, 20, scene, particles); 
    if (cameraShakeCallback) cameraShakeCallback(0.5); 
    if (takeDamageCallback) takeDamageCallback(pos, rad);
}
