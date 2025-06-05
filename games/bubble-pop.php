<?php
session_start();
$lang = $_SESSION['lang'] ?? 'en';
$langFile = "../lang/$lang.json";
$translations = file_exists($langFile) ? json_decode(file_get_contents($langFile), true) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bubble Pop Game</title>
    <meta name="viewport" content="width=420, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bubblepop.css">
</head>

<body>
    <a href="../" class="bubble-back">← Back to Start</a>
    <button id="show-leaderboard-btn" class="custom-btn btn-outline-light" style="margin-bottom:1.2rem;">
        🏆 Show Leaderboard
    </button>
    <div class="bubble-flex-row">
        <div id="side-leaderboard">
            <div id="side-leaderboard-content"></div>
        </div>
        <div style="display:flex; flex-direction:column; align-items:center;">
            <div class="bubble-game-container">
                <div class="bubble-game-header">
                    <span id="bubble-score">Score: 0</span>
                    <span id="bubble-timer" style="display:none;">Time: 30</span>
                </div>
                <div id="bubble-start-screen" style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(20,0,40,0.97);z-index:20;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:18px;">
                    <h2 style="color:#00ffe7;margin-bottom:1.5rem;">Bubble Pop</h2>
                    <button id="bubble-play-btn" style="padding:0.7rem 2.2rem;font-size:1.2rem;border-radius:999px;border:none;background:#00ffe7;color:#1a0033;font-weight:700;cursor:pointer;">Play</button>
                </div>
                <canvas id="bubble-canvas" width="400" height="600"></canvas>
                <div class="bubble-game-end" id="bubble-game-end" style="display:none;"></div>
            </div>
        </div>
    </div>
    <script>
        // --- Leaderboard toggle logic ---
        const showLbBtn = document.getElementById('show-leaderboard-btn');
        const sideLb = document.getElementById('side-leaderboard');
        const sideLbContent = document.getElementById('side-leaderboard-content');

        showLbBtn.onclick = function () {
            if (sideLb.style.display === "none" || sideLb.style.display === "") {
                showLeaderboardSide();
                sideLb.style.display = "block";
                showLbBtn.textContent = "❌ Hide Leaderboard";
                sessionStorage.setItem('bubbleLeaderboardOpen', '1');
                fetch('save-leaderboard.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ toggle: 'open' }) });
            } else {
                sideLb.style.display = "none";
                showLbBtn.textContent = "🏆 Show Leaderboard";
                sessionStorage.removeItem('bubbleLeaderboardOpen');
                fetch('save-leaderboard.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ toggle: 'close' }) });
            }
        };

        window.addEventListener('DOMContentLoaded', () => {
            if (sessionStorage.getItem('bubbleLeaderboardOpen') === '1') {
                showLeaderboardSide();
                sideLb.style.display = "block";
                showLbBtn.textContent = "❌ Hide Leaderboard";
            }
        });

        function showLeaderboardSide() {
            fetch('save-leaderboard.php')
                .then(res => res.json())
                .then(data => {
                    let board = Array.isArray(data) ? data : data.leaderboard;
                    renderLeaderboard(board, sideLbContent);
                });
        }

        // --- Game logic ---
        let bubbles = [], score = 0, spawnRate = 700, bubbleInterval, running = false, bonusActive = false;
        let lives = 3;
        const canvas = document.getElementById('bubble-canvas');
        const ctx = canvas.getContext('2d');
        const scoreEl = document.getElementById('bubble-score');
        const timerEl = document.getElementById('bubble-timer');
        const endScreen = document.getElementById('bubble-game-end');

        timerEl.style.display = "none";

        // Add hearts display
        let heartsEl = document.createElement('span');
        heartsEl.id = "bubble-hearts";
        heartsEl.style.marginLeft = "auto";
        heartsEl.style.fontSize = "1.3rem";
        heartsEl.style.letterSpacing = "0.2rem";
        document.querySelector('.bubble-game-header').appendChild(heartsEl);

        function updateHearts() {
            heartsEl.innerHTML = "❤️".repeat(lives) + "🖤".repeat(3 - lives);
            heartsEl.classList.remove('heart-pop');
            void heartsEl.offsetWidth;
            heartsEl.classList.add('heart-pop');
        }

        function randomColor() {
            const colors = ['#ffb6e6', '#aee6e6', '#ffd700', '#ff6f61', '#6e7ff3', '#ffb347'];
            return colors[Math.floor(Math.random() * colors.length)];
        }
        function spawnBubble(isBonus = false) {
            const radius = isBonus ? 32 : (Math.random() * 22 + 18);
            bubbles.push({
                x: Math.random() * (canvas.width - 2 * radius) + radius,
                y: canvas.height + radius,
                r: radius,
                color: isBonus ? '#ffd700' : randomColor(),
                speed: isBonus ? 2.2 : (Math.random() * 1.5 + 1.2),
                bonus: isBonus
            });
        }
        function drawBubbles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (const b of bubbles) {
                ctx.beginPath();
                ctx.arc(b.x, b.y, b.r, 0, 2 * Math.PI);
                ctx.fillStyle = b.color;
                ctx.globalAlpha = b.bonus ? 1 : 0.85;
                ctx.fill();
                ctx.globalAlpha = 1;
                ctx.strokeStyle = b.bonus ? "#fff700" : "#fff";
                ctx.lineWidth = b.bonus ? 4 : 2;
                ctx.stroke();
                if (b.bonus) {
                    ctx.font = "bold 18px Arial";
                    ctx.fillStyle = "#fff";
                    ctx.textAlign = "center";
                    ctx.fillText("★", b.x, b.y + 6);
                }
            }
        }
        function updateBubbles() {
            for (const b of bubbles) b.y -= b.speed;
            let lost = 0;
            bubbles = bubbles.filter(b => {
                if (b.y - b.r <= 0) {
                    if (!b.bonus) lost++;
                    return false;
                }
                return true;
            });
            if (lost > 0) {
                lives -= lost;
                updateHearts();
                if (lives <= 0) endGame();
            }
        }
        canvas.addEventListener('click', function (e) {
            if (!running) return;
            const rect = canvas.getBoundingClientRect();
            const mx = e.clientX - rect.left, my = e.clientY - rect.top;
            for (let i = 0; i < bubbles.length; ++i) {
                const b = bubbles[i];
                if (Math.hypot(mx - b.x, my - b.y) < b.r) {
                    if (b.bonus) {
                        score += 5;
                        bonusActive = false;
                    } else {
                        score++;
                    }
                    bubbles.splice(i, 1);
                    scoreEl.textContent = "Score: " + score;
                    if (score > 0 && score % 50 === 0 && !bonusActive) {
                        spawnBubble(true);
                        bonusActive = true;
                    }
                    let newRate = Math.max(300, 700 - Math.floor(score / 100) * 50);
                    if (newRate !== spawnRate) {
                        spawnRate = newRate;
                        clearInterval(bubbleInterval);
                        bubbleInterval = setInterval(() => spawnBubble(), spawnRate);
                    }
                    break;
                }
            }
        });
        function gameLoop() {
            if (!running) return;
            updateBubbles();
            drawBubbles();
            requestAnimationFrame(gameLoop);
        }
        function startGame() {
            score = 0; spawnRate = 700; bubbles = []; bonusActive = false; lives = 3;
            scoreEl.textContent = "Score: 0";
            canvas.style.display = "block";
            endScreen.style.display = "none";
            running = true;
            updateHearts();
            spawnBubble();
            clearInterval(bubbleInterval);
            bubbleInterval = setInterval(() => spawnBubble(), spawnRate);
            gameLoop();
        }
        function endGame() {
            running = false;
            clearInterval(bubbleInterval);
            showLeaderboardForm();
        }

        // --- Leaderboard logic ---
        function showLeaderboardForm() {
            endScreen.style.display = "block";
            endScreen.innerHTML = `
        <h2>Game Over!</h2>
        <p>Your score: <span id="bubble-final-score">${score}</span></p>
        <form id="bubble-leaderboard-form" style="margin-top:1rem;">
            <input type="text" id="bubble-username" maxlength="16" placeholder="Your name/nickname" required
                style="padding:0.4rem 1rem;border-radius:999px;border:none;font-size:1rem;">
            <button type="submit" style="padding:0.4rem 1.2rem;border-radius:999px;border:none;background:linear-gradient(90deg,#aee6e6,#394867);color:#232946;font-weight:600;cursor:pointer;font-size:1rem;">Submit</button>
        </form>
        <div id="bubble-leaderboard-msg" style="color:#ffb6b6;margin-top:0.5rem;"></div>
        <div id="bubble-leaderboard-list" style="margin-top:1.2rem;"></div>
        <button id="bubble-restart" style="margin-top:1.2rem;">Play Again</button>
        `;
            endScreen.classList.remove('bubble-popup');
            void endScreen.offsetWidth;
            endScreen.classList.add('bubble-popup');
            document.getElementById('bubble-restart').onclick = startGame;
            document.getElementById('bubble-leaderboard-form').onsubmit = function (e) {
                e.preventDefault();
                const username = document.getElementById('bubble-username').value.trim();
                if (score === 0) {
                    document.getElementById('bubble-leaderboard-msg').textContent = "Score 0 cannot be submitted!";
                    return;
                }
                if (!isUsernameClean(username)) {
                    document.getElementById('bubble-leaderboard-msg').textContent = "Please use a friendly nickname!";
                    return;
                }
                saveScore(username, score);
                showLeaderboard();
            };
        }
        function isUsernameClean(name) {
            const badWords = ["fuck", "shit", "bitch", "ass", "dick", "cunt", "piss", "cock", "fag", "nigger", "nigga", "retard", "whore", "slut"];
            const lower = name.toLowerCase();
            return !badWords.some(word => lower.includes(word));
        }
        function saveScore(name, score) {
            fetch('save-leaderboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, score })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showLeaderboard(data.leaderboard);
                    } else {
                        document.getElementById('bubble-leaderboard-msg').textContent = data.error || "Error saving score.";
                    }
                })
                .catch(() => {
                    document.getElementById('bubble-leaderboard-msg').textContent = "Network error.";
                });
        }

        function showLeaderboard(board = null) {
            if (!board) {
                fetch('save-leaderboard.php')
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data)) renderLeaderboard(data);
                        else if (data.leaderboard) renderLeaderboard(data.leaderboard);
                    });
            } else {
                renderLeaderboard(board);
            }
        }

        function renderLeaderboard(board, target = null) {
            board = board.slice(0, 100);
            let cols = [];
            for (let i = 0; i < board.length; i += 25) {
                cols.push(board.slice(i, i + 25));
            }
            let html = "<h3>Leaderboard</h3><div style='display:flex;gap:1.5rem;'>";
            cols.forEach((col, idx) => {
                html += `<div class="leaderboard-col"><ol start="${idx * 25 + 1}">`;
                col.forEach(entry => {
                    html += `<li><span style="color:#ffd700;font-weight:600;">${entry.name}</span> — <span style="color:#aee6e6;">${entry.score}</span></li>`;
                });
                html += "</ol></div>";
            });
            html += "</div>";
            if (target) target.innerHTML = html;
            else document.getElementById('bubble-leaderboard-list').innerHTML = html;
        }

        // Start game on load
        function showStartScreen() {
            document.getElementById('bubble-start-screen').style.display = "flex";
            canvas.style.display = "block";
            endScreen.style.display = "none";
            scoreEl.textContent = "Score: 0";
            updateHearts();
        }
        document.getElementById('bubble-play-btn').onclick = function() {
            document.getElementById('bubble-start-screen').style.display = "none";
            startGame();
        };

        // Override Play Again to show start screen
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'bubble-restart') {
                showStartScreen();
            }
        });

        // Initial state
        showStartScreen();
        updateHearts();
    </script>
</body>

</html>