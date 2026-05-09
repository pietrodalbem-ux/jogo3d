import { SHARED } from '../utils/constants.js';

const TEX_CACHE = {};

export function getProceduralTexture(type, GAME_MAP) {
    if (TEX_CACHE[type]) return TEX_CACHE[type];
    const c = document.createElement('canvas'); c.width = 512; c.height = 512; const ctx = c.getContext('2d');
    
    if (type === 'dirt') { 
        ctx.fillStyle = '#0a0f12'; ctx.fillRect(0, 0, 512, 512); 
        for (let i = 0; i < 3000; i++) { ctx.fillStyle = Math.random() > 0.5 ? 'rgba(0,0,0,0.5)' : 'rgba(255,255,255,0.02)'; ctx.fillRect(Math.random() * 512, Math.random() * 512, 3, 3); } 
    } 
    else if (type === 'dungeon_tiles') {
        ctx.fillStyle = '#0f172a'; ctx.fillRect(0, 0, 512, 512); ctx.strokeStyle = '#020408'; ctx.lineWidth = 8;
        for(let i=0; i<=512; i+=128) { ctx.beginPath(); ctx.moveTo(i,0); ctx.lineTo(i,512); ctx.stroke(); ctx.beginPath(); ctx.moveTo(0,i); ctx.lineTo(512,i); ctx.stroke(); }
    } 
    else { 
        ctx.fillStyle = '#020408'; ctx.fillRect(0, 0, 512, 512); ctx.strokeStyle = '#00ffcc'; ctx.lineWidth = 2; 
        for (let i = 0; i <= 512; i += 64) { ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, 512); ctx.stroke(); ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(512, i); ctx.stroke(); } 
        ctx.fillStyle = '#00b0ff'; for (let i = 0; i <= 512; i += 64) { for (let j = 0; j <= 512; j += 64) { ctx.beginPath(); ctx.arc(i, j, 4, 0, Math.PI * 2); ctx.fill(); } } 
    }
    
    const tex = new THREE.CanvasTexture(c); tex.wrapS = THREE.RepeatWrapping; tex.wrapT = THREE.RepeatWrapping; tex.repeat.set(40, 40); 
    TEX_CACHE[type] = tex; 
    return tex;
}

export function createMapEnvironment(scene, GAME_MAP, obstacles, neonObjects) {
    const floorGeo = new THREE.PlaneGeometry(200, 200); 
    let texType = GAME_MAP === 'neon' ? 'neon_grid' : (GAME_MAP === 'dungeon' ? 'dungeon_tiles' : 'dirt');
    let fCol = GAME_MAP === 'forest' ? 0x1a2622 : (GAME_MAP === 'dungeon' ? 0x333344 : 0xffffff);
    const floorMat = new THREE.MeshStandardMaterial({color: fCol, roughness: 0.9, map: getProceduralTexture(texType, GAME_MAP)}); 
    if (GAME_MAP === 'neon') { floorMat.emissiveMap = getProceduralTexture('neon_grid', GAME_MAP); floorMat.emissive = new THREE.Color(0xffffff); floorMat.emissiveIntensity = 0.5; }
    const floor = new THREE.Mesh(floorGeo, floorMat); floor.rotation.x = -Math.PI / 2; floor.receiveShadow = true; scene.add(floor);

    const wm = new THREE.MeshStandardMaterial({color: GAME_MAP === 'forest' ? 0x050a0a : (GAME_MAP === 'dungeon' ? 0x050508 : 0x020408)}); 
    const wg = new THREE.BoxGeometry(200, 40, 2);
    const walls = [new THREE.Mesh(wg, wm), new THREE.Mesh(wg, wm), new THREE.Mesh(wg, wm), new THREE.Mesh(wg, wm)]; 
    walls[0].position.set(0, 20, -100); walls[1].position.set(0, 20, 100); walls[2].position.set(-100, 20, 0); walls[2].rotation.y = Math.PI / 2; walls[3].position.set(100, 20, 0); walls[3].rotation.y = Math.PI / 2; scene.add(...walls);

    let attempts = 0, spawned = 0;
    while(spawned < 60 && attempts < 500) {
        attempts++;
        const px = (Math.random() - 0.5) * 180; const pz = (Math.random() - 0.5) * 180;
        if(Math.abs(px) < 15 && Math.abs(pz) < 15) continue;
        let overlap = false;
        for(let o of obstacles) { if(Math.hypot(px - o.x, pz - o.z) < o.radius + 3.0) { overlap = true; break; } }
        if(overlap) continue;
        
        spawned++; let rad = 2.0;
        if(GAME_MAP==='forest') {
            if(Math.random()>0.5){
                const tr=new THREE.Group(); const tk=new THREE.Mesh(new THREE.CylinderGeometry(0.4, 0.6, 3, 5), new THREE.MeshStandardMaterial({color:0x221a15, flatShading:true})); tk.position.y=1.5; tk.castShadow=true; tr.add(tk);
                const c=[0x1a3322, 0x224a2c, 0x2b6338]; for(let l=0;l<3;l++){const lf=new THREE.Mesh(new THREE.ConeGeometry(2.5-l*0.5, 3, 5),new THREE.MeshStandardMaterial({color:c[l], flatShading:true})); lf.position.y=2.5+l*1.5; lf.castShadow=true; lf.rotation.y=Math.random(); tr.add(lf);}
                tr.position.set(px,0,pz); const scl=0.8+Math.random()*0.6; tr.scale.set(scl,scl,scl); scene.add(tr); rad=2.0*scl; obstacles.push({x:px,z:pz,radius:rad,type:'forest'});
            } else {
                const bs=new THREE.Mesh(new THREE.DodecahedronGeometry(1.2, 0),new THREE.MeshStandardMaterial({color:0x475569, flatShading:true})); bs.position.set(px,0.5,pz); bs.rotation.set(Math.random(),Math.random(),Math.random()); const scl=0.5+Math.random()*1.5; bs.scale.set(scl,scl*0.6,scl); bs.castShadow=true; scene.add(bs); rad=1.2*scl; obstacles.push({x:px,z:pz,radius:rad,type:'forest'});
            }
        } else if(GAME_MAP==='dungeon') {
            const pg=new THREE.Group(); const pm=new THREE.MeshStandardMaterial({color:0x1e293b, flatShading:true});
            const bs=new THREE.Mesh(new THREE.BoxGeometry(3, 1.5, 3),pm); bs.position.y=0.75; pg.add(bs); const pl=new THREE.Mesh(new THREE.CylinderGeometry(1.2, 1.2, 30, 8),pm); pl.position.y=15; pl.castShadow=true; pl.receiveShadow=true; pg.add(pl);
            pg.position.set(px,0,pz); scene.add(pg); rad=1.5; obstacles.push({x:px,z:pz,radius:rad,type:'dungeon'});
        } else if(GAME_MAP==='neon') {
            if(spawned%2===0){const py=new THREE.Mesh(new THREE.ConeGeometry(2, 4, 4),new THREE.MeshStandardMaterial({color:0x00b0ff, emissive:0x00b0ff, emissiveIntensity:1.5, wireframe:true})); py.position.set(px, 3+Math.random()*5, pz); py.rotation.set(Math.random(),Math.random(),Math.random()); scene.add(py); neonObjects.push(py); rad=1.5; obstacles.push({x:px,z:pz,radius:rad,type:'neon'});}
            else{const rn=new THREE.Mesh(new THREE.TorusGeometry(1.5, 0.1, 8, 16),new THREE.MeshStandardMaterial({color:0x00ffcc, emissive:0x00ffcc, emissiveIntensity:2})); rn.position.set(px, 2+Math.random()*4, pz); scene.add(rn); neonObjects.push(rn); rad=1.5; obstacles.push({x:px,z:pz,radius:rad,type:'neon'});}
        }
    }
}
