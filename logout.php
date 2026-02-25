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
    <title>Logout Berhasil - SOP Digital System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <noscript><meta http-equiv="refresh" content="3;url=index.php"></noscript>

    <style>
        /* --- COPY CSS DARI TEMA KAMU --- */
        :root {
            --primary-glow: #3b82f6;
            --accent-orange: #f97316;
            --bg-main: #020617;
            --glass-bg: rgba(15, 23, 42, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --grid-color: rgba(255, 255, 255, 0.03);
        }

        [data-theme="light"] {
            --bg-main: #f1f5f9;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(0, 0, 0, 0.1);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --grid-color: rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: var(--bg-main) !important;
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin: 0;
            transition: background-color 0.5s ease, color 0.5s ease;
        }

        /* --- BACKGROUND --- */
        .ambient-light {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.15), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(249, 115, 22, 0.08), transparent 25%);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: moveOrb 20s infinite alternate;
        }
        .orb-1 { width: 400px; height: 400px; background: var(--primary-glow); top: -100px; left: -100px; }
        .orb-2 { width: 500px; height: 500px; background: var(--accent-orange); bottom: -150px; right: -150px; animation-delay: -5s; }

        @keyframes moveOrb {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 30px); }
        }

        .grid-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px),
            linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: -1;
            mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
            -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        }

        /* --- BOX LOGOUT CUSTOM --- */
        .login-container {
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        .logout-box {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3), 0 0 40px rgba(59, 130, 246, 0.1);
            max-width: 450px; 
            width: 100%;
            padding: 40px 30px;
            text-align: center;
            animation: cardEntrance 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
            transition: all 0.5s ease;
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .logout-box i.fa-check-circle {
            font-size: 60px;
            color: #10b981;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.4));
            animation: pulseIcon 2s infinite;
        }

        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .logout-box h2 {
            color: var(--text-main);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .logout-box p {
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 0;
        }
        
        .spinner {
            margin-top: 20px;
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-glow);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

    <div class="grid-overlay"></div>
    <div class="ambient-light">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="login-container">
        <div class="logout-box" id="logoutBox">
            <i class="fas fa-check-circle"></i>
            <h2>Anda berhasil logout!</h2>
            <p>Sesi Anda telah aman ditutup.<br>Mohon tunggu, dialihkan dalam <span id="countdown">3</span> detik...</p>
            <div class="spinner"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBox = document.getElementById('logoutBox');
            const countdownElement = document.getElementById('countdown');
            let timeLeft = 3;

            // --- FITUR HITUNG MUNDUR ---
            const timer = setInterval(function() {
                timeLeft--; // Kurangi angka 1
                
                if (timeLeft > 0) {
                    countdownElement.textContent = timeLeft; 
                } else {
                    clearInterval(timer); 
                }
            }, 1000);

            // --- ANIMASI MENGHILANG (FADE OUT & FLOAT UP) ---
            setTimeout(function() {
                logoutBox.style.opacity = '0';
                logoutBox.style.transform = 'translateY(-30px) scale(0.95)';
            }, 2500);

            // --- REDIRECT KE HALAMAN LOGIN ---
            setTimeout(function() {
                window.location.href = "index.php";
            }, 3000); 
        });
    </script>

</body>
</html>