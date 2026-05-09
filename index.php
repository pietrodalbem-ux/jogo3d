<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synthetic Dawn | Tactical Cyber-Combat</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=Syncopate:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Bibliotecas 3D -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/EffectComposer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/RenderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/ShaderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/CopyShader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/LuminosityHighPassShader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/UnrealBloomPass.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-black overflow-hidden select-none">

<div id="game-container">
    <canvas id="webgl-canvas"></canvas>
    
    <div id="damage-overlay"></div>
    <div id="special-overlay"></div>
    <div id="crosshair"></div>
    <div id="hit-crosshair"></div>
    <div id="hit-marker">HIT!</div>
    
    <!-- HUD IN-GAME -->
    <div id="hud-bottom-left" class="hidden">
        <div class="hud-item-label">MATRIZ VITAL</div>
        <div id="player-hp-text">300 / 300</div>
        <div id="player-hp-bg"><div id="player-hp-bar"></div></div>
        
        <div class="flex items-end gap-6 mt-4">
            <div id="ammo-display">30/30</div>
            <div id="special-container">
                <div id="special-bar"></div>
                <div id="special-text">SOBRECARGA DISPONÍVEL [Q]</div>
            </div>
        </div>
    </div>
    
    <div id="hud-top-right" class="hidden text-right">
        <div class="hud-item-label">SETOR ATUAL</div>
        <div class="font-orbitron font-bold text-[#00ffcc] text-2xl mb-1 text-shadow-glow">0<span id="level-display">1</span></div>
        <div class="hud-item-label mt-4">DANO ACUMULADO</div>
        <div id="score-display" class="score-text">0</div>
    </div>

    <div id="wave-info" class="hidden">INICIAÇÃO</div>
    <div id="boss-ui" class="hidden">
        <div id="boss-name">ANOMALIA CRÍTICA</div>
        <div id="boss-hp-bg"><div id="boss-hp-bar"></div></div>
    </div>

    <!-- TELA DE INTRODUÇÃO -->
    <div id="screen-intro" class="screen flex flex-col justify-center items-center" onclick="window.showLobby()">

        <div class="scanline"></div>
        <div class="cyber-glitch-container">
            <h1 class="font-orbitron text-6xl md:text-8xl font-black text-white tracking-[0.3em] uppercase mix-blend-difference">Synthetic</h1>
            <h1 class="font-orbitron text-6xl md:text-8xl font-black text-[#00ffcc] tracking-[0.1em] uppercase mt-[-10px]">Dawn</h1>
        </div>
        <div class="mt-12 flex flex-col items-center gap-4">
            <p class="text-[#64748b] tracking-[0.5em] text-xs uppercase animate-pulse">Estabelecendo Conexão Neural...</p>
            <div class="h-[1px] w-48 bg-gradient-to-r from-transparent via-[#00ffcc] to-transparent"></div>
            <p class="text-[#00ffcc] font-rajdhani text-sm tracking-[0.2em] mt-2 uppercase cursor-pointer hover:text-white transition-colors">Clique ou Pressione ESPAÇO para entrar</p>
        </div>
    </div>

    <!-- LOBBY / ARSENAL -->
    <div id="screen-lobby" class="screen hidden" style="background: radial-gradient(circle at 70% 50%, rgba(0, 255, 204, 0.05) 0%, transparent 50%);">
        <div class="absolute left-12 top-12 border-l-2 border-[#00ffcc] pl-6 py-2">
            <h2 class="font-orbitron text-4xl text-white font-bold tracking-tighter">ARSENAL <span class="text-[#00ffcc]">TÁTICO</span></h2>
            <p class="text-[#64748b] text-xs tracking-widest uppercase mt-1">Selecione o seu armamento de combate</p>
        </div>
        
        <div class="flex flex-col gap-6 w-full max-w-md justify-center absolute left-12 top-1/2 -translate-y-1/2 z-10">
            <button class="weapon-btn selected group" id="btn-assault" onclick="selectLobbyWeapon('assault')">
                <div class="flex justify-between items-center">
                    <h3 class="font-orbitron text-white text-lg font-bold group-hover:text-[#00ffcc]">PULSE RIFLE</h3>
                    <span class="text-[10px] text-[#64748b] group-hover:text-[#00ffcc]">MODEL-A</span>
                </div>
                <div class="mt-2 flex gap-1 h-1">
                    <div class="w-full bg-[#00ffcc]/40"></div><div class="w-full bg-[#00ffcc]/40"></div><div class="w-2/3 bg-slate-800"></div>
                </div>
            </button>
            <button class="weapon-btn group" id="btn-shotgun" onclick="selectLobbyWeapon('shotgun')">
                <div class="flex justify-between items-center">
                    <h3 class="font-orbitron text-white text-lg font-bold group-hover:text-[#00ffcc]">SCATTER GUN</h3>
                    <span class="text-[10px] text-[#64748b] group-hover:text-[#00ffcc]">MODEL-S</span>
                </div>
                <div class="mt-2 flex gap-1 h-1">
                    <div class="w-full bg-[#00ffcc]/40"></div><div class="w-1/3 bg-slate-800"></div><div class="w-1/4 bg-slate-800"></div>
                </div>
            </button>
            <button class="weapon-btn group" id="btn-sniper" onclick="selectLobbyWeapon('sniper')">
                <div class="flex justify-between items-center">
                    <h3 class="font-orbitron text-white text-lg font-bold group-hover:text-[#00ffcc]">RAILGUN</h3>
                    <span class="text-[10px] text-[#64748b] group-hover:text-[#00ffcc]">MODEL-R</span>
                </div>
                <div class="mt-2 flex gap-1 h-1">
                    <div class="w-full bg-[#00ffcc]/40"></div><div class="w-full bg-[#00ffcc]/40"></div><div class="w-full bg-[#00ffcc]/40"></div>
                </div>
            </button>
            
            <div class="flex flex-col gap-3 mt-8">
                <button class="btn btn-blue py-4" onclick="openCustomizeModal()">PERSONALIZAR PERSONAGEM</button>
                <button class="btn btn-green py-5 text-xl" onclick="goToMapSelect()">INICIAR OPERAÇÃO ></button>
            </div>
        </div>

        <div id="save-prompt" class="absolute bottom-12 left-12 hidden">
            <div class="flex items-center gap-4 bg-white/5 border border-white/10 p-4 rounded-lg backdrop-blur-sm">
                <div class="text-left">
                    <p class="text-[#00ffcc] text-[10px] tracking-widest font-bold uppercase">Progresso Detectado</p>
                    <p class="text-white/60 text-[10px] uppercase">Deseja continuar ou apagar?</p>
                </div>
                <button class="text-[#ff1744] text-[10px] font-black uppercase hover:bg-[#ff1744]/10 px-3 py-2 rounded transition-colors" onclick="clearSaveAndStart()">Reiniciar Tudo</button>
            </div>
        </div>
    </div>

    <!-- MODAL CUSTOMIZAÇÃO -->
    <div id="modal-customize" class="absolute top-0 left-0 w-full h-full hidden" style="z-index: 25;">
        <div class="w-[400px] h-full bg-[#020408]/95 border-r border-white/10 p-12 flex flex-col justify-center shadow-2xl backdrop-blur-xl absolute left-0 top-0">
            <h2 class="font-orbitron text-3xl text-white mb-10 tracking-widest border-b border-white/10 pb-6">ADAPTAR <span class="text-[#00ffcc]">NÚCLEO</span></h2>
            
            <div class="space-y-8">
                <div class="control-group">
                    <label class="text-xs text-[#64748b] mb-3 block font-bold uppercase tracking-[0.2em]">Pele Sintética</label>
                    <div class="flex gap-4 items-center">
                        <input type="color" id="color-skin" value="#e2e8f0" class="color-picker-custom" onchange="updateLobbyColors()">
                        <span class="text-white/40 text-[10px] font-mono">DIP-RGB HEX</span>
                    </div>
                </div>
                <div class="control-group">
                    <label class="text-xs text-[#64748b] mb-3 block font-bold uppercase tracking-[0.2em]">Armadura Tática</label>
                    <div class="flex gap-4 items-center">
                        <input type="color" id="color-clothes" value="#1e293b" class="color-picker-custom" onchange="updateLobbyColors()">
                        <span class="text-white/40 text-[10px] font-mono">PLT-DEF HEX</span>
                    </div>
                </div>
                <div class="control-group">
                    <label class="text-xs text-[#64748b] mb-3 block font-bold uppercase tracking-[0.2em]">Pintura da Arma</label>
                    <div class="flex gap-4 items-center">
                        <input type="color" id="color-weapon" value="#1e293b" class="color-picker-custom" onchange="updateLobbyColors()">
                        <span class="text-white/40 text-[10px] font-mono">WPN-SIG HEX</span>
                    </div>
                </div>
            </div>

            <button class="btn btn-green w-full mt-16 py-4" onclick="closeCustomizeModal()">CONFIRMAR ALTERAÇÕES</button>
        </div>
    </div>

    <!-- ÁRVORE DE FASES -->
    <div id="screen-map" class="screen hidden" style="z-index: 20; background: radial-gradient(circle at center, #0f172a 0%, #020408 100%);">
        <h2 class="font-orbitron text-4xl text-white mb-16 tracking-[0.3em] font-black uppercase">Mapa de <span class="text-[#00ffcc]">Setores</span></h2>
        <div class="tree-container scale-110">
            <svg width="100%" height="100%" style="position: absolute; top: 0; left: 0; z-index: 1;">
                <path d="M 450 350 L 450 280" stroke="rgba(255,255,255,0.1)" stroke-width="4" />
                <path d="M 450 280 Q 450 180 600 180" id="line-1" stroke="rgba(255,255,255,0.1)" stroke-width="4" fill="none" style="transition: stroke 0.5s ease;" />
                <path d="M 600 180 Q 300 180 250 80" id="line-2" stroke="rgba(255,255,255,0.1)" stroke-width="4" fill="none" style="transition: stroke 0.5s ease;" />
            </svg>
            <div id="node-1" class="ygg-node unlocked" style="left: 450px; top: 350px;" onclick="selectMapTree(1, 'forest')">
                <div class="node-inner">01</div>
                <div class="node-title">Planície Verde</div>
            </div>
            <div id="node-2" class="ygg-node locked" style="left: 600px; top: 180px;" onclick="if(GAME.maxStageUnlocked>=2)selectMapTree(2, 'dungeon')">
                <div class="node-inner" id="lock-2">🔒</div>
                <div class="node-inner-unlocked hidden">02</div>
                <div class="node-title">Ruínas Subterrâneas</div>
            </div>
            <div id="node-3" class="ygg-node locked" style="left: 250px; top: 80px;" onclick="if(GAME.maxStageUnlocked>=3)selectMapTree(3, 'neon')">
                <div class="node-inner" id="lock-3">🔒</div>
                <div class="node-inner-unlocked hidden">03</div>
                <div class="node-title">O Núcleo Sintético</div>
            </div>
        </div>
        <button class="btn mt-24 px-12 border border-white/10 hover:bg-white/5 text-white/50" onclick="showLobby()">RETORNAR</button>
    </div>

    <!-- SELEÇÃO DE PODERES -->
    <div id="screen-power-select" class="screen hidden" style="z-index: 20; background: rgba(2,4,8,0.98);">
        <div class="scanline"></div>
        <h1 class="font-orbitron text-5xl text-[#00ffcc] mb-4 tracking-widest font-black uppercase">Módulo Adquirido</h1>
        <p class="text-[#64748b] mb-16 text-sm uppercase tracking-[0.3em]">Instale uma nova competência bélica na sua matriz neural:</p>
        
        <div class="flex flex-wrap justify-center gap-8 max-w-6xl">
            <div class="power-card group border-l-4 border-[#00ffcc]" onclick="selectPower('kamehameha')">
                <div class="power-card-header">RAIO IONIZANTE</div>
                <p class="power-card-desc">Feixe contínuo de plasma de alta densidade que perfura qualquer blindagem sintética em linha reta.</p>
                <div class="mt-auto pt-6 flex justify-between items-center">
                    <span class="text-[10px] text-[#00ffcc] font-bold">TIPO: ENERGIA</span>
                    <button class="btn btn-green py-2 px-6 text-[10px]">INSTALAR</button>
                </div>
            </div>
            <div class="power-card group border-l-4 border-[#ff1744]" onclick="selectPower('supernova')">
                <div class="power-card-header text-[#ff1744]">SUPERNOVA</div>
                <p class="power-card-desc">Colapso térmico massivo que liberta uma onda de choque de 30 metros, incinerando tudo ao redor.</p>
                <div class="mt-auto pt-6 flex justify-between items-center">
                    <span class="text-[10px] text-[#ff1744] font-bold">TIPO: EXPLOSIVO</span>
                    <button class="btn btn-red py-2 px-6 text-[10px]">INSTALAR</button>
                </div>
            </div>
            <div class="power-card group border-l-4 border-[#aa00ff]" onclick="selectPower('relativity')">
                <div class="power-card-header text-[#aa00ff]">ESTASE TEMPORAL</div>
                <p class="power-card-desc">Manipulação gravitacional localizada. Bloqueia as funções motoras e processamento de todas as entidades.</p>
                <div class="mt-auto pt-6 flex justify-between items-center">
                    <span class="text-[10px] text-[#aa00ff] font-bold">TIPO: TEMPORAL</span>
                    <button class="btn btn-purple py-2 px-6 text-[10px]">INSTALAR</button>
                </div>
            </div>
        </div>
    </div>

    <!-- VITÓRIA -->
    <div id="screen-win" class="screen hidden" style="z-index: 20; background: rgba(2,4,8,0.98);">
        <div class="scanline"></div>
        <h1 class="font-orbitron text-7xl text-[#00ffcc] mb-6 tracking-[0.3em] font-black uppercase">Missão Concluída</h1>
        <p class="text-[#64748b] mb-12 text-sm uppercase tracking-[0.5em]">O núcleo sintético foi purificado. Ameaça eliminada.</p>
        <div class="bg-white/5 p-8 rounded-lg border border-white/10 mb-12 text-center">
            <div class="text-[#64748b] text-[10px] mb-2 tracking-widest font-bold">TOTAL DE DANO INFLIGIDO</div>
            <p class="font-orbitron text-6xl text-white tracking-widest"><span id="win-score">0</span></p>
        </div>
        <button class="btn btn-green px-12 py-5 text-xl" onclick="clearSaveAndStart(); location.reload();">REINICIAR SIMULAÇÃO</button>
    </div>

    <!-- GAME OVER -->
    <div id="screen-gameover" class="screen hidden" style="z-index: 20; background: rgba(15, 0, 0, 0.98);">
        <div class="scanline"></div>
        <h1 class="font-orbitron text-7xl text-[#ff1744] mb-6 tracking-[0.3em] font-black uppercase">Falha Crítica</h1>
        <p class="text-[#64748b] mb-12 text-sm uppercase tracking-[0.5em]">Conexão neural perdida. Matriz destruída em combate.</p>
        <div class="bg-white/5 p-8 rounded-lg border border-white/10 mb-12 text-center">
            <div class="text-[#64748b] text-[10px] mb-2 tracking-widest font-bold">DANO CAUSADO ANTES DA FALHA</div>
            <p class="font-orbitron text-6xl text-white tracking-widest"><span id="final-score">0</span></p>
        </div>
        <button class="btn btn-red px-12 py-5 text-xl" onclick="location.reload();">RECONECTAR AO SISTEMA</button>
    </div>
</div>

<!-- Pausa / Perda de Foco -->
<div id="screen-pause" class="screen hidden" style="background: rgba(0,0,0,0.9); z-index: 60;">
    <div class="flex flex-col items-center">
        <div class="w-24 h-24 border-4 border-[#00ffcc] rounded-full flex items-center justify-center animate-pulse mb-12">
            <div class="w-12 h-12 bg-[#00ffcc] rounded-sm"></div>
        </div>
        <h2 class="font-orbitron text-5xl text-white mb-4 tracking-widest font-black">SISTEMA <span class="text-[#00ffcc]">SUSPENSO</span></h2>
        <p class="text-[#64748b] text-lg mb-12 uppercase tracking-widest">Aguardando reinicialização da interface neural</p>
        <button class="btn btn-green text-xl px-12 py-5" onclick="resumeFromPause()">REATIVAR CONEXÃO</button>
    </div>
</div>

<!-- Scripts de Inicialização -->
<script type="module" src="js/main.js"></script>

</body>
</html>