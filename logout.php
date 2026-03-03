<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Berhasil — SOP Digital System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Fallback jika JS mati -->
    <noscript><meta http-equiv="refresh" content="3;url=login.php"></noscript>

    <style>
        :root {
            --primary-glow:  #3b82f6;
            --accent-orange: #f97316;
            --bg-main:       #020617;
            --glass-bg:      rgba(15, 23, 42, 0.7);
            --glass-border:  rgba(255, 255, 255, 0.1);
            --text-main:     #f8fafc;
            --text-muted:    #94a3b8;
            --grid-color:    rgba(255, 255, 255, 0.03);
        }
        [data-theme="light"] {
            --bg-main:      #f1f5f9;
            --glass-bg:     rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.1);
            --text-main:    #0f172a;
            --text-muted:   #64748b;
            --grid-color:   rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            overflow: hidden;
            transition: background-color 0.5s ease;
        }

        /* --- BACKGROUND --- */
        .ambient-light {
            position: fixed;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(circle at 15% 50%, rgba(59,130,246,0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(249,115,22,0.08), transparent 25%);
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: moveOrb 20s infinite alternate;
        }
        .orb-1 { width:400px; height:400px; background:var(--primary-glow); top:-100px; left:-100px; }
        .orb-2 { width:500px; height:500px; background:var(--accent-orange); bottom:-150px; right:-150px; animation-delay:-5s; }
        @keyframes moveOrb {
            0%   { transform: translate(0,0); }
            100% { transform: translate(50px,30px); }
        }

        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* --- CARD --- */
        .logout-box {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3), 0 0 40px rgba(59,130,246,0.1);
            max-width: 420px;
            width: 90%;
            padding: 48px 36px;
            text-align: center;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        @keyframes cardEntrance {
            from { opacity:0; transform:scale(0.95) translateY(20px); }
            to   { opacity:1; transform:scale(1) translateY(0); }
        }

        /* --- LOGO --- */
        .logout-logo {
            height: 40px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
            margin-bottom: 24px;
            filter: drop-shadow(0 0 8px rgba(59,130,246,0.25));
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        [data-theme="light"] .logout-logo {
            filter: drop-shadow(0 0 6px rgba(29,78,216,0.15));
        }

        /* --- ICON --- */
        .logout-icon {
            font-size: 56px;
            color: #10b981;
            margin-bottom: 20px;
            display: block;
            filter: drop-shadow(0 0 12px rgba(16,185,129,0.5));
            animation: pulseIcon 2s ease-in-out infinite;
        }
        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.06); }
        }

        .logout-box h2 {
            color: var(--text-main);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }
        .logout-box p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 24px;
        }
        .logout-box p span {
            color: var(--primary-glow);
            font-weight: 600;
        }

        /* --- PROGRESS BAR --- */
        .progress-wrap {
            background: rgba(59,130,246,0.1);
            border-radius: 100px;
            height: 4px;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .progress-bar {
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 100px;
            animation: shrink 3s linear forwards;
            transform-origin: left;
        }
        @keyframes shrink {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        /* --- SPINNER --- */
        .spinner {
            display: inline-block;
            width: 24px; height: 24px;
            border: 3px solid rgba(59,130,246,0.2);
            border-radius: 50%;
            border-top-color: var(--primary-glow);
            animation: spin 0.9s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* --- BTN MANUAL --- */
        .btn-manual {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 10px 22px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59,130,246,0.3);
        }
        .btn-manual:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139,92,246,0.4);
        }
    </style>
</head>
<body>

    <!-- Sinkron tema SEBELUM render agar tidak flicker -->
    <script>
        const t = localStorage.getItem('sni-theme');
        if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
    </script>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="logout-box" id="logoutBox">

        <!-- Logo -->
        <img src="assets/images/logo.png"
             alt="Sinergi Nusantara Integrasi"
             class="logout-logo"
             onerror="this.style.display='none'">

        <!-- Icon centang -->
        <i class="fas fa-circle-check logout-icon"></i>

        <h2>Logout Berhasil!</h2>
        <p>
            Sesi Anda telah aman ditutup.<br>
            Dialihkan ke halaman login dalam <span id="countdown">3</span> detik...
        </p>

        <!-- Progress bar hitung mundur -->
        <div class="progress-wrap">
            <div class="progress-bar"></div>
        </div>

        <div class="spinner"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const box      = document.getElementById('logoutBox');
            const counter  = document.getElementById('countdown');
            let timeLeft   = 3;

            // Hitung mundur teks
            const timer = setInterval(function () {
                timeLeft--;
                if (timeLeft > 0) {
                    counter.textContent = timeLeft;
                } else {
                    clearInterval(timer);
                }
            }, 1000);

            // Fade out card
            setTimeout(function () {
                box.style.opacity   = '0';
                box.style.transform = 'translateY(-30px) scale(0.95)';
            }, 2500);

            setTimeout(function () {
                window.location.href = 'login.php';
            }, 3000);
        });
    </script>

</body>
</html>