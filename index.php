<?php
// Redirect if already logged in
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP Digital System — Sinergi Nusantara Integrasi</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --navy-deep:    #020d1f;
            --navy-mid:     #061530;
            --navy-card:    rgba(6, 21, 48, 0.85);
            --blue-bright:  #1d4ed8;
            --blue-glow:    #3b82f6;
            --blue-soft:    #60a5fa;
            --gold:         #f59e0b;
            --gold-light:   #fcd34d;
            --text-white:   #f0f6ff;
            --text-muted:   #7a9cc0;
            --text-dim:     #4a6a8a;
            --border-glow:  rgba(59, 130, 246, 0.25);
            --glass:        rgba(6, 20, 50, 0.6);
        }
        [data-theme="light"] {
            --navy-deep:    #f0f5ff;
            --navy-mid:     #e0eaff;
            --navy-card:    rgba(255,255,255,0.9);
            --blue-bright:  #1d4ed8;
            --blue-glow:    #3b82f6;
            --blue-soft:    #2563eb;
            --text-white:   #0a1628;
            --text-muted:   #4a6a8a;
            --text-dim:     #7a9cc0;
            --border-glow:  rgba(59,130,246,0.2);
            --glass:        rgba(255,255,255,0.7);
        }

        /* ===== RESET & BASE ===== */
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--navy-deep);
            color: var(--text-white);
            overflow-x: hidden;
            min-height: 100vh;
            transition: background 0.4s ease, color 0.4s ease;
            cursor: default;
        }

        /* ===== ANIMATED BACKGROUND ===== */
        .bg-canvas {
            position: fixed;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }
        .bg-canvas::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 50% at 20% 40%, rgba(29,78,216,0.18) 0%, transparent 60%),
                radial-gradient(ellipse 50% 60% at 80% 70%, rgba(245,158,11,0.06) 0%, transparent 50%),
                radial-gradient(ellipse 40% 30% at 60% 10%, rgba(59,130,246,0.1) 0%, transparent 50%);
        }
        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(59,130,246,0.12) 1px, transparent 1px);
            background-size: 40px 40px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 30%, transparent 100%);
            -webkit-mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 30%, transparent 100%);
        }
        [data-theme="light"] .dot-grid {
            background-image: radial-gradient(circle, rgba(29,78,216,0.1) 1px, transparent 1px);
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            animation: driftOrb 18s ease-in-out infinite alternate;
        }
        .orb-a { width: 600px; height: 600px; background: rgba(29,78,216,0.12); top:-200px; left:-150px; }
        .orb-b { width: 500px; height: 500px; background: rgba(245,158,11,0.06); bottom:-150px; right:-100px; animation-delay:-6s; }
        .orb-c { width: 300px; height: 300px; background: rgba(59,130,246,0.1); top:40%; right:15%; animation-delay:-12s; }

        @keyframes driftOrb {
            0%   { transform: translate(0,0) scale(1); }
            100% { transform: translate(40px, 30px) scale(1.08); }
        }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 0 5%;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(2, 13, 31, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-glow);
            transition: background 0.4s ease;
        }
        [data-theme="light"] .navbar {
            background: rgba(240,245,255,0.8);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .nav-logo-img {
            height: 38px;
            width: auto;
            max-width: 200px;
            object-fit: contain;
            filter: brightness(1) drop-shadow(0 0 8px rgba(59,130,246,0.2));
            transition: filter 0.3s ease;
            flex-shrink: 0;
        }
        [data-theme="light"] .nav-logo-img {
            filter: brightness(0.9) drop-shadow(0 0 6px rgba(29,78,216,0.15));
        }
        .nav-brand:hover .nav-logo-img {
            filter: brightness(1.1) drop-shadow(0 0 14px rgba(59,130,246,0.45));
        }
        .nav-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }
        .nav-brand-text .name {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--text-white);
            letter-spacing: 0.5px;
        }
        .nav-brand-text .tagline {
            font-size: 10px;
            color: var(--blue-soft);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 400;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .nav-links a:hover, .nav-links a.active {
            color: var(--text-white);
            background: rgba(59,130,246,0.1);
        }
        .nav-links a.active { color: var(--blue-soft); }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--glass);
            border: 1px solid var(--border-glow);
            border-radius: 10px;
            padding: 8px 14px;
            transition: all 0.3s ease;
        }
        .search-bar input {
            background: none;
            border: none;
            outline: none;
            color: var(--text-white);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            width: 160px;
        }
        .search-bar input::placeholder { color: var(--text-dim); }
        .search-bar i { color: var(--text-dim); font-size: 13px; }
        .search-bar:focus-within {
            border-color: var(--blue-glow);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* Theme Toggle */
        .theme-btn {
            width: 40px; height: 40px;
            border-radius: 10px;
            background: var(--glass);
            border: 1px solid var(--border-glow);
            color: var(--text-muted);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        .theme-btn:hover {
            color: var(--blue-soft);
            border-color: var(--blue-glow);
            box-shadow: 0 0 15px rgba(59,130,246,0.2);
        }

        .btn-login-nav {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 20px;
            background: linear-gradient(90deg, #1d4ed8, #3b82f6);
            color: white !important;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59,130,246,0.3);
        }
        .btn-login-nav:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.45);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 100px 5% 60px;
        }

        .hero-inner {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-content { max-width: 580px; }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(29,78,216,0.15);
            border: 1px solid rgba(59,130,246,0.3);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 500;
            color: var(--blue-soft);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 28px;
            animation: fadeUp 0.6s ease 0.1s both;
        }
        .hero-badge .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--blue-glow);
            animation: blink 1.5s ease infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hero-headline {
            font-family: 'Syne', sans-serif;
            font-size: clamp(36px, 4.5vw, 60px);
            font-weight: 800;
            line-height: 1.05;
            letter-spacing: -1.5px;
            margin-bottom: 24px;
            animation: fadeUp 0.6s ease 0.2s both;
        }
        .hero-headline .line-1 { color: var(--text-white); display: block; }
        .hero-headline .line-2 {
            display: block;
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 50%, #f59e0b 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 16px;
            line-height: 1.75;
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 460px;
            font-weight: 300;
            animation: fadeUp 0.6s ease 0.3s both;
        }
        .hero-desc strong { color: var(--text-white); font-weight: 500; }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            animation: fadeUp 0.6s ease 0.4s both;
        }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 28px;
            background: linear-gradient(90deg, #1d4ed8 0%, #3b82f6 100%);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(29,78,216,0.35);
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            letter-spacing: 0.3px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(59,130,246,0.5);
        }
        .btn-primary i { font-size: 14px; }

        .btn-secondary {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 14px 28px;
            background: transparent;
            color: var(--text-white);
            border: 1px solid var(--border-glow);
            border-radius: 12px;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            letter-spacing: 0.3px;
        }
        .btn-secondary:hover {
            background: rgba(59,130,246,0.08);
            border-color: var(--blue-glow);
            color: var(--blue-soft);
        }

        /* STATS */
        .hero-stats {
            display: flex;
            gap: 32px;
            margin-top: 48px;
            padding-top: 32px;
            border-top: 1px solid var(--border-glow);
            animation: fadeUp 0.6s ease 0.5s both;
        }
        .stat { display: flex; flex-direction: column; }
        .stat-num {
            font-family: 'Syne', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: var(--text-white);
            letter-spacing: -1px;
        }
        .stat-num span { color: var(--blue-soft); }
        .stat-label {
            font-size: 11px;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 3px;
            font-weight: 400;
        }
        .stat-divider {
            width: 1px;
            background: var(--border-glow);
            align-self: stretch;
        }

        /* ===== HERO VISUAL (ISOMETRIC ILLUSTRATION) ===== */
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            animation: fadeLeft 0.8s ease 0.3s both;
        }

        @keyframes fadeLeft {
            from { opacity: 0; transform: translateX(40px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .visual-glow {
            position: absolute;
            width: 70%;
            height: 70%;
            background: radial-gradient(circle, rgba(29,78,216,0.25) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
            pointer-events: none;
        }

        .isometric-svg {
            width: 100%;
            max-width: 560px;
            height: auto;
            filter: drop-shadow(0 20px 60px rgba(29,78,216,0.3));
            animation: floatVis 7s ease-in-out infinite;
            position: relative;
            z-index: 2;
        }

        @keyframes floatVis {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        /* Floating badges */
        .float-badge {
            position: absolute;
            background: var(--navy-card);
            border: 1px solid var(--border-glow);
            border-radius: 14px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            animation: floatBadge 5s ease-in-out infinite;
            z-index: 10;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-white);
            white-space: nowrap;
        }
        .float-badge i { font-size: 18px; }
        .float-badge-1 { top: 8%; right: 0%; animation-delay: 0s; }
        .float-badge-2 { bottom: 15%; left: -3%; animation-delay: -2s; }
        .float-badge-3 { top: 50%; right: -2%; animation-delay: -4s; font-size: 12px; }

        .float-badge .icon-wrap {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }
        .icon-blue { background: rgba(59,130,246,0.2); color: #60a5fa; }
        .icon-green { background: rgba(16,185,129,0.2); color: #10b981; }
        .icon-gold { background: rgba(245,158,11,0.2); color: #f59e0b; }

        .badge-label { font-size: 10px; color: var(--text-dim); display: block; }
        .badge-val { font-size: 14px; font-weight: 600; color: var(--text-white); display: block; line-height: 1.2; }

        @keyframes floatBadge {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* ===== FEATURES STRIP ===== */
        .features-strip {
            position: relative;
            z-index: 10;
            padding: 0 5% 80px;
        }

        .features-label {
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--text-dim);
            margin-bottom: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .feat-card {
            background: var(--glass);
            border: 1px solid var(--border-glow);
            border-radius: 16px;
            padding: 24px 20px;
            transition: all 0.3s ease;
            cursor: default;
            backdrop-filter: blur(16px);
            position: relative;
            overflow: hidden;
        }
        .feat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59,130,246,0.4), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feat-card:hover { 
            border-color: rgba(59,130,246,0.4);
            transform: translateY(-4px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.2);
        }
        .feat-card:hover::before { opacity: 1; }

        .feat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            margin-bottom: 14px;
        }
        .feat-icon-1 { background: rgba(59,130,246,0.15); color: #60a5fa; }
        .feat-icon-2 { background: rgba(16,185,129,0.15); color: #10b981; }
        .feat-icon-3 { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .feat-icon-4 { background: rgba(139,92,246,0.15); color: #a78bfa; }

        .feat-title {
            font-family: 'Syne', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: var(--text-white);
            margin-bottom: 8px;
        }
        .feat-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
            font-weight: 300;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: relative;
            z-index: 10;
            text-align: center;
            padding: 24px 5%;
            border-top: 1px solid var(--border-glow);
            font-size: 12px;
            color: var(--text-dim);
        }
        .footer span { color: var(--text-muted); }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .hero-inner { grid-template-columns: 1fr; gap: 40px; }
            .hero-visual { order: -1; }
            .isometric-svg { max-width: 420px; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .nav-links { display: none; }
            .search-bar { display: none; }
        }
        @media (max-width: 640px) {
            .hero-headline { font-size: 34px; }
            .hero-stats { gap: 20px; }
            .features-grid { grid-template-columns: 1fr; }
            .float-badge-1, .float-badge-2, .float-badge-3 { display: none; }
            .hero-actions { flex-direction: column; align-items: flex-start; }
            .btn-primary, .btn-secondary { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

    <!-- BG -->
    <div class="bg-canvas">
        <div class="dot-grid"></div>
        <div class="orb orb-a"></div>
        <div class="orb orb-b"></div>
        <div class="orb orb-c"></div>
    </div>

    <!-- NAVBAR -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <img src="assets/images/logo.png" alt="Sinergi Nusantara Integrasi" class="nav-logo-img"
                 onerror="this.src='logo.png'">
        </a>

        <ul class="nav-links">
            <li><a href="#" class="active"><i class="fas fa-house"></i> Home</a></li>
            <li><a href="#features"><i class="fas fa-th-large"></i> Fitur</a></li>
            <li><a href="#about"><i class="fas fa-circle-info"></i> Tentang</a></li>
        </ul>

        <div class="nav-right">
            <div class="search-bar">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" placeholder="Search...">
            </div>
            <button class="theme-btn" id="themeToggle" title="Ubah Tema">
                <i class="fas fa-moon"></i>
            </button>
            <a href="login.php" class="btn-login-nav">
                <i class="fas fa-arrow-right-to-bracket"></i> Masuk
            </a>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="home">
        <div class="hero-inner">

            <!-- LEFT: TEXT -->
            <div class="hero-content">
                <div class="hero-badge">
                    <span class="dot"></span>
                    Sistem Dokumen Digital dan Terintegrasi
                </div>

                <h1 class="hero-headline">
                    <span class="line-1">SINERGI NUSANTARA</span>
                    <span class="line-2">INTEGRASI</span>
                </h1>

                <p class="hero-desc">
                    <strong>PT. Sinergi Nusantara Integrasi (SINERGI)</strong> adalah perusahaan dengan solusi teknologi kelas dunia yang memberikan berbagai solusi inovatif berdasarkan teknologi yang terintegrasi berdasarkan pada produk perangkat lunak dengan kinerja terbaik bagi dunia usaha dalam memecahkan masalah lebih efektif dan efisien.
                </p>

                <div class="hero-actions">
                    <a href="login.php" class="btn-primary">
                        <i class="fas fa-rocket"></i> Masuk ke Sistem
                    </a>
                    <a href="#features" class="btn-secondary">
                        <i class="fas fa-circle-play"></i> Pelajari Lebih
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-num">500<span>+</span></span>
                        <span class="stat-label">Dokumen SOP</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat">
                        <span class="stat-num">99<span>%</span></span>
                        <span class="stat-label">Uptime</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat">
                        <span class="stat-num">12<span>+</span></span>
                        <span class="stat-label">Divisi</span>
                    </div>
                </div>
            </div>

            <!-- RIGHT: ILLUSTRATION -->
            <div class="hero-visual" id="about">
                <div class="visual-glow"></div>

                <!-- Floating badges -->
                <div class="float-badge float-badge-1">
                    <div class="icon-wrap icon-green"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <span class="badge-label">Status</span>
                        <span class="badge-val">SOP Disetujui</span>
                    </div>
                </div>

                <div class="float-badge float-badge-2">
                    <div class="icon-wrap icon-blue"><i class="fas fa-file-lines"></i></div>
                    <div>
                        <span class="badge-label">Dokumen Aktif</span>
                        <span class="badge-val">124 SOP</span>
                    </div>
                </div>

                <div class="float-badge float-badge-3">
                    <div class="icon-wrap icon-gold"><i class="fas fa-star"></i></div>
                    <div>
                        <span class="badge-label">Versi Terbaru</span>
                        <span class="badge-val">v3.2.1</span>
                    </div>
                </div>

                <!-- ISOMETRIC SVG ILLUSTRATION -->
                <svg class="isometric-svg" viewBox="0 0 600 520" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="ig-blue" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.9"/>
                            <stop offset="100%" stop-color="#3b82f6" stop-opacity="0.9"/>
                        </linearGradient>
                        <linearGradient id="ig-gold" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#f59e0b"/>
                            <stop offset="100%" stop-color="#fcd34d"/>
                        </linearGradient>
                        <linearGradient id="ig-dark" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#0f2044" stop-opacity="0.95"/>
                            <stop offset="100%" stop-color="#061530" stop-opacity="1"/>
                        </linearGradient>
                        <linearGradient id="ig-screen" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#0d2137"/>
                            <stop offset="100%" stop-color="#061229"/>
                        </linearGradient>
                        <linearGradient id="ig-navy1" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#1a3a6e"/>
                            <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.6"/>
                        </linearGradient>
                        <filter id="glow-f">
                            <feGaussianBlur stdDeviation="3" result="blur"/>
                            <feComposite in="SourceGraphic" in2="blur" operator="over"/>
                        </filter>
                        <filter id="soft-shadow">
                            <feDropShadow dx="0" dy="8" stdDeviation="12" flood-color="rgba(0,0,0,0.5)"/>
                        </filter>
                    </defs>

                    <g filter="url(#soft-shadow)">
                        <animateTransform attributeName="transform" type="translate" values="0,0; 0,-10; 0,0" dur="7s" repeatCount="indefinite"/>

                        <!-- === PLATFORM BASE (ISOMETRIC FLOOR) === -->
                        <ellipse cx="300" cy="460" rx="220" ry="55" fill="rgba(29,78,216,0.08)" />

                        <!-- Floor tiles -->
                        <polygon points="80,400 300,460 520,400 300,340" fill="rgba(13,33,55,0.9)" stroke="rgba(59,130,246,0.2)" stroke-width="1"/>
                        <!-- Tile grid lines -->
                        <line x1="190" y1="370" x2="190" y2="430" stroke="rgba(59,130,246,0.12)" stroke-width="1"/>
                        <line x1="300" y1="340" x2="300" y2="460" stroke="rgba(59,130,246,0.12)" stroke-width="1"/>
                        <line x1="410" y1="370" x2="410" y2="430" stroke="rgba(59,130,246,0.12)" stroke-width="1"/>
                        <line x1="80" y1="400" x2="520" y2="400" stroke="rgba(59,130,246,0.1)" stroke-width="1"/>

                        <!-- === MAIN LAPTOP === -->
                        <!-- Screen back -->
                        <polygon points="150,180 300,120 450,180 450,320 300,380 150,320" fill="url(#ig-dark)" stroke="url(#ig-blue)" stroke-width="1.5"/>
                        <!-- Screen face -->
                        <polygon points="165,190 300,133 435,190 435,312 300,370 165,312" fill="url(#ig-screen)" />
                        <!-- Bezel top bar -->
                        <rect x="175" y="145" width="250" height="6" rx="3" fill="rgba(29,78,216,0.3)" transform="skewX(-10)"/>

                        <!-- SCREEN CONTENT -->
                        <!-- Header bar -->
                        <rect x="180" y="155" width="240" height="18" rx="3" fill="rgba(29,78,216,0.5)" opacity="0.8" transform="skewX(-10)"/>
                        <circle cx="195" cy="164" r="4" fill="#ef4444" opacity="0.7"/>
                        <circle cx="207" cy="164" r="4" fill="#f59e0b" opacity="0.7"/>
                        <circle cx="219" cy="164" r="4" fill="#10b981" opacity="0.7"/>
                        <!-- Navigation text lines -->
                        <rect x="235" y="160" width="30" height="5" rx="2" fill="rgba(255,255,255,0.25)" transform="skewX(-10)"/>
                        <rect x="270" y="160" width="25" height="5" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>
                        <rect x="300" y="160" width="35" height="5" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>

                        <!-- Sidebar -->
                        <rect x="180" y="178" width="45" height="115" rx="2" fill="rgba(13,28,60,0.8)" transform="skewX(-10)"/>
                        <rect x="185" y="185" width="32" height="5" rx="2" fill="rgba(59,130,246,0.6)" transform="skewX(-10)"/>
                        <rect x="185" y="195" width="28" height="5" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="185" y="205" width="30" height="5" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="185" y="215" width="26" height="5" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="185" y="225" width="32" height="5" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="185" y="235" width="24" height="5" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>

                        <!-- Main content area -->
                        <rect x="230" y="178" width="185" height="28" rx="3" fill="rgba(29,78,216,0.2)" transform="skewX(-10)"/>
                        <rect x="235" y="183" width="80" height="6" rx="3" fill="rgba(255,255,255,0.3)" transform="skewX(-10)"/>
                        <rect x="235" y="193" width="55" height="4" rx="2" fill="rgba(255,255,255,0.1)" transform="skewX(-10)"/>

                        <!-- Table rows -->
                        <rect x="230" y="212" width="185" height="14" rx="2" fill="rgba(255,255,255,0.05)" transform="skewX(-10)"/>
                        <rect x="230" y="212" width="185" height="14" rx="2" fill="rgba(59,130,246,0.12)" transform="skewX(-10)"/>
                        <rect x="235" y="216" width="60" height="4" rx="2" fill="rgba(255,255,255,0.3)" transform="skewX(-10)"/>
                        <rect x="300" y="216" width="40" height="4" rx="2" fill="rgba(16,185,129,0.6)" transform="skewX(-10)"/>
                        <rect x="345" y="216" width="30" height="4" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>
                        <rect x="380" y="216" width="25" height="4" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>

                        <rect x="230" y="230" width="185" height="14" rx="2" fill="rgba(255,255,255,0.03)" transform="skewX(-10)"/>
                        <rect x="235" y="234" width="55" height="4" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>
                        <rect x="300" y="234" width="40" height="4" rx="2" fill="rgba(245,158,11,0.5)" transform="skewX(-10)"/>
                        <rect x="345" y="234" width="30" height="4" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="380" y="234" width="25" height="4" rx="2" fill="rgba(255,255,255,0.1)" transform="skewX(-10)"/>

                        <rect x="230" y="248" width="185" height="14" rx="2" fill="rgba(59,130,246,0.06)" transform="skewX(-10)"/>
                        <rect x="235" y="252" width="65" height="4" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>
                        <rect x="300" y="252" width="40" height="4" rx="2" fill="rgba(16,185,129,0.5)" transform="skewX(-10)"/>
                        <rect x="345" y="252" width="30" height="4" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="380" y="252" width="25" height="4" rx="2" fill="rgba(255,255,255,0.1)" transform="skewX(-10)"/>

                        <rect x="230" y="266" width="185" height="14" rx="2" fill="rgba(255,255,255,0.03)" transform="skewX(-10)"/>
                        <rect x="235" y="270" width="48" height="4" rx="2" fill="rgba(255,255,255,0.2)" transform="skewX(-10)"/>
                        <rect x="300" y="270" width="40" height="4" rx="2" fill="rgba(239,68,68,0.5)" transform="skewX(-10)"/>
                        <rect x="345" y="270" width="30" height="4" rx="2" fill="rgba(255,255,255,0.15)" transform="skewX(-10)"/>
                        <rect x="380" y="270" width="25" height="4" rx="2" fill="rgba(255,255,255,0.1)" transform="skewX(-10)"/>

                        <!-- Progress bar bottom -->
                        <rect x="230" y="288" width="185" height="4" rx="2" fill="rgba(255,255,255,0.05)" transform="skewX(-10)"/>
                        <rect x="230" y="288" width="120" height="4" rx="2" fill="url(#ig-blue)" transform="skewX(-10)">
                            <animate attributeName="width" values="80;120;100;120" dur="4s" repeatCount="indefinite"/>
                        </rect>

                        <!-- KEYBOARD -->
                        <polygon points="155,325 300,385 445,325 445,340 300,400 155,340" fill="rgba(20,40,80,0.9)" stroke="rgba(59,130,246,0.3)" stroke-width="1"/>
                        <!-- Key rows -->
                        <g opacity="0.4">
                            <rect x="170" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="183" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="196" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="209" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="222" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="235" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                            <rect x="248" y="328" width="8" height="5" rx="1" fill="#60a5fa" transform="skewX(-18)"/>
                        </g>

                        <!-- === FLOATING DOCUMENT CARDS === -->
                        <!-- Card 1 - top right area -->
                        <g transform="translate(440, 200)">
                            <animateTransform attributeName="transform" type="translate" values="440,200; 443,195; 440,200" dur="5s" repeatCount="indefinite"/>
                            <rect x="0" y="0" width="90" height="70" rx="8" fill="rgba(13,26,50,0.95)" stroke="rgba(59,130,246,0.4)" stroke-width="1"/>
                            <rect x="8" y="10" width="50" height="5" rx="2" fill="#60a5fa" opacity="0.8"/>
                            <rect x="8" y="22" width="74" height="3" rx="2" fill="rgba(255,255,255,0.15)"/>
                            <rect x="8" y="30" width="60" height="3" rx="2" fill="rgba(255,255,255,0.1)"/>
                            <rect x="8" y="38" width="68" height="3" rx="2" fill="rgba(255,255,255,0.1)"/>
                            <rect x="8" y="50" width="30" height="10" rx="5" fill="rgba(16,185,129,0.3)" stroke="rgba(16,185,129,0.5)" stroke-width="0.5"/>
                            <text x="23" y="58" font-size="5" fill="#10b981" font-family="sans-serif" text-anchor="middle">APPROVED</text>
                        </g>

                        <!-- Card 2 - left -->
                        <g transform="translate(60, 250)">
                            <animateTransform attributeName="transform" type="translate" values="60,250; 57,256; 60,250" dur="6s" repeatCount="indefinite"/>
                            <rect x="0" y="0" width="85" height="65" rx="8" fill="rgba(13,26,50,0.95)" stroke="rgba(245,158,11,0.35)" stroke-width="1"/>
                            <rect x="8" y="10" width="40" height="5" rx="2" fill="#f59e0b" opacity="0.8"/>
                            <rect x="8" y="22" width="69" height="3" rx="2" fill="rgba(255,255,255,0.15)"/>
                            <rect x="8" y="30" width="55" height="3" rx="2" fill="rgba(255,255,255,0.1)"/>
                            <rect x="8" y="38" width="60" height="3" rx="2" fill="rgba(255,255,255,0.1)"/>
                            <rect x="8" y="48" width="28" height="10" rx="5" fill="rgba(245,158,11,0.2)" stroke="rgba(245,158,11,0.5)" stroke-width="0.5"/>
                            <text x="22" y="56" font-size="5" fill="#f59e0b" font-family="sans-serif" text-anchor="middle">REVIEW</text>
                        </g>

                        <!-- Server/DB boxes right bottom -->
                        <g transform="translate(465, 340)">
                            <rect x="0" y="0" width="55" height="75" rx="5" fill="rgba(10,20,45,0.95)" stroke="rgba(59,130,246,0.3)" stroke-width="1"/>
                            <rect x="5" y="8" width="45" height="10" rx="2" fill="rgba(29,78,216,0.5)"/>
                            <rect x="5" y="22" width="45" height="10" rx="2" fill="rgba(29,78,216,0.35)"/>
                            <rect x="5" y="36" width="45" height="10" rx="2" fill="rgba(29,78,216,0.25)"/>
                            <circle cx="44" cy="13" r="2.5" fill="#10b981">
                                <animate attributeName="opacity" values="1;0.3;1" dur="2s" repeatCount="indefinite"/>
                            </circle>
                            <circle cx="44" cy="27" r="2.5" fill="#10b981">
                                <animate attributeName="opacity" values="1;0.3;1" dur="2.5s" repeatCount="indefinite"/>
                            </circle>
                            <circle cx="44" cy="41" r="2.5" fill="#60a5fa">
                                <animate attributeName="opacity" values="0.3;1;0.3" dur="3s" repeatCount="indefinite"/>
                            </circle>
                            <rect x="5" y="55" width="45" height="12" rx="3" fill="rgba(29,78,216,0.2)"/>
                            <text x="27" y="64" font-size="5" fill="#60a5fa" font-family="sans-serif" text-anchor="middle">DATABASE</text>
                        </g>

                        <!-- Connection lines -->
                        <line x1="445" y1="335" x2="450" y2="355" stroke="rgba(59,130,246,0.3)" stroke-width="1" stroke-dasharray="3,3">
                            <animate attributeName="stroke-dashoffset" values="0;-12" dur="1s" repeatCount="indefinite"/>
                        </line>

                        <!-- Floating particles -->
                        <circle cx="500" cy="160" r="3" fill="#3b82f6" opacity="0.7">
                            <animate attributeName="cy" values="160;145;160" dur="3s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0;0.8;0" dur="3s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="100" cy="200" r="2.5" fill="#f59e0b" opacity="0.6">
                            <animate attributeName="cy" values="200;185;200" dur="4s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0;0.7;0" dur="4s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="380" cy="100" r="4" fill="#60a5fa" opacity="0.5">
                            <animate attributeName="cx" values="380;390;380" dur="5s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0.2;0.7;0.2" dur="5s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="150" cy="140" r="2" fill="#a78bfa" opacity="0.6">
                            <animate attributeName="cy" values="140;128;140" dur="3.5s" repeatCount="indefinite"/>
                            <animate attributeName="opacity" values="0;0.8;0" dur="3.5s" repeatCount="indefinite"/>
                        </circle>
                    </g>
                </svg>
            </div>
        </div>
    </section>

    <!-- FEATURES -->
    <section class="features-strip" id="features">
        <p class="features-label">Fitur Unggulan Platform</p>
        <div class="features-grid">
            <div class="feat-card">
                <div class="feat-icon feat-icon-1"><i class="fas fa-file-shield"></i></div>
                <div class="feat-title">Dokumentasi SOP Digital</div>
                <p class="feat-desc">Kelola seluruh dokumen SOP secara digital dengan sistem versioning dan approval workflow yang terstruktur.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon feat-icon-2"><i class="fas fa-arrows-rotate"></i></div>
                <div class="feat-title">Approval Workflow</div>
                <p class="feat-desc">Proses persetujuan dokumen multi-level yang cepat, transparan, dan dapat dilacak secara real-time.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon feat-icon-3"><i class="fas fa-chart-line"></i></div>
                <div class="feat-title">Monitoring & Analitik</div>
                <p class="feat-desc">Dashboard analitik komprehensif untuk memantau kepatuhan dan efektivitas implementasi SOP.</p>
            </div>
            <div class="feat-card">
                <div class="feat-icon feat-icon-4"><i class="fas fa-users-gear"></i></div>
                <div class="feat-title">Manajemen Pengguna</div>
                <p class="feat-desc">Kontrol akses berbasis peran (RBAC) untuk memastikan keamanan dan privasi dokumen perusahaan.</p>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <span>PT. Sinergi Nusantara Integrasi</span> &mdash; SOP Digital System</p>
        <p style="margin-top:6px; font-size:11px;">Developed by <span>Rahul Candra</span></p>
    </footer>

    <script>
        // Theme toggle
        const btn = document.getElementById('themeToggle');
        const icon = btn.querySelector('i');
        const html = document.documentElement;

        const saved = localStorage.getItem('sni-theme');
        if (saved === 'light') {
            html.setAttribute('data-theme', 'light');
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        btn.addEventListener('click', () => {
            const isLight = html.getAttribute('data-theme') === 'light';
            if (isLight) {
                html.removeAttribute('data-theme');
                icon.classList.replace('fa-sun', 'fa-moon');
                localStorage.setItem('sni-theme', 'dark');
            } else {
                html.setAttribute('data-theme', 'light');
                icon.classList.replace('fa-moon', 'fa-sun');
                localStorage.setItem('sni-theme', 'light');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const target = document.querySelector(a.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });

        // Intersection observer for feat cards
        const cards = document.querySelectorAll('.feat-card');
        const obs = new IntersectionObserver(entries => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.15 });

        cards.forEach((card, i) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `opacity 0.5s ease ${i * 0.1}s, transform 0.5s ease ${i * 0.1}s, border-color 0.3s, box-shadow 0.3s`;
            obs.observe(card);
        });
    </script>
</body>
</html>