<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

// Get statistics
 $sql_total_sop = "SELECT COUNT(*) as total FROM sop";
 $result_sop = mysqli_query($conn, $sql_total_sop);
 $total_sop = mysqli_fetch_assoc($result_sop)['total'];

 $sql_total_kategori = "SELECT COUNT(*) as total FROM categories";
 $result_kategori = mysqli_query($conn, $sql_total_kategori);
 $total_kategori = mysqli_fetch_assoc($result_kategori)['total'];

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
        /* --- DARK THEME CSS --- */
        :root { --primary-glow: #3b82f6; --glass-bg: rgba(30, 41, 59, 0.7); --text-main: #f8fafc; }
        body { font-family: 'Outfit', sans-serif !important; background-color: #020617 !important; color: var(--text-main); overflow-x: hidden; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%); }
        
        .sidebar { background: rgba(15, 23, 42, 0.95) !important; border-right: 1px solid rgba(255,255,255,0.08) !important; backdrop-filter: blur(10px); }
        .sidebar-menu a { color: #94a3b8 !important; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(59, 130, 246, 0.1) !important; color: #fff !important; border-left-color: var(--primary-glow); }
        .sidebar-logo { filter: brightness(0) invert(1); }
        .main-content { background: transparent !important; }
        .topbar { background: rgba(15, 23, 42, 0.8) !important; backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255,255,255,0.08) !important; }
        .topbar-left h2 { color: #fff !important; }
        .user-avatar { background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: white; }
        .btn-logout { padding: 8px 20px; background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; text-decoration: none; }

        /* Stats & Cards */
        .card { background: var(--glass-bg) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; }
        .card-header h3 { color: #fff !important; }
        .stat-card { background: rgba(30, 41, 59, 0.6) !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; color: #fff; }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(59, 130, 246, 0.3) !important; box-shadow: 0 0 20px rgba(59, 130, 246, 0.1); }
        .stat-info h3 { color: #fff !important; text-shadow: 0 0 15px rgba(255,255,255,0.2); }
        .stat-info p { color: #94a3b8 !important; }

        /* Table */
        table { width: 100%; border-collapse: collapse; color: #cbd5e1; }
        th { background: rgba(0,0,0,0.3) !important; color: #94a3b8 !important; padding: 15px; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        
        /* Buttons */
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .alert { border-radius: 10px !important; color: #fff !important; border: none !important; }
        .alert-success { background: rgba(16, 185, 129, 0.15) !important; color: #6ee7b7 !important; }

        /* User Category Card Style */
        .cat-card-item {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.1));
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 20px; border-radius: 12px; color: white;
            transition: all 0.3s ease; backdrop-filter: blur(5px);
        }
        .cat-card-item:hover { transform: translateY(-5px); background: linear-gradient(135deg, rgba(59, 130, 246, 0.4), rgba(37, 99, 235, 0.2)); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo">
                <h3 style="color: white;">SOP Digital</h3>
                <p style="color: #94a3b8;">User Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                <li><a href="browse_sop.php"><i class="fas fa-search"></i><span>Cari SOP</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori</span></a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left"><h2><i class="fas fa-home"></i> Dashboard</h2></div>
                <div class="topbar-right">
                    <div class="user-info"><div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div><div><strong style="color:white"><?php echo getNamaLengkap(); ?></strong><p style="margin:0;font-size:12px;color:#94a3b8">User</p></div></div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <?php if ($flash): ?><div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div><?php endif; ?>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info"><h3><?php echo $total_sop; ?></h3><p>Total SOP</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-folder"></i></div>
                        <div class="stat-info"><h3><?php echo $total_kategori; ?></h3><p>Total Kategori</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                        <div class="stat-info"><h3><?php echo date('H:i'); ?></h3><p><?php echo date('d M Y'); ?></p></div>
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
                                    <h4 style="margin-bottom: 10px; font-size: 18px; color: #fff;"><?php echo htmlspecialchars($cat['nama_kategori']); ?></h4>
                                    <p style="margin: 0; opacity: 0.9; font-size: 14px; color: #cbd5e1;"><?php echo $cat['jumlah_sop']; ?> SOP tersedia</p>
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
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead><tr><th>No</th><th>Judul SOP</th><th>Kategori</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php $no = 1; while ($row = mysqli_fetch_assoc($result_recent)): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['judul']); ?></strong></td>
                                        <td><span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                        <td><a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat</a></td>
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
</body>
</html>