(function () {
    const canvas = document.getElementById('breakout');
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;

    const GOLD = '#C8A96E';
    const CYAN = '#4EC9B0';

    // Pixel patterns pour "404" (5 lignes x 4 colonnes par chiffre)
    const DIGITS = {
        '4': [
            [1,0,0,1],
            [1,0,0,1],
            [1,1,1,1],
            [0,0,0,1],
            [0,0,0,1],
        ],
        '0': [
            [0,1,1,0],
            [1,0,0,1],
            [1,0,0,1],
            [1,0,0,1],
            [0,1,1,0],
        ],
    };

    const BRICK_W   = 24;   // 20 * 1.2
    const BRICK_H   = 12;   // 10 * 1.2
    const BRICK_PAD = 5;    // 4  * 1.2 (arrondi)
    const DIGIT_GAP = 19;   // 16 * 1.2 (arrondi)
    const DIGIT_COLS = 4;

    function buildBricks() {
        const bricks = [];
        const digits = ['4', '0', '4'];
        const totalW = digits.length * (DIGIT_COLS * (BRICK_W + BRICK_PAD) - BRICK_PAD) + (digits.length - 1) * DIGIT_GAP;
        const startX = (W - totalW) / 2;
        const startY = 48;   // 40 * 1.2

        digits.forEach((d, di) => {
            const pattern = DIGITS[d];
            const offsetX = startX + di * (DIGIT_COLS * (BRICK_W + BRICK_PAD) + DIGIT_GAP);
            pattern.forEach((row, ri) => {
                row.forEach((cell, ci) => {
                    if (cell) {
                        bricks.push({
                            x: offsetX + ci * (BRICK_W + BRICK_PAD),
                            y: startY + ri * (BRICK_H + BRICK_PAD),
                            w: BRICK_W,
                            h: BRICK_H,
                            alive: true,
                            color: di === 1 ? CYAN : GOLD,
                        });
                    }
                });
            });
        });
        return bricks;
    }

    let bricks, ball, paddle, running, animId, score, totalBricks;

    function initGame() {
        bricks = buildBricks();
        totalBricks = bricks.filter(b => b.alive).length;
        score = 0;

        paddle = {
            w: 96,   // 80 * 1.2
            h: 12,   // 10 * 1.2
            x: W / 2 - 48,
            y: H - 36,  // 30 * 1.2
        };

        ball = {
            x: W / 2,
            y: H - 60,  // 50 * 1.2
            r: 7,       // 6  * 1.2 (arrondi)
            vx: 0, vy: 0,
            launched: false,
        };

        running = false;
    }

    function launch() {
        if (!ball.launched) {
            ball.vx = (Math.random() > 0.5 ? 1 : -1) * 3.6;  // 3 * 1.2
            ball.vy = -4.8;  // -4 * 1.2
            ball.launched = true;
            running = true;
        }
    }

    const keys = {};
    document.addEventListener('keydown', e => {
        keys[e.code] = true;
        if (e.code === 'Space') {
            e.preventDefault();
            launch();
        }
    });
    document.addEventListener('keyup', e => { keys[e.code] = false; });

    canvas.addEventListener('mousemove', e => {
        const rect = canvas.getBoundingClientRect();
        const mx = (e.clientX - rect.left) * (W / rect.width);
        paddle.x = mx - paddle.w / 2;
    });
    canvas.addEventListener('click', () => launch());
    canvas.addEventListener('touchmove', e => {
        e.preventDefault();
        const rect = canvas.getBoundingClientRect();
        const tx = (e.touches[0].clientX - rect.left) * (W / rect.width);
        paddle.x = tx - paddle.w / 2;
    }, { passive: false });
    canvas.addEventListener('touchstart', () => launch());

    function update() {
        if (keys['ArrowLeft'])  paddle.x -= 7;   // 6 * 1.2 (arrondi)
        if (keys['ArrowRight']) paddle.x += 7;
        paddle.x = Math.max(0, Math.min(W - paddle.w, paddle.x));

        if (!ball.launched) {
            ball.x = paddle.x + paddle.w / 2;
            return;
        }

        ball.x += ball.vx;
        ball.y += ball.vy;

        if (ball.x - ball.r < 0)  { ball.x = ball.r;      ball.vx *= -1; }
        if (ball.x + ball.r > W)  { ball.x = W - ball.r;  ball.vx *= -1; }
        if (ball.y - ball.r < 0)  { ball.y = ball.r;      ball.vy *= -1; }

        if (ball.y - ball.r > H) {
            running = false;
            showOverlay('loseOverlay');
            return;
        }

        if (
            ball.y + ball.r >= paddle.y &&
            ball.y - ball.r <= paddle.y + paddle.h &&
            ball.x >= paddle.x &&
            ball.x <= paddle.x + paddle.w
        ) {
            const hit = (ball.x - (paddle.x + paddle.w / 2)) / (paddle.w / 2);
            ball.vx = hit * 6;
            ball.vy = -Math.abs(ball.vy);
            ball.y = paddle.y - ball.r;
        }

        for (const b of bricks) {
            if (!b.alive) continue;
            if (
                ball.x + ball.r > b.x &&
                ball.x - ball.r < b.x + b.w &&
                ball.y + ball.r > b.y &&
                ball.y - ball.r < b.y + b.h
            ) {
                b.alive = false;
                score++;
                ball.vy *= -1;

                if (score >= totalBricks) {
                    running = false;
                    setTimeout(() => showOverlay('winOverlay'), 400);
                    return;
                }
                break;
            }
        }
    }

    function draw() {
        ctx.clearRect(0, 0, W, H);

        // Grille de fond subtile
        ctx.strokeStyle = 'rgba(255,255,255,0.03)';
        ctx.lineWidth = 1;
        for (let x = 0; x < W; x += 24) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, H); ctx.stroke(); }
        for (let y = 0; y < H; y += 24) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(W, y); ctx.stroke(); }

        // Briques
        bricks.forEach(b => {
            if (!b.alive) return;
            ctx.fillStyle = b.color;
            ctx.shadowColor = b.color;
            ctx.shadowBlur = 8;
            ctx.beginPath();
            ctx.roundRect(b.x, b.y, b.w, b.h, 3);
            ctx.fill();
            ctx.shadowBlur = 0;
        });

        // Paddle
        ctx.fillStyle = GOLD;
        ctx.shadowColor = GOLD;
        ctx.shadowBlur = 12;
        ctx.beginPath();
        ctx.roundRect(paddle.x, paddle.y, paddle.w, paddle.h, 6);
        ctx.fill();
        ctx.shadowBlur = 0;

        // Balle
        ctx.fillStyle = CYAN;
        ctx.shadowColor = CYAN;
        ctx.shadowBlur = 14;
        ctx.beginPath();
        ctx.arc(ball.x, ball.y, ball.r, 0, Math.PI * 2);
        ctx.fill();
        ctx.shadowBlur = 0;

        // Score
        ctx.fillStyle = 'rgba(255,255,255,0.25)';
        ctx.font = '12px monospace';
        ctx.fillText(`${score} / ${totalBricks}`, 14, H - 14);
    }

    function loop() {
        if (running) update();
        else if (ball.launched && !running) return;
        else update();
        draw();
        animId = requestAnimationFrame(loop);
    }

    function showOverlay(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).style.display = 'flex';
    }

    function hideOverlays() {
        ['overlay', 'winOverlay', 'loseOverlay'].forEach(id => {
            const el = document.getElementById(id);
            el.classList.add('hidden');
            el.style.display = 'none';
        });
    }

    document.getElementById('startBtn').addEventListener('click', () => {
        hideOverlays();
        initGame();
        if (animId) cancelAnimationFrame(animId);
        loop();
    });

    document.getElementById('retryBtn').addEventListener('click', () => {
        hideOverlays();
        initGame();
        if (animId) cancelAnimationFrame(animId);
        loop();
    });

    initGame();
    draw();

})();