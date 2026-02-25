<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

// Get statistics
$sql_total_sop = "SELECT COUNT(*) as total FROM sop";
$result_sop    = mysqli_query($conn, $sql_total_sop);
$total_sop     = mysqli_fetch_assoc($result_sop)['total'];

$sql_total_kategori = "SELECT COUNT(*) as total FROM categories";
$result_kategori    = mysqli_query($conn, $sql_total_kategori);
$total_kategori     = mysqli_fetch_assoc($result_kategori)['total'];

// Get recent SOPs
$sql_recent = "SELECT s.*, c.nama_kategori FROM sop s 
               LEFT JOIN categories c ON s.kategori_id = c.id 
               ORDER BY s.created_at DESC LIMIT 6";
$result_recent = mysqli_query($conn, $sql_recent);

// Get categories
$sql_cat = "SELECT c.*, COUNT(s.id) as jumlah_sop FROM categories c 
            LEFT JOIN sop s ON c.id = s.kategori_id 
            GROUP BY c.id ORDER BY c.nama_kategori ASC";
$result_cat = mysqli_query($conn, $sql_cat);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
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
            --tbor: rgba(0, 0, 0, .07); --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626; 
            --lbor: rgba(239, 68, 68, .18); --sl: #64748b; --sa: rgba(59, 130, 246, .08); 
            --togbg: rgba(241, 245, 249, .95); --togc: #475569;
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
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); margin-bottom: 24px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--gb); }
        .card-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 600; }
        .card-body { padding: 22px; }

        .stat-card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; }
        .stat-card:hover { transform: translateY(-5px); border-color: #3b82f6 !important; box-shadow: 0 4px 20px rgba(59, 130, 246, .15); }
        .stat-info h3 { color: var(--tm) !important; font-size: 24px; margin-bottom: 4px; }
        .stat-info p { color: var(--tmut) !important; }

        .table-responsive { overflow-x: auto; border-radius: 10px; overflow: hidden; }
        table { width: 100% !important; border-collapse: collapse !important; }
        thead tr { background: var(--thbg) !important; }
        thead th { background: var(--thbg) !important; color: var(--tmut) !important; padding: 13px 16px !important; font-size: .75rem !important; font-weight: 600 !important; text-transform: uppercase !important; border: none !important; }
        tbody tr:nth-child(odd) td { background: var(--trodd) !important; }
        tbody tr:nth-child(even) td { background: var(--treven) !important; }
        tbody tr:hover td { background: var(--trhov) !important; }
        tbody td { color: var(--tsub) !important; padding: 14px 16px !important; border-bottom: 1px solid var(--tbor) !important; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 9px !important; border: none !important; color: #fff !important; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: .25s; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; box-shadow: 0 4px 12px rgba(59, 130, 246, .3); }
        .btn-sm { padding: 6px 12px !important; font-size: 12px !important; }

        .alert { border-radius: 10px !important; padding: 12px 18px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, .12) !important; color: #059669 !important; border: 1px solid rgba(16, 185, 129, .25) !important; }

        .cat-card-item { background: var(--trodd); border: 1px solid var(--gb); padding: 20px; border-radius: 12px; color: var(--tm); transition: all .3s ease; }
        .cat-card-item:hover { transform: translateY(-5px); border-color: #3b82f6; background: var(--trhov); box-shadow: 0 10px 20px rgba(0, 0, 0, .1); }
        .cat-card-item h4 { margin-bottom: 10px; font-size: 16px; font-weight: 600; color: var(--tm); }
        .cat-card-item p { margin: 0; font-size: 13px; color: var(--tmut); }
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2><i class="fas fa-home" style="color:#3b82f6"></i> Dashboard</h2>
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
                    <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $total_sop; ?></h3>
                            <p>Total SOP</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-folder"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $total_kategori; ?></h3>
                            <p>Total Kategori</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3><?php echo date('H:i'); ?></h3>
                            <p><?php echo date('d M Y'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> Kategori SOP</h3>
                        <a href="kategori.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <?php while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                                <a href="browse_sop.php?kategori=<?php echo $cat['id']; ?>" style="text-decoration: none;">
                                    <div class="cat-card-item">
                                        <h4><?php echo htmlspecialchars($cat['nama_kategori']); ?></h4>
                                        <p><i class="fas fa-file-alt" style="margin-right:5px"></i> <?php echo $cat['jumlah_sop']; ?> SOP tersedia</p>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> SOP Terbaru</h3>
                        <a href="browse_sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Judul SOP</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result_recent)): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td style="font-weight:500; color:var(--tm)!important">
                                                <?php echo htmlspecialchars($row['judul']); ?>
                                            </td>
                                            <td>
                                                <span class="badge" style="background:var(--sa); color:#3b82f6; border:1px solid rgba(59,130,246,0.3);">
                                                    <?php echo htmlspecialchars($row['nama_kategori']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] == 'Disetujui'): ?>
                                                    <span class="badge" style="background: rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3);">Disetujui</span>
                                                <?php elseif ($row['status'] == 'Pending'): ?>
                                                    <span class="badge" style="background: rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3);">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3);">Ditolak</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> Lihat
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
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