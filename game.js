const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");
const container = document.getElementById("game-container");
canvas.width = container.clientWidth;
canvas.height = container.clientHeight;

let keys = {};
let mouse = { x: canvas.width/2, y: canvas.height/2, down: false };

// --- Gamepad-Unterstützung ---
let gamepadIndex = null;
let gamepadAimActive = false;
let gamepadAimAngle = 0;
let gamepadShootHeld = false;
let gamepadPauseWasPressed = false;

window.addEventListener("gamepadconnected", (e) => {
    gamepadIndex = e.gamepad.index;
});
window.addEventListener("gamepaddisconnected", (e) => {
    if (gamepadIndex === e.gamepad.index) gamepadIndex = null;
});

function pollGamepad() {
    if (gamepadIndex === null) return;
    const gp = navigator.getGamepads()[gamepadIndex];
    if (!gp) return;

    const deadzone = 0.2;

    // Linker Stick -> Bewegung (in "keys" einspeisen, damit Player.update() es direkt nutzt)
    const lx = gp.axes[0] || 0, ly = gp.axes[1] || 0;
    keys["gp_left"]  = lx < -deadzone;
    keys["gp_right"] = lx > deadzone;
    keys["gp_up"]    = ly < -deadzone;
    keys["gp_down"]  = ly > deadzone;

    // Rechter Stick -> Zielrichtung
    const rx = gp.axes[2] || 0, ry = gp.axes[3] || 0;
    if (Math.hypot(rx, ry) > deadzone) {
        gamepadAimActive = true;
        gamepadAimAngle = Math.atan2(ry, rx);
    } else {
        gamepadAimActive = false;
    }

    // Rechter Trigger (RT/R2, Index 7) oder A/X-Knopf (Index 0) -> Schiessen
    gamepadShootHeld = !!(gp.buttons[7] && gp.buttons[7].pressed) ||
                        !!(gp.buttons[0] && gp.buttons[0].pressed);

    // Start-Knopf (Index 9) -> Pause (nur bei neuem Tastendruck umschalten)
    const pausePressed = !!(gp.buttons[9] && gp.buttons[9].pressed);
    if (pausePressed && !gamepadPauseWasPressed) paused = !paused;
    gamepadPauseWasPressed = pausePressed;
}

// Liefert den aktuellen Zielwinkel: rechter Stick hat Vorrang vor der Maus
function getAimAngle(x, y) {
    if (gamepadAimActive) return gamepadAimAngle;
    return Math.atan2(mouse.y - y, mouse.x - x);
}

let player, enemies, bullets, particles, powerups;
let score, hp, wave, running, paused;
let rafId = null;

class Player {
    constructor() {
        this.x = canvas.width/2;
        this.y = canvas.height/2;
        this.speed = 3.5;
        this.radius = 20;
    }
    update() {
        let dx = 0, dy = 0;
        if (keys["w"] || keys["gp_up"]) dy -= 1;
        if (keys["s"] || keys["gp_down"]) dy += 1;
        if (keys["a"] || keys["gp_left"]) dx -= 1;
        if (keys["d"] || keys["gp_right"]) dx += 1;
        const len = Math.hypot(dx, dy) || 1;
        this.x += dx/len * this.speed;
        this.y += dy/len * this.speed;
        this.x = Math.max(this.radius, Math.min(canvas.width-this.radius, this.x));
        this.y = Math.max(this.radius, Math.min(canvas.height-this.radius, this.y));
    }
    draw() {
        ctx.save();
        ctx.translate(this.x, this.y);
        const angle = getAimAngle(this.x, this.y);
        ctx.rotate(angle);

        const grad = ctx.createRadialGradient(0,0,5,0,0,40);
        grad.addColorStop(0, "rgba(140,191,53,0.9)");
        grad.addColorStop(1, "rgba(0,0,0,0)");
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.arc(0,0,40,0,Math.PI*2);
        ctx.fill();

        ctx.fillStyle = "#8CBF35";
        ctx.beginPath();
        ctx.moveTo(25,0);
        ctx.lineTo(-20,-15);
        ctx.lineTo(-20,15);
        ctx.closePath();
        ctx.fill();

        ctx.restore();
    }
}

class Enemy {
    constructor() {
        this.x = Math.random()*canvas.width;
        this.y = -50;
        this.radius = 18;
        this.speed = 1.2 + wave*0.2;
        this.hp = 2 + wave;
    }
    update() {
        // Verfolgung des Spielers
        const dx = player.x - this.x;
        const dy = player.y - this.y;
        const dist = Math.hypot(dx, dy) || 1;
        this.x += dx/dist * this.speed;
        this.y += dy/dist * this.speed;
    }
    draw() {
        ctx.save();
        ctx.translate(this.x, this.y);

        const grad = ctx.createRadialGradient(0,0,5,0,0,40);
        grad.addColorStop(0, "rgba(255,0,120,0.9)");
        grad.addColorStop(1, "rgba(0,0,0,0)");
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.arc(0,0,40,0,Math.PI*2);
        ctx.fill();

        ctx.fillStyle = "#ff0078";
        ctx.beginPath();
        ctx.arc(0,0,this.radius,0,Math.PI*2);
        ctx.fill();

        ctx.restore();
    }
}

class Bullet {
    constructor(x,y,angle) {
        this.x = x;
        this.y = y;
        this.speed = 8;
        this.vx = Math.cos(angle)*this.speed;
        this.vy = Math.sin(angle)*this.speed;
        this.radius = 4;
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
    }
    draw() {
        ctx.save();
        ctx.translate(this.x, this.y);
        ctx.fillStyle = "#B6E14A";
        ctx.beginPath();
        ctx.arc(0,0,this.radius,0,Math.PI*2);
        ctx.fill();
        ctx.restore();
    }
}

class Particle {
    constructor(x,y,color) {
        this.x = x;
        this.y = y;
        this.vx = (Math.random()-0.5)*4;
        this.vy = (Math.random()-0.5)*4;
        this.life = 30;
        this.color = color;
    }
    update() {
        this.x += this.vx;
        this.y += this.vy;
        this.life--;
    }
    draw() {
        ctx.save();
        ctx.globalAlpha = this.life/30;
        ctx.fillStyle = this.color;
        ctx.beginPath();
        ctx.arc(this.x,this.y,3,0,Math.PI*2);
        ctx.fill();
        ctx.restore();
    }
}

function resetGame() {
    player = new Player();
    enemies = [];
    bullets = [];
    particles = [];
    score = 0;
    hp = 100;
    wave = 1;
    running = true;
    paused = false;
    document.getElementById("hp").textContent = hp;
    document.getElementById("score").textContent = score;
    document.getElementById("wave").textContent = wave;
    document.getElementById("center-overlay").style.display = "none";
}

function spawnWave() {
    for (let i=0;i<5+wave*2;i++) {
        enemies.push(new Enemy());
    }
}

// Verhindert, dass Gegner sich gegenseitig durchdringen bzw. aufeinander stapeln,
// indem sich überlappende Gegner nach jedem Update auseinander schieben.
function resolveEnemyCollisions() {
    for (let pass = 0; pass < 3; pass++) {
        for (let i = 0; i < enemies.length; i++) {
            for (let j = i + 1; j < enemies.length; j++) {
                const a = enemies[i], b = enemies[j];
                const dx = b.x - a.x, dy = b.y - a.y;
                let dist = Math.hypot(dx, dy);
                const minDist = a.radius + b.radius;
                if (dist < minDist) {
                    if (dist < 0.01) {
                        // exakt gleiche Position -> zufällige Richtung erzwingen
                        dist = 0.01;
                    }
                    const overlap = (minDist - dist) / 2;
                    const nx = dx / dist, ny = dy / dist;
                    a.x -= nx * overlap;
                    a.y -= ny * overlap;
                    b.x += nx * overlap;
                    b.y += ny * overlap;
                }
            }
        }
        // Sanft aus dem Spieler herausdrücken, damit sie nicht in dessen Zentrum sitzen
        enemies.forEach(e => {
            const dx = e.x - player.x, dy = e.y - player.y;
            let dist = Math.hypot(dx, dy) || 0.01;
            const minDist = e.radius + player.radius;
            if (dist < minDist) {
                const overlap = minDist - dist;
                e.x += (dx / dist) * overlap;
                e.y += (dy / dist) * overlap;
            }
        });
    }
}

function shoot() {
    const angle = getAimAngle(player.x, player.y);
    const bx = player.x + Math.cos(angle)*25;
    const by = player.y + Math.sin(angle)*25;
    bullets.push(new Bullet(bx,by,angle));
    for (let i=0;i<6;i++) {
        particles.push(new Particle(bx,by,"#B6E14A"));
    }
}

function update() {
    if (!running || paused) return;

    ctx.clearRect(0,0,canvas.width,canvas.height);

    pollGamepad();

    player.update();
    player.draw();

    enemies.forEach(e => e.update());
    resolveEnemyCollisions();
    enemies.forEach(e => e.draw());

    bullets.forEach(b => {
        b.update();
        b.draw();
    });

    particles.forEach(p => {
        p.update();
        p.draw();
    });

    bullets = bullets.filter(b => {
        if (b.x<0 || b.x>canvas.width || b.y<0 || b.y>canvas.height) return false;
        let hit = false;
        enemies.forEach(e => {
            const dist = Math.hypot(b.x - e.x, b.y - e.y);
            if (dist < e.radius+4) {
                e.hp--;
                hit = true;
                for (let i=0;i<8;i++) {
                    particles.push(new Particle(e.x,e.y,"#ff0078"));
                }
                if (e.hp<=0) {
                    score += 10;
                    document.getElementById("score").textContent = score;
                }
            }
        });
        enemies = enemies.filter(e => e.hp>0);
        return !hit;
    });

    enemies.forEach(e => {
        const dist = Math.hypot(e.x - player.x, e.y - player.y);
        if (dist < e.radius + player.radius) {
            hp -= 0.4;
            document.getElementById("hp").textContent = Math.floor(hp);
            particles.push(new Particle(player.x,player.y,"#ff0000"));
            if (hp <= 0) gameOver();
        }
    });

    particles = particles.filter(p => p.life>0);

    if (enemies.length === 0) {
        wave++;
        document.getElementById("wave").textContent = wave;
        spawnWave();
    }

    rafId = requestAnimationFrame(update);
}

// Startet (oder neu-startet) das Spiel und stellt sicher, dass
// niemals zwei update()-Loops gleichzeitig laufen
function startGame() {
    if (rafId !== null) {
        cancelAnimationFrame(rafId);
        rafId = null;
    }
    resetGame();
    spawnWave();
    update();
}

// Sendet das Ergebnis ans Backend, sobald die Runde vorbei ist
async function submitScore(finalScore, finalWave) {
    try {
        const res = await fetch("api/save_score.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ score: finalScore, wave: finalWave })
        });
        return await res.json();
    } catch (err) {
        console.error("Highscore konnte nicht gespeichert werden:", err);
        return { success: false };
    }
}

function gameOver() {
    running = false;
    const overlay = document.getElementById("center-overlay");
    overlay.style.display = "flex";
    overlay.innerHTML = `
        <h1>Game Over</h1>
        <p>Score: ${score} · Welle: ${wave}</p>
        <p id="save-status" style="font-size:13px;color:#C9E28F;">Speichere Highscore...</p>
        <a class="btn secondary" href="highscore.php">Highscore ansehen</a>
    `;

    submitScore(score, wave).then(result => {
        const status = document.getElementById("save-status");
        if (!status) return;
        status.textContent = result.success
            ? "Highscore gespeichert! Du hattest nur einen Versuch."
            : "Highscore konnte nicht gespeichert werden.";
    });
}

document.getElementById("start-btn").onclick = startGame;

window.addEventListener("keydown", (e) => {
    keys[e.key.toLowerCase()] = true;
    if (e.key === "p") paused = !paused;
});
window.addEventListener("keyup", (e) => keys[e.key.toLowerCase()] = false);

canvas.addEventListener("mousemove", (e) => {
    const rect = canvas.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
});
canvas.addEventListener("mousedown", () => { mouse.down = true; shoot(); });
canvas.addEventListener("mouseup", () => mouse.down = false);

setInterval(() => {
    if ((mouse.down || gamepadShootHeld) && running && !paused) shoot();
}, 120);