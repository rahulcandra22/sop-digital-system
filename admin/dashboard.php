<?php
require_once '../config/database.php';
require_once '../includes/session.php';

requireAdmin();

// Get statistics
 $sql_total_sop = "SELECT COUNT(*) as total FROM sop";
 $result_sop = mysqli_query($conn, $sql_total_sop);
 $total_sop = mysqli_fetch_assoc($result_sop)['total'];

 $sql_total_kategori = "SELECT COUNT(*) as total FROM categories";
 $result_kategori = mysqli_query($conn, $sql_total_kategori);
 $total_kategori = mysqli_fetch_assoc($result_kategori)['total'];

 $sql_total_user = "SELECT COUNT(*) as total FROM users WHERE role='user'";
 $result_user = mysqli_query($conn, $sql_total_user);
 $total_user = mysqli_fetch_assoc($result_user)['total'];

// Get recent SOPs
 $sql_recent = "SELECT s.*, c.nama_kategori FROM sop s 
               LEFT JOIN categories c ON s.kategori_id = c.id 
               ORDER BY s.created_at DESC LIMIT 5";
$result_recent = mysqli_query($conn, $sql_recent);
if (!$result_recent) {
    die("Query error: " . mysqli_error($conn));
}
 $flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- FORCE DARK THEME OVERRIDE --- */
        :root {
            --primary-glow: #3b82f6;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        body {
            font-family: 'Outfit', sans-serif !important;
            background-color: #020617 !important; /* Deep Dark Background */
            color: var(--text-main);
            overflow-x: hidden;
        }
        /* Background Effects */
        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, 0.08), transparent 25%),
                        radial-gradient(circle at 85% 30%, rgba(139, 92, 246, 0.08), transparent 25%);
        }
        
        /* Sidebar */
        .sidebar {
            background: rgba(15, 23, 42, 0.95) !important;
            border-right: 1px solid var(--glass-border) !important;
            backdrop-filter: blur(10px);
        }
        .sidebar-header { border-bottom: 1px solid var(--glass-border) !important; }
        .sidebar-logo { filter: brightness(0) invert(1); }
        .sidebar-menu a { color: #94a3b8 !important; transition: 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #fff !important;
            border-left-color: var(--primary-glow);
        }

        /* Main Content */
        .main-content { background: transparent !important; }
        .topbar {
            background: rgba(15, 23, 42, 0.8) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border) !important;
        }
        .topbar-left h2 { color: #fff !important; text-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
        
        /* Cards */
        .card { background: var(--glass-bg) !important; border: 1px solid var(--glass-border) !important; border-radius: 16px !important; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .card-header h3 { color: #fff !important; }

        /* Stats */
        .stat-card {
            background: rgba(30, 41, 59, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 16px !important;
            color: #fff;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(59, 130, 246, 0.3) !important; box-shadow: 0 0 20px rgba(59, 130, 246, 0.1); }
        .stat-info h3 { color: #fff !important; text-shadow: 0 0 15px rgba(255,255,255,0.2); }
        .stat-info p { color: var(--text-muted) !important; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; color: #cbd5e1; }
        th { background: rgba(0,0,0,0.3) !important; color: #94a3b8 !important; padding: 15px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        tr:hover td { background: rgba(255,255,255,0.03) !important; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }

        /* Buttons */
        .btn { border-radius: 8px !important; border: none !important; color: white; transition: 0.3s; font-weight: 500; }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); }
        .btn-info { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }

        /* Alerts */
        .alert { border-radius: 10px !important; color: #fff !important; border: none !important; }
        .alert-success { background: rgba(16, 185, 129, 0.15) !important; color: #6ee7b7 !important; border: 1px solid rgba(16, 185, 129, 0.3) !important; }
        .alert-danger { background: rgba(239, 68, 68, 0.15) !important; color: #fca5a5 !important; border: 1px solid rgba(239, 68, 68, 0.3) !important; }

        /* User */
        .user-avatar { background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important; color: white; box-shadow: 0 0 10px rgba(139, 92, 246, 0.4); }
        .btn-logout { padding: 8px 20px; background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; text-decoration: none; transition: 0.3s; }
        .btn-logout:hover { background: rgba(239,68,68,0.4); }

        /* Grid Actions */
        .grid-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .grid-buttons a { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 15px; text-decoration: none; border-radius: 12px; color: white; font-weight: 600; transition: 0.3s; }
        .grid-buttons a:hover { transform: translateY(-3px); filter: brightness(1.1); }

        /* Status */
        .status-draft {
        background: rgba(71, 85, 105, 0.3);
        color: #94a3b8;
        border: 1px solid rgba(71, 85, 105, 0.5);
        }
        .status-review {
        background: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
        border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .status-approved {
        background: rgba(16, 185, 129, 0.2);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.4);
        }
        .status-revisi {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.4);
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo">
                <h3 style="color: white;">SOP Digital</h3>
                <p style="color: #94a3b8;">Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
                <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
                <li><a href="sop.php"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
                <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="topbar">
                <div class="topbar-left">
                    <h2><i class="fas fa-chart-line" style="color: var(--primary-glow);"></i> Dashboard</h2>
                </div>
                <div class="topbar-right">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr(getNamaLengkap(), 0, 1)); ?></div>
                        <div>
                            <strong style="color: white;"><?php echo getNamaLengkap(); ?></strong>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">Administrator</p>
                        </div>
                    </div>
                    <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            
            <div class="content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>"><i class="fas fa-info-circle"></i> <?php echo $flash['message']; ?></div>
                <?php endif; ?>
                
                <!-- Stats -->
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
                        <div class="stat-icon orange"><i class="fas fa-users"></i></div>
                        <div class="stat-info"><h3><?php echo $total_user; ?></h3><p>Total User</p></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                        <div class="stat-info"><h3><?php echo date('H:i'); ?></h3><p><?php echo date('d M Y'); ?></p></div>
                    </div>
                </div>
                
                <!-- Recent SOPs -->
                <div class="card">
                <div class="card-header">
                <h3><i class="fas fa-history"></i> SOP Terbaru</h3>
                    <a href="sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
                </div>
            <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul SOP</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = mysqli_query($conn, "SELECT s.*, c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id = c.id ORDER BY s.created_at DESC LIMIT 5");
                $no = 1;
                if (mysqli_num_rows($res) > 0):
                    while ($row = mysqli_fetch_assoc($res)):
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td>
                    <td><?php
                        $s = trim($row['status']);
                        switch($s) {
                            case 'Draft':     echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(71,85,105,0.5);color:#cbd5e1;border:1px solid #475569">Draft</span>'; break;
                            case 'Review':    echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(245,158,11,0.3);color:#fbbf24;border:1px solid #d97706">Review</span>'; break;
                            case 'Disetujui': echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(16,185,129,0.3);color:#34d399;border:1px solid #059669">Disetujui</span>'; break;
                            default:          echo '<span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;background:rgba(239,68,68,0.3);color:#f87171;border:1px solid #dc2626">'.$s.'</span>';
                        }
                    ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                    <td><a href="sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">Belum ada data</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
                
                <!-- Quick Actions -->
<div class="card">
    <div class="card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
    <div class="card-body">
        <div class="grid-buttons">
            <a href="sop.php" style="background:linear-gradient(135deg,#10b981,#059669)"><i class="fas fa-plus"></i> Tambah SOP</a>
            <a href="kategori.php" style="background:linear-gradient(135deg,#3b82f6,#2563eb)"><i class="fas fa-folder-plus"></i> Tambah Kategori</a>
            <a href="users.php" style="background:linear-gradient(135deg,#f59e0b,#d97706)"><i class="fas fa-user-plus"></i> Tambah User</a>
        </div>
            </div>
            </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/script.js"></script>
</body>
</html>