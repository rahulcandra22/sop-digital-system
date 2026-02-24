<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

$sql = "SELECT c.*, COUNT(s.id) as jumlah_sop FROM categories c 
        LEFT JOIN sop s ON c.id = s.kategori_id 
        GROUP BY c.id ORDER BY c.nama_kategori ASC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategori SOP - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #020617; --sb: rgba(15, 23, 42, .97); --tb: rgba(15, 23, 42, .87); --cb: rgba(30, 41, 59, .75);
            --gb: rgba(255, 255, 255, .08); --tm: #f8fafc; --tmut: #94a3b8; --tsub: #cbd5e1;
            --lf: brightness(0) invert(1); --lbg: rgba(239, 68, 68, .18); --lc: #fca5a5; --lbor: rgba(239, 68, 68, .30);
            --sl: #94a3b8; --sa: rgba(59, 130, 246, .12); --togbg: rgba(30, 41, 59, .80); --togc: #94a3b8;
        }
        [data-theme="light"] {
            --bg: #f0f4f8; --sb: rgba(255, 255, 255, .98); --tb: rgba(255, 255, 255, .96); --cb: rgba(255, 255, 255, .95);
            --gb: rgba(0, 0, 0, .09); --tm: #0f172a; --tmut: #64748b; --tsub: #334155;
            --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626; --lbor: rgba(239, 68, 68, .18);
            --sl: #64748b; --sa: rgba(59, 130, 246, .08); --togbg: rgba(241, 245, 249, .95); --togc: #475569;
        }
        
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif !important; background-color: var(--bg) !important; color: var(--tm) !important; margin: 0; overflow-x: hidden; transition: background-color .35s, color .35s; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, .07), transparent 30%); pointer-events: none; }
        
        .sidebar { background: var(--sb) !important; border-right: 1px solid var(--gb) !important; backdrop-filter: blur(12px); }
        .sidebar-header { border-bottom: 1px solid var(--gb) !important; padding: 20px; }
        .sidebar-header h3 { color: var(--tm) !important; margin: 4px 0 2px; font-size: 16px; font-weight: 700; }
        .sidebar-header p { color: var(--tmut) !important; margin: 0; font-size: 12px; }
        .sidebar-logo { filter: var(--lf); max-width: 80px; }
        .sidebar-menu { list-style: none; margin: 0; padding: 12px 0; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--sl) !important; text-decoration: none; border-left: 3px solid transparent; font-size: 14px; font-weight: 500; transition: .25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--sa) !important; color: #3b82f6 !important; border-left-color: #3b82f6; }
        
        .main-content { background: transparent !important; }
        .topbar { background: var(--tb) !important; border-bottom: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 64px; }
        .topbar-left h2 { color: var(--tm) !important; font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        
        #theme-toggle-btn { all: unset; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; background: var(--togbg) !important; border: 1px solid var(--gb) !important; color: var(--togc) !important; display: flex !important; align-items: center; justify-content: center; font-size: 17px; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); flex-shrink: 0; transition: all .25s; }
        #theme-toggle-btn:hover { color: #3b82f6 !important; transform: scale(1.1); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-info strong { color: var(--tm) !important; font-size: 14px; display: block; }
        .user-info p { color: var(--tmut) !important; margin: 0; font-size: 11px; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: #fff !important; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .btn-logout { padding: 8px 18px; background: var(--lbg) !important; color: var(--lc) !important; border: 1px solid var(--lbor) !important; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; white-space: nowrap; display: flex; align-items: center; gap: 6px; }

        .content-wrapper { padding: 24px; }
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); }
        .card-header { padding: 18px 22px; border-bottom: 1px solid var(--gb); }
        .card-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 600; }
        .card-body { padding: 22px; }

        .cat-card-lg { background: var(--bg); border: 1px solid var(--gb); border-radius: 16px; padding: 30px; position: relative; overflow: hidden; transition: all .3s ease; }
        .cat-card-lg:hover { transform: translateY(-10px); box-shadow: 0 15px 30px rgba(0, 0, 0, .1); border-color: #3b82f6; }
        .cat-icon-circle { width: 60px; height: 60px; background: var(--sa); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; border: 1px solid rgba(59, 130, 246, .2); }
        .cat-card-lg h3 { color: var(--tm); margin-bottom: 10px; font-size: 20px; font-weight: 600; }
        .cat-card-lg p { color: var(--tsub); margin-bottom: 20px; line-height: 1.6; font-size: 14px; }
        .cat-btn-cta { background: var(--sa); color: #3b82f6; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: all .3s; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(59, 130, 246, .2); }
        .cat-btn-cta:hover { background: #3b82f6; color: #fff; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                <h3>SOP Digital</h3>
                <p>User Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php" class="active"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2><i class="fas fa-folder" style="color:#3b82f6"></i> Kategori SOP</h2>
                </div>
                <div class="topbar-right">
                    <button type="button" id="theme-toggle-btn" title="Ganti Tema"><i class="fas fa-moon" id="theme-icon"></i></button>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo getNamaLengkap(); ?></strong>
                            <p>User</p>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book-open"></i> Semua Kategori</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <?php while ($cat = mysqli_fetch_assoc($result)): ?>
                            <div class="cat-card-lg">
                                <div style="position: relative; z-index: 1;">
                                    <div class="cat-icon-circle">
                                        <i class="fas fa-folder-open" style="font-size: 26px; color: #3b82f6;"></i>
                                    </div>
                                    <h3><?php echo htmlspecialchars($cat['nama_kategori']); ?></h3>
                                    <p><?php echo htmlspecialchars($cat['deskripsi']); ?></p>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-size: 13px; font-weight: 500; color: var(--tmut);">
                                            <i class="fas fa-file-alt"></i> <?php echo $cat['jumlah_sop']; ?> SOP
                                        </span>
                                        <a href="browse_sop.php?kategori=<?php echo $cat['id']; ?>" class="cat-btn-cta">
                                            Lihat SOP <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('theme-toggle-btn'),
                icon = document.getElementById('theme-icon');
                
            function sync() {
                icon.className = document.documentElement.getAttribute('data-theme') === 'light' ? 'fas fa-sun' : 'fas fa-moon';
            }
            
            sync();
            
            if (btn) {
                btn.addEventListener('click', function() {
                    var light = document.documentElement.getAttribute('data-theme') === 'light';
                    if (light) {
                        document.documentElement.removeAttribute('data-theme');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.setAttribute('data-theme', 'light');
                        localStorage.setItem('theme', 'light');
                    }
                    sync();
                });
            }
        });
    </script>
</body>
</html>