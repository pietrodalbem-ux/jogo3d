import { SHARED } from '../utils/constants.js';

export function createSkeleton(isBoss, GAME_MAP) {
    const grp=new THREE.Group(), mat=isBoss?SHARED.mat.boss:(GAME_MAP==='forest'?SHARED.mat.boneFor:(GAME_MAP==='neon'?SHARED.mat.boneNeo:SHARED.mat.boneDun)), s=isBoss?2.5:1.0;
    
    const hg=new THREE.Group(); hg.position.y=1.7*s; 
    const sk=new THREE.Mesh(SHARED.geo.skull,mat); sk.castShadow=true; hg.add(sk);
    const em=isBoss?SHARED.mat.eyeBoss:SHARED.mat.eyeNorm; 
    const el=new THREE.Mesh(new THREE.BoxGeometry(0.15*s,0.15*s,0.1*s),em); el.position.set(-0.25*s,0.05*s,0.41*s); hg.add(el); 
    const er=new THREE.Mesh(new THREE.BoxGeometry(0.15*s,0.15*s,0.1*s),em); er.position.set(0.25*s,0.05*s,0.41*s); hg.add(er);
    if(isBoss){const cw=new THREE.Mesh(new THREE.CylinderGeometry(0.4*s,0.5*s,0.3*s,6),SHARED.mat.crown); cw.position.y=0.5*s; hg.add(cw);} grp.add(hg);
    
    const tg=new THREE.Group(); tg.position.y=1.0*s; 
    const spine = new THREE.Mesh(SHARED.geo.bone, mat); spine.castShadow=true; tg.add(spine);
    grp.add(tg);
    
    const al=new THREE.Group(); al.position.set(-0.5*s,1.4*s,0); const alm=new THREE.Mesh(SHARED.geo.bone,mat); alm.position.y=-0.4*s; alm.castShadow=true; al.add(alm); const alj=new THREE.Mesh(SHARED.geo.joint,mat); al.add(alj); grp.add(al);
    const ar=new THREE.Group(); ar.position.set(0.5*s,1.4*s,0); const arm=new THREE.Mesh(SHARED.geo.bone,mat); arm.position.y=-0.4*s; arm.castShadow=true; ar.add(arm); const arj=new THREE.Mesh(SHARED.geo.joint,mat); ar.add(arj); grp.add(ar);
    const ll=new THREE.Group(); ll.position.set(-0.25*s,0.8*s,0); const llm=new THREE.Mesh(SHARED.geo.bone,mat); llm.position.y=-0.4*s; llm.castShadow=true; ll.add(llm); const llj=new THREE.Mesh(SHARED.geo.joint,mat); ll.add(llj); grp.add(ll);
    const lr=new THREE.Group(); lr.position.set(0.25*s,0.8*s,0); const lrm=new THREE.Mesh(SHARED.geo.bone,mat); lrm.position.y=-0.4*s; lrm.castShadow=true; lr.add(lrm); const lrj=new THREE.Mesh(SHARED.geo.joint,mat); lr.add(lrj); grp.add(lr);
    
    if(isBoss){
        const sw=new THREE.Mesh(SHARED.geo.sword,SHARED.mat.sword); 
        sw.position.set(0,0,-1.2); 
        sw.rotation.x=Math.PI/8; 
        tg.add(sw); 
        grp.userData.sword = sw;
    }
    
    // Use Object.assign para manter propriedades anteriores (como a espada)
    Object.assign(grp.userData, {
        head: hg,
        torso: tg,
        armL: al,
        armR: ar,
        legL: ll,
        legR: lr,
        headBox: new THREE.Box3(),
        bodyBox: new THREE.Box3()
    });
    
    return grp;
}


export function getValidSpawnPosition(bx,bz,r, obstacles) {
    let x=Math.max(-95+r,Math.min(95-r,bx)), z=Math.max(-95+r,Math.min(95-r,bz));
    for(let o of obstacles){let d=Math.hypot(x-o.x,z-o.z); if(d<o.radius+r+1){let dx=x-o.x,dz=z-o.z; if(dx===0&&dz===0){dx=1;dz=0;} let pd=new THREE.Vector2(dx,dz).normalize(); x+=pd.x*(o.radius+r+1-d); z+=pd.y*(o.radius+r+1-d);}}
    return {x:Math.max(-95+r,Math.min(95-r,x)),z:Math.max(-95+r,Math.min(95-r,z))};
}
