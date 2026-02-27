<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

if (!isset($_GET['id'])) {
    header('Location: browse_sop.php');
    exit();
}

$id = intval($_GET['id']);
$sql = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s 
        LEFT JOIN categories c ON s.kategori_id = c.id 
        LEFT JOIN users u ON s.created_by = u.id 
        WHERE s.id = $id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    header('Location: browse_sop.php');
    exit();
}

$sop = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sop['judul']); ?> - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* =========================================================
            VARIABLE TEMA (Sama persis dengan Dashboard)
        ========================================================= */
        :root {
            --bg: #020617; --sb: rgba(15, 23, 42, .97); --tb: rgba(15, 23, 42, .87); --cb: rgba(30, 41, 59, .75);
            --gb: rgba(255, 255, 255, .08); --tm: #f8fafc; --tmut: #94a3b8; --tsub: #cbd5e1;
            --thbg: rgba(0, 0, 0, .35); --trodd: rgba(15, 23, 42, .55); --treven: rgba(15, 23, 42, .35);
            --trhov: rgba(59, 130, 246, .09); --tbor: rgba(255, 255, 255, .06); --lf: brightness(0) invert(1);
            --lbg: rgba(239, 68, 68, .18); --lc: #fca5a5; --lbor: rgba(239, 68, 68, .30);
            --sl: #94a3b8; --sa: rgba(59, 130, 246, .12); --togbg: rgba(30, 41, 59, .80); --togc: #94a3b8;
        }
        
        [data-theme="light"] {
            --bg: #f0f4f8; --sb: rgba(255, 255, 255, .98); --tb: rgba(255, 255, 255, .96); --cb: rgba(255, 255, 255, .95);
            --gb: rgba(0, 0, 0, .09); --tm: #0f172a; --tmut: #64748b; --tsub: #334155;
            --thbg: #e9eef5; --trodd: #ffffff; --treven: #f8fafc; --trhov: #eff6ff;
            --tbor: rgba(0, 0, 0, .07); --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626; --lbor: rgba(239, 68, 68, .18);
            --sl: #64748b; --sa: rgba(59, 130, 246, .08); --togbg: rgba(241, 245, 249, .95); --togc: #475569;
        }
        
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif !important; background-color: var(--bg) !important; color: var(--tm) !important; margin: 0; overflow-x: hidden; transition: background-color .35s, color .35s; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, .07), transparent 30%); pointer-events: none; }
        
        /* Layout Sidebar & Topbar persis Dashboard */
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; min-width: 260px; background: var(--sb) !important; border-right: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; flex-direction: column; }
        .sidebar-header { border-bottom: 1px solid var(--gb) !important; padding: 20px; text-align: center; }
        .sidebar-header h3 { color: var(--tm) !important; margin: 10px 0 2px; font-size: 18px; font-weight: 700; }
        .sidebar-header p { color: var(--tmut) !important; margin: 0; font-size: 13px; }
        .sidebar-logo { filter: var(--lf); max-width: 60px; }
        .sidebar-menu { list-style: none; margin: 0; padding: 12px 0; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--sl) !important; text-decoration: none; border-left: 3px solid transparent; font-size: 14px; font-weight: 500; transition: .25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--sa) !important; color: #3b82f6 !important; border-left-color: #3b82f6; }
        
        .main-content { flex: 1; display: flex; flex-direction: column; min-width: 0; background: transparent !important; }
        .topbar { background: var(--tb) !important; border-bottom: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 70px; }
        .topbar-left h2 { color: var(--tm) !important; font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
        .topbar-right { display: flex; align-items: center; gap: 15px; }
        
        /* Tombol Tema */
        #theme-toggle-btn { all: unset; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; background: var(--togbg) !important; border: 1px solid var(--gb) !important; color: var(--togc) !important; display: flex !important; align-items: center; justify-content: center; font-size: 17px; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); flex-shrink: 0; transition: all .25s; }
        #theme-toggle-btn:hover { color: #3b82f6 !important; transform: scale(1.1); }
        
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-info strong { color: var(--tm) !important; font-size: 14px; display: block; }
        .user-info p { color: var(--tmut) !important; margin: 0; font-size: 11px; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: #fff !important; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .btn-logout { padding: 8px 18px; background: var(--lbg) !important; color: var(--lc) !important; border: 1px solid var(--lbor) !important; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 500; white-space: nowrap; display: flex; align-items: center; gap: 6px; }

        /* Konten Spesifik view_sop.php */
        .content-wrapper { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
        
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); overflow: hidden; }
        
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 8px !important; border: none !important; color: #fff !important; font-weight: 500; font-size: 14px; cursor: pointer; text-decoration: none; transition: .25s; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-back { background: var(--trodd); color: var(--tsub) !important; border: 1px solid var(--gb) !important; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 12px rgba(16, 185, 129, .3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; box-shadow: 0 4px 12px rgba(245, 158, 11, .3); }
        .btn-edit { background: linear-gradient(135deg, #f59e0b, #d97706) !important; margin-left: auto; }

        .card-header-glow { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 20px 24px; border-bottom: 1px solid var(--gb); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .card-header-glow h2 { margin: 0; text-shadow: 0 0 10px rgba(0, 0, 0, 0.3); font-size: 22px; }
        .card-header-glow span { padding: 6px 14px; background: rgba(255, 255, 255, 0.2); border-radius: 20px; font-size: 13px; font-weight: 500; }
        
        .card-body { padding: 24px; }

        .meta-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 24px; 
            padding: 18px; background: var(--treven); border-radius: 12px; border: 1px solid var(--gb); 
        }
        .meta-label { color: var(--tmut); font-size: 13px; margin: 0; font-weight: 500; }
        .meta-value { color: var(--tm); font-weight: 600; margin: 6px 0 0 0; font-size: 15px; }

        /* Revisi Box Style */
        .revision-box { 
            background: rgba(239, 68, 68, 0.1); 
            border: 1px solid #ef4444; 
            border-left: 5px solid #ef4444; 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 24px; 
            animation: pulse-border 2s infinite;
        }
        @keyframes pulse-border {
            0% { border-color: #ef4444; }
            50% { border-color: transparent; }
            100% { border-color: #ef4444; }
        }

        .section-box { padding: 20px; border-radius: 12px; margin-bottom: 24px; background: var(--trodd); border: 1px solid var(--gb); }
        .section-box:last-child { margin-bottom: 0; }
        
        .section-blue { background: rgba(59, 130, 246, 0.05); border-left: 4px solid #3b82f6; }
        .section-green { background: rgba(16, 185, 129, 0.05); border-left: 4px solid #10b981; }
        .section-violet { background: rgba(139, 92, 246, 0.05); border-left: 4px solid #8b5cf6; }

        .section-title { color: var(--tm); margin-top: 0; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; font-size: 18px; font-weight: 600; }
        .section-box p { color: var(--tsub); line-height: 1.6; margin: 0; font-size: 15px; }
        .section-box pre { font-family: 'Outfit', sans-serif; white-space: pre-wrap; margin: 0; line-height: 1.6; font-size: 15px; color: var(--tsub); }
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
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2><i class="fas fa-file-alt" style="color:#3b82f6;"></i> Detail SOP</h2>
                </div>
                <div class="topbar-right">
                    <button type="button" id="theme-toggle-btn" title="Ganti Tema">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                        <div>
                            <strong><?php echo getNamaLengkap(); ?></strong>
                            <p>User</p>
                        </div>
                    </div>
                    <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="dashboard.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    
                    <a href="print_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-success">
                        <i class="fas fa-print"></i> Cetak
                    </a>

                    <?php if ($sop['status'] == 'Revisi'): ?>
                        <a href="edit_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i> Perbaiki Sekarang
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header-glow">
                        <h2><?php echo htmlspecialchars($sop['judul']); ?></h2>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <span style="background: rgba(255,255,255,0.1);"><?php echo strtoupper($sop['status']); ?></span>
                            <span><?php echo htmlspecialchars($sop['nama_kategori']); ?></span>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div class="meta-grid">
                            <div>
                                <p class="meta-label">Dibuat oleh</p>
                                <p class="meta-value"><?php echo htmlspecialchars($sop['creator'] ?? 'Sistem'); ?></p>
                            </div>
                            <div>
                                <p class="meta-label">Tanggal Dibuat</p>
                                <p class="meta-value"><?php echo date('d F Y, H:i', strtotime($sop['created_at'])); ?> WIB</p>
                            </div>
                            <div>
                                <p class="meta-label">Terakhir Diupdate</p>
                                <p class="meta-value"><?php echo date('d F Y, H:i', strtotime($sop['updated_at'])); ?> WIB</p>
                            </div>
                        </div>

                        <?php if ($sop['status'] == 'Revisi' && !empty($sop['catatan_admin'])): ?>
                        <div class="revision-box">
                            <h3 style="color: #ef4444; margin-top: 0; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-exclamation-circle"></i> Catatan Revisi Admin
                            </h3>
                            <p style="color: var(--tm); font-style: italic; line-height: 1.6; margin-bottom: 15px;">
                                "<?php echo nl2br(htmlspecialchars($sop['catatan_admin'])); ?>"
                            </p>
                            <a href="edit_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-tools"></i> Klik untuk Memperbaiki Dokumen
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($sop['deskripsi'])): ?>
                        <div class="section-box section-blue">
                            <h3 class="section-title"><i class="fas fa-info-circle" style="color: #3b82f6;"></i> Deskripsi</h3>
                            <p><?php echo nl2br(htmlspecialchars($sop['deskripsi'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="section-box section-green">
                            <h3 class="section-title"><i class="fas fa-tasks" style="color: #10b981;"></i> Langkah-langkah Kerja</h3>
                            <pre><?php echo htmlspecialchars($sop['langkah_kerja']); ?></pre>
                        </div>
                        
                        <?php if (!empty($sop['file_attachment'])): ?>
                        <div class="section-box section-violet">
                            <h3 class="section-title"><i class="fas fa-paperclip" style="color: #8b5cf6;"></i> File Lampiran</h3>
                            <p style="margin-bottom: 12px;">
                                <strong>File:</strong> <?php echo htmlspecialchars($sop['file_attachment']); ?>
                            </p>
                            <a href="../assets/uploads/<?php echo $sop['file_attachment']; ?>" target="_blank" class="btn btn-warning">
                                <i class="fas fa-download"></i> Download File
                            </a>
                        </div>
                        <?php endif; ?>
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
                if(icon) icon.className = document.documentElement.getAttribute('data-theme') === 'light' ? 'fas fa-sun' : 'fas fa-moon';
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