const canvas = document.getElementById("game");
const ctx = canvas.getContext("2d");
const container = document.getElementById("game-container");
canvas.width = container.clientWidth;
canvas.height = container.clientHeight;

let keys = {};
let mouse = { x: canvas.width/2, y: canvas.height/2, down: false };

let player, enemies, bullets, particles, powerups;
let score, hp, wave, running, paused;

class Player {
    constructor() {
        this.x = canvas.width/2;
        this.y = canvas.height/2;
        this.speed = 3.5;
        this.radius = 20;
    }
    update() {
        let dx = 0, dy = 0;
        if (keys["w"]) dy -= 1;
        if (keys["s"]) dy += 1;
        if (keys["a"]) dx -= 1;
        if (keys["d"]) dx += 1;
        const len = Math.hypot(dx, dy) || 1;
        this.x += dx/len * this.speed;
        this.y += dy/len * this.speed;
        this.x = Math.max(this.radius, Math.min(canvas.width-this.radius, this.x));
        this.y = Math.max(this.radius, Math.min(canvas.height-this.radius, this.y));
    }
    draw() {
        ctx.save();
        ctx.translate(this.x, this.y);
        const angle = Math.atan2(mouse.y - this.y, mouse.x - this.x);
        ctx.rotate(angle);

        const grad = ctx.createRadialGradient(0,0,5,0,0,40);
        grad.addColorStop(0, "rgba(0,200,255,0.9)");
        grad.addColorStop(1, "rgba(0,0,0,0)");
        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.arc(0,0,40,0,Math.PI*2);
        ctx.fill();

        ctx.fillStyle = "#00eaff";
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
        ctx.fillStyle = "#00ffea";
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

function shoot() {
    const angle = Math.atan2(mouse.y - player.y, mouse.x - player.x);
    const bx = player.x + Math.cos(angle)*25;
    const by = player.y + Math.sin(angle)*25;
    bullets.push(new Bullet(bx,by,angle));
    for (let i=0;i<6;i++) {
        particles.push(new Particle(bx,by,"#00ffea"));
    }
}

function update() {
    if (!running || paused) return;

    ctx.clearRect(0,0,canvas.width,canvas.height);

    player.update();
    player.draw();

    enemies.forEach(e => {
        e.update();
        e.draw();
    });

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

    requestAnimationFrame(update);
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
        <p id="save-status" style="font-size:13px;color:#9fd8ff;">Speichere Highscore...</p>
        <button class="btn" id="restart-btn">Restart</button>
        <a class="btn secondary" href="highscore.php">Highscore ansehen</a>
    `;
    document.getElementById("restart-btn").onclick = () => {
        resetGame();
        spawnWave();
        update();
    };

    submitScore(score, wave).then(result => {
        const status = document.getElementById("save-status");
        if (!status) return;
        status.textContent = result.success
            ? "Highscore gespeichert!"
            : "Highscore konnte nicht gespeichert werden.";
    });
}

document.getElementById("start-btn").onclick = () => {
    resetGame();
    spawnWave();
    update();
};

window.addEventListener("keydown", (e) => {
    keys[e.key.toLowerCase()] = true;
    if (e.key === "p") paused = !paused;
    if (e.key === "r") {
        resetGame();
        spawnWave();
        update();
    }
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
    if (mouse.down && running && !paused) shoot();
}, 120);
