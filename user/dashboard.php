<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';

requireLogin();

// ============================================================
// AJAX: Update Profil
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    header('Content-Type: application/json');
    $user_id      = getUserId();
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $email        = trim($_POST['email'] ?? '');

    if (!$user_id || empty($nama_lengkap) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Data tidak boleh kosong.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid.']); exit;
    }
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $chk->bind_param("si", $email, $user_id); $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { echo json_encode(['success' => false, 'message' => 'Email sudah digunakan akun lain.']); exit; }

    $upd = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, username=? WHERE id=?");
    $upd->bind_param("sssi", $nama_lengkap, $email, $email, $user_id);
    if ($upd->execute()) {
        $_SESSION['nama_lengkap'] = $nama_lengkap; $_SESSION['email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!', 'nama' => $nama_lengkap, 'email' => $email]);
    } else { echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan.']); }
    exit;
}

// ============================================================
// AJAX: Ubah Password
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    header('Content-Type: application/json');
    $user_id       = getUserId();
    $old_password  = $_POST['old_password'] ?? '';
    $new_password  = $_POST['new_password'] ?? '';
    $conf_password = $_POST['conf_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($conf_password)) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']); exit;
    }
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']); exit;
    }
    if ($new_password !== $conf_password) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok.']); exit;
    }
    $sel = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $sel->bind_param("i", $user_id); $sel->execute(); $sel->bind_result($hash); $sel->fetch(); $sel->close();
    if (!password_verify($old_password, $hash)) {
        echo json_encode(['success' => false, 'message' => 'Password lama tidak sesuai.']); exit;
    }
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $upd->bind_param("si", $new_hash, $user_id);
    if ($upd->execute()) { echo json_encode(['success' => true, 'message' => 'Password berhasil diubah!']); }
    else { echo json_encode(['success' => false, 'message' => 'Gagal menyimpan password.']); }
    exit;
}

$user_id = getUserId();

$sql_notif    = "SELECT COUNT(*) as total FROM sop WHERE created_by = $user_id AND status = 'Revisi'";
$result_notif = mysqli_query($conn, $sql_notif);
$notif_count  = mysqli_fetch_assoc($result_notif)['total'];

$sql_total_sop = "SELECT COUNT(*) as total FROM sop";
$result_sop    = mysqli_query($conn, $sql_total_sop);
$total_sop     = mysqli_fetch_assoc($result_sop)['total'];

$sql_total_kategori = "SELECT COUNT(*) as total FROM categories";
$result_kategori    = mysqli_query($conn, $sql_total_kategori);
$total_kategori     = mysqli_fetch_assoc($result_kategori)['total'];

$sql_recent = "SELECT s.*, c.nama_kategori FROM sop s
               LEFT JOIN categories c ON s.kategori_id = c.id
               WHERE s.created_by = $user_id
               ORDER BY s.created_at DESC LIMIT 6";
$result_recent = mysqli_query($conn, $sql_recent);

$sql_cat = "SELECT c.*, COUNT(s.id) as jumlah_sop FROM categories c
            LEFT JOIN sop s ON c.id = s.kategori_id
            GROUP BY c.id ORDER BY c.nama_kategori ASC";
$result_cat = mysqli_query($conn, $sql_cat);

$flash     = getFlashMessage();
$cur_nama  = getNamaLengkap();
$cur_email = $_SESSION['email'] ?? '';
$cur_init  = strtoupper(substr($cur_nama, 0, 1));
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg:#020617;--sb:rgba(15,23,42,.97);--tb:rgba(15,23,42,.87);--cb:rgba(30,41,59,.75);
            --gb:rgba(255,255,255,.08);--tm:#f8fafc;--tmut:#94a3b8;--tsub:#cbd5e1;
            --thbg:rgba(0,0,0,.35);--trodd:rgba(15,23,42,.55);--treven:rgba(15,23,42,.35);
            --trhov:rgba(59,130,246,.09);--tbor:rgba(255,255,255,.06);--lf:brightness(0) invert(1);
            --lbg:rgba(239,68,68,.18);--lc:#fca5a5;--lbor:rgba(239,68,68,.30);
            --sl:#94a3b8;--sa:rgba(59,130,246,.12);--togbg:rgba(30,41,59,.80);--togc:#94a3b8;
            /* dropdown */
            --dd-bg:rgba(18,26,48,.99);--dd-sep:rgba(255,255,255,.08);
            --dd-hover:rgba(255,255,255,.06);--dd-text:#e2e8f0;--dd-danger:#f87171;
        }
        [data-theme="light"] {
            --bg:#f0f4f8;--sb:rgba(255,255,255,.98);--tb:rgba(255,255,255,.96);--cb:rgba(255,255,255,.95);
            --gb:rgba(0,0,0,.09);--tm:#0f172a;--tmut:#64748b;--tsub:#334155;
            --thbg:#e9eef5;--trodd:#ffffff;--treven:#f8fafc;--trhov:#eff6ff;
            --tbor:rgba(0,0,0,.07);--lf:none;--lbg:rgba(239,68,68,.07);--lc:#dc2626;
            --lbor:rgba(239,68,68,.18);--sl:#64748b;--sa:rgba(59,130,246,.08);
            --togbg:rgba(241,245,249,.95);--togc:#475569;
            --dd-bg:#ffffff;--dd-sep:rgba(0,0,0,.09);
            --dd-hover:rgba(0,0,0,.04);--dd-text:#1e293b;--dd-danger:#dc2626;
        }

        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;scroll-behavior:smooth;}

        @keyframes ring-pulse{0%{transform:scale(1);box-shadow:0 0 0 0 rgba(239,68,68,.7);}70%{transform:scale(1.1);box-shadow:0 0 0 10px rgba(239,68,68,0);}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(239,68,68,0);}}
        .notif-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg);border:1px solid var(--gb);color:var(--togc);display:flex;align-items:center;justify-content:center;font-size:17px;transition:.3s;position:relative;text-decoration:none;}
        .notif-btn:hover{color:#3b82f6;border-color:#3b82f6;transform:translateY(-2px);}
        .notif-badge{position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid var(--tb);animation:ring-pulse 2s infinite;}

        .sidebar{background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);}
        .sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;}
        .sidebar-header h3{color:var(--tm)!important;margin:4px 0 2px;font-size:16px;font-weight:700;}
        .sidebar-header p{color:var(--tmut)!important;margin:0;font-size:12px;}
        .sidebar-menu{list-style:none;margin:0;padding:12px 0;}
        .sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}

        .main-content{background:transparent!important;}
        .topbar{background:var(--tb)!important;border-bottom:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:64px;position:relative;z-index:1000;overflow:visible;}
        .topbar-left h2{color:var(--tm)!important;font-size:20px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
        .topbar-right{display:flex;align-items:center;gap:12px;}

        #theme-toggle-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;transition:all .25s;}
        #theme-toggle-btn:hover{color:#3b82f6!important;transform:scale(1.1);}

        /* ═══ USER TRIGGER ═══ */
        .user-info-wrap{position:relative;}
        .user-trigger{display:flex;align-items:center;gap:9px;padding:4px 10px 4px 4px;border-radius:10px;cursor:pointer;transition:background .18s;user-select:none;}
        .user-trigger:hover{background:var(--dd-hover);}
        .user-avatar{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
        .user-trigger-name{font-size:13px;font-weight:600;color:var(--tm);}
        .user-trigger-chevron{font-size:10px;color:var(--tmut);transition:transform .22s;margin-left:1px;}
        .user-trigger-chevron.open{transform:rotate(180deg);}

        /* ═══ DROPDOWN ═══ */
        .user-dropdown{
            position:absolute;top:calc(100% + 8px);right:0;
            min-width:190px;
            background:var(--dd-bg);
            border:1px solid var(--dd-sep);
            border-radius:10px;
            padding:5px 0;
            box-shadow:0 10px 40px rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.12);
            backdrop-filter:blur(24px);
            opacity:0;pointer-events:none;
            transform:translateY(-5px) scale(.97);
            transform-origin:top right;
            transition:opacity .16s ease,transform .16s ease;
            z-index:600;
        }
        .user-dropdown.show{opacity:1;pointer-events:auto;transform:translateY(0) scale(1);}

        .dd-item{
            display:block;width:100%;
            padding:11px 20px;
            font-size:13.5px;font-weight:500;letter-spacing:.01em;
            color:var(--dd-text);
            background:none;border:none;
            text-align:left;cursor:pointer;
            text-decoration:none;
            font-family:'Outfit',sans-serif;
            transition:background .12s;
            white-space:nowrap;
        }
        .dd-item:hover{background:var(--dd-hover);}
        .dd-sep{height:1px;background:var(--dd-sep);margin:3px 0;}
        .dd-item-logout{color:var(--dd-danger)!important;font-weight:600;}
        .dd-item-logout:hover{background:rgba(239,68,68,.07)!important;}

        /* ═══ MODAL BASE ═══ */
        .modal-overlay{position:fixed;inset:0;z-index:9999;background:rgba(2,6,23,.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .22s ease;padding:20px;}
        .modal-overlay.show{opacity:1;pointer-events:auto;}
        .modal-card{background:var(--sb);border:1px solid var(--gb);border-radius:16px;width:100%;max-width:440px;box-shadow:0 24px 60px rgba(0,0,0,.38);transform:scale(.96) translateY(14px);transition:transform .22s ease;overflow:hidden;}
        .modal-overlay.show .modal-card{transform:scale(1) translateY(0);}

        .modal-header{display:flex;align-items:center;gap:12px;padding:18px 20px 14px;border-bottom:1px solid var(--gb);}
        .modal-icon-wrap{width:40px;height:40px;border-radius:10px;flex-shrink:0;background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(139,92,246,.18));border:1px solid rgba(59,130,246,.28);display:flex;align-items:center;justify-content:center;font-size:15px;color:#60a5fa;}
        .modal-header h3{margin:0 0 2px;font-size:15px;font-weight:700;color:var(--tm);}
        .modal-header p{margin:0;font-size:11px;color:var(--tmut);}
        .modal-close{margin-left:auto;background:none;border:none;cursor:pointer;color:var(--tmut);font-size:14px;width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;transition:.18s;flex-shrink:0;}
        .modal-close:hover{background:rgba(239,68,68,.1);color:#ef4444;}

        .modal-alert{margin:12px 20px 0;padding:9px 12px;border-radius:8px;font-size:12.5px;display:none;align-items:center;gap:7px;}
        .modal-alert.success{background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.2);display:flex;}
        .modal-alert.error{background:rgba(239,68,68,.1);color:#ef4444;border:1px solid rgba(239,68,68,.2);display:flex;}

        .modal-body{padding:14px 20px 18px;display:flex;flex-direction:column;gap:13px;}
        .mf-group label{display:block;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--tm);margin-bottom:6px;}
        .mf-wrap{position:relative;}
        .mf-wrap i.mf-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--tmut);font-size:13px;pointer-events:none;z-index:1;}
        .mf-wrap input{width:100%;padding:10px 36px;background:var(--togbg);border:1px solid var(--gb);border-radius:9px;color:var(--tm);font-size:13px;font-family:'Outfit',sans-serif;transition:all .2s;}
        .mf-wrap input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);background:var(--cb);}
        .mf-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--tmut);font-size:13px;cursor:pointer;z-index:2;}
        .mf-eye:hover{color:#60a5fa;}

        .modal-footer{display:flex;gap:8px;justify-content:flex-end;padding:0 20px 18px;}
        .mf-btn-cancel{padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;background:none;border:1px solid var(--gb);color:var(--tmut);transition:.18s;}
        .mf-btn-cancel:hover{border-color:#ef4444;color:#ef4444;}
        .mf-btn-save{padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;background:linear-gradient(90deg,#3b82f6,#8b5cf6);color:#fff;border:none;box-shadow:0 4px 12px rgba(59,130,246,.28);transition:.2s;display:flex;align-items:center;gap:6px;}
        .mf-btn-save:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(139,92,246,.36);}
        .mf-btn-save:disabled{opacity:.65;cursor:not-allowed;transform:none;}

        @keyframes fadeSlide{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}

        /* page */
        .content-wrapper{padding:24px;}
        .card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,.10);margin-bottom:24px;}
        .card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--gb);}
        .card-header h3{color:var(--tm)!important;margin:0;font-size:16px;font-weight:600;}
        .card-body{padding:22px;}
        .stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px;}
        .stat-card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;padding:20px;display:flex;align-items:center;gap:15px;transition:.3s;}
        .stat-card:hover{transform:translateY(-5px);border-color:#3b82f6!important;}
        .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;}
        .stat-icon.blue{background:rgba(59,130,246,.1);color:#3b82f6;}
        .stat-icon.green{background:rgba(16,185,129,.1);color:#10b981;}
        .stat-icon.purple{background:rgba(139,92,246,.1);color:#8b5cf6;}
        .stat-info h3{color:var(--tm)!important;font-size:24px;margin:0;}
        .stat-info p{color:var(--tmut)!important;margin:0;font-size:14px;}
        .table-responsive{overflow-x:auto;border-radius:10px;overflow:hidden;}
        table{width:100%!important;border-collapse:collapse!important;}
        thead tr{background:var(--thbg)!important;}
        thead th{background:var(--thbg)!important;color:var(--tmut)!important;padding:13px 16px!important;font-size:.75rem!important;font-weight:600!important;text-transform:uppercase!important;border:none!important;text-align:left;}
        tbody tr:nth-child(odd) td{background:var(--trodd)!important;}
        tbody tr:nth-child(even) td{background:var(--treven)!important;}
        tbody tr:hover td{background:var(--trhov)!important;}
        tbody td{color:var(--tsub)!important;padding:14px 16px!important;border-bottom:1px solid var(--tbor)!important;}
        .badge{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 15px;border-radius:8px!important;border:none!important;color:#fff!important;font-weight:600;font-size:12px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;}
        .btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706)!important;color:#fff!important;}
        .btn-sm{padding:6px 12px!important;}
        .alert{border-radius:10px!important;padding:12px 18px;margin-bottom:20px;font-size:14px;}
        .alert-success{background:rgba(16,185,129,.12)!important;color:#059669!important;border:1px solid rgba(16,185,129,.25)!important;}
        .cat-card-item{background:var(--trodd);border:1px solid var(--gb);padding:20px;border-radius:12px;color:var(--tm);transition:all .3s ease;}
        .cat-card-item:hover{transform:translateY(-5px);border-color:#3b82f6;background:var(--trhov);}
        .cat-card-item h4{margin:0 0 10px;font-size:16px;font-weight:600;color:var(--tm);}
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width:220px;">
            <p>SOP Digital System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse_sop.php"><i class="fas fa-file-alt"></i><span>Daftar SOP</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-home" style="color:#3b82f6"></i> Dashboard</h2>
            </div>
            <div class="topbar-right">

                <a href="#status-sop" id="notif-btn" class="notif-btn"
                   data-count="<?php echo $notif_count; ?>"
                   title="<?php echo ($notif_count > 0) ? 'Ada '.$notif_count.' revisi' : 'Tidak ada notifikasi'; ?>">
                    <i class="fas fa-bell"></i>
                    <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </a>

                <button type="button" id="theme-toggle-btn" title="Ganti Tema">
                    <i class="fas fa-moon" id="theme-icon"></i>
                </button>

                <!-- USER TRIGGER + DROPDOWN -->
                <div class="user-info-wrap">
                    <div class="user-trigger" id="userTrigger">
                        <div class="user-avatar" id="topbarAvatar"><?php echo $cur_init; ?></div>
                        <span class="user-trigger-name" id="topbarNama"><?php echo htmlspecialchars($cur_nama); ?></span>
                        <i class="fas fa-chevron-down user-trigger-chevron" id="userChevron"></i>
                    </div>

                    <!-- Dropdown bersih — hanya 3 item -->
                    <div class="user-dropdown" id="userDropdown">
                        <button class="dd-item" id="openEditProfil">Edit Profil</button>
                        <div class="dd-sep"></div>
                        <button class="dd-item" id="openUbahPassword">Ubah Password</button>
                        <div class="dd-sep"></div>
                        <a href="../logout.php" class="dd-item dd-item-logout">Logout</a>
                    </div>
                </div>

            </div>
        </div>

        <div class="content-wrapper">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-info-circle"></i> <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

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
                    <div class="stat-info">
                        <h3 id="liveClock"><?php echo date('H:i:s'); ?></h3>
                        <p id="liveDate"><?php echo date('d M Y'); ?></p></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Kategori SOP</h3>
                    <a href="kategori.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px;">
                        <?php mysqli_data_seek($result_cat, 0); while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                            <a href="browse_sop.php?kategori=<?php echo $cat['id']; ?>" style="text-decoration:none;">
                                <div class="cat-card-item">
                                    <h4><?php echo htmlspecialchars($cat['nama_kategori']); ?></h4>
                                    <p style="margin:0;font-size:13px;color:var(--tmut);"><i class="fas fa-file-alt"></i> <?php echo $cat['jumlah_sop']; ?> SOP</p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="card" id="status-sop">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Status SOP Saya</h3>
                    <a href="browse_sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat SOP</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th width="5%">No</th><th>Judul SOP</th><th>Kategori</th>
                                    <th>Status</th><th>Tanggal</th><th width="20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no=1; while($row=mysqli_fetch_assoc($result_recent)): $status=$row['status']; ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td style="font-weight:500;color:var(--tm)!important"><?php echo htmlspecialchars($row['judul']); ?></td>
                                    <td><span class="badge" style="background:var(--sa);color:#3b82f6;border:1px solid rgba(59,130,246,.3);"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td>
                                    <td>
                                        <?php if($status=='Disetujui'): ?><span class="badge" style="background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3);">Disetujui</span>
                                        <?php elseif($status=='Review'): ?><span class="badge" style="background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.3);">Review</span>
                                        <?php elseif($status=='Revisi'): ?><span class="badge" style="background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3);">Revisi</span>
                                        <?php else: ?><span class="badge" style="background:rgba(148,163,184,.15);color:#94a3b8;border:1px solid rgba(148,163,184,.3);">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y',strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex;gap:5px;">
                                            <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                            <?php if($status=='Revisi'): ?>
                                                <a href="edit_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Perbaiki</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result_recent)==0): ?>
                                    <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--tmut)">Belum ada data.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL: Edit Profil -->
<div class="modal-overlay" id="modalEditProfil">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-icon-wrap"><i class="fas fa-user-edit"></i></div>
            <div><h3>Edit Profil</h3><p>Perubahan tersimpan langsung ke sistem</p></div>
            <button class="modal-close" data-close="modalEditProfil"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-alert" id="alertEditProfil"></div>
        <form id="formEditProfil" autocomplete="off">
            <input type="hidden" name="action" value="update_profile">
            <div class="modal-body">
                <div class="mf-group">
                    <label>Nama Lengkap</label>
                    <div class="mf-wrap">
                        <i class="fas fa-id-card mf-icon"></i>
                        <input type="text" name="nama_lengkap" id="editNama"
                               value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required>
                    </div>
                </div>
                <div class="mf-group">
                    <label>Email <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label>
                    <div class="mf-wrap">
                        <i class="fas fa-envelope mf-icon"></i>
                        <input type="email" name="email" id="editEmail"
                               value="<?php echo htmlspecialchars($cur_email); ?>" placeholder="email@sinergi.co.id" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="mf-btn-cancel" data-close="modalEditProfil">Batal</button>
                <button type="submit" class="mf-btn-save" id="btnSaveProfil"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ubah Password -->
<div class="modal-overlay" id="modalUbahPassword">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-icon-wrap"><i class="fas fa-lock"></i></div>
            <div><h3>Ubah Password</h3><p>Gunakan password yang kuat dan unik</p></div>
            <button class="modal-close" data-close="modalUbahPassword"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-alert" id="alertUbahPassword"></div>
        <form id="formUbahPassword" autocomplete="off">
            <input type="hidden" name="action" value="update_password">
            <div class="modal-body">
                <div class="mf-group">
                    <label>Password Lama</label>
                    <div class="mf-wrap">
                        <i class="fas fa-lock mf-icon"></i>
                        <input type="password" name="old_password" id="oldPass" placeholder="Password saat ini">
                        <i class="fas fa-eye mf-eye" data-target="oldPass"></i>
                    </div>
                </div>
                <div class="mf-group">
                    <label>Password Baru</label>
                    <div class="mf-wrap">
                        <i class="fas fa-lock mf-icon"></i>
                        <input type="password" name="new_password" id="newPass" placeholder="Min. 6 karakter">
                        <i class="fas fa-eye mf-eye" data-target="newPass"></i>
                    </div>
                </div>
                <div class="mf-group">
                    <label>Konfirmasi Password Baru</label>
                    <div class="mf-wrap">
                        <i class="fas fa-lock mf-icon"></i>
                        <input type="password" name="conf_password" id="confPass" placeholder="Ulangi password baru">
                        <i class="fas fa-eye mf-eye" data-target="confPass"></i>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="mf-btn-cancel" data-close="modalUbahPassword">Batal</button>
                <button type="submit" class="mf-btn-save" id="btnSavePassword"><i class="fas fa-key"></i> Ubah Password</button>
            </div>
        </form>
    </div>
</div>

<script>
(function(){if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');})();

document.addEventListener('DOMContentLoaded',function(){

    // tema
    var btn=document.getElementById('theme-toggle-btn'),icon=document.getElementById('theme-icon');
    function syncIcon(){if(icon)icon.className=document.documentElement.getAttribute('data-theme')==='light'?'fas fa-sun':'fas fa-moon';}
    syncIcon();
    if(btn)btn.addEventListener('click',function(){
        var l=document.documentElement.getAttribute('data-theme')==='light';
        if(l){document.documentElement.removeAttribute('data-theme');localStorage.setItem('theme','dark');}
        else{document.documentElement.setAttribute('data-theme','light');localStorage.setItem('theme','light');}
        syncIcon();
    });

    // notifikasi
    var nb=document.getElementById('notif-btn');
    if(nb)nb.addEventListener('click',function(e){
        if(parseInt(this.getAttribute('data-count'))===0){e.preventDefault();
            Swal.fire({title:'Tidak Ada Notifikasi',text:'Semua dokumen aman!',icon:'info',confirmButtonColor:'#3b82f6',
                background:getComputedStyle(document.documentElement).getPropertyValue('--cb').trim(),
                color:getComputedStyle(document.documentElement).getPropertyValue('--tm').trim()});}
    });

    // dropdown
    var trigger=document.getElementById('userTrigger'),
        dropdown=document.getElementById('userDropdown'),
        chevron=document.getElementById('userChevron');

    trigger.addEventListener('click',function(e){
        e.stopPropagation();
        var open=dropdown.classList.toggle('show');
        chevron.classList.toggle('open',open);
    });
    document.addEventListener('click',function(e){
        if(!dropdown.contains(e.target)&&!trigger.contains(e.target)){
            dropdown.classList.remove('show');chevron.classList.remove('open');
        }
    });

    // modal helpers
    function openModal(id){
        document.getElementById(id).classList.add('show');
        dropdown.classList.remove('show');chevron.classList.remove('open');
        var al=document.querySelector('#'+id+' .modal-alert');
        if(al)al.className='modal-alert';
    }
    function closeModal(id){document.getElementById(id).classList.remove('show');}

    document.getElementById('openEditProfil').addEventListener('click',function(){openModal('modalEditProfil');});
    document.getElementById('openUbahPassword').addEventListener('click',function(){openModal('modalUbahPassword');});

    document.querySelectorAll('[data-close]').forEach(function(el){
        el.addEventListener('click',function(){closeModal(this.getAttribute('data-close'));});
    });
    document.querySelectorAll('.modal-overlay').forEach(function(ov){
        ov.addEventListener('click',function(e){if(e.target===ov)closeModal(ov.id);});
    });

    // show/hide password
    document.querySelectorAll('.mf-eye').forEach(function(eye){
        eye.addEventListener('click',function(){
            var inp=document.getElementById(this.dataset.target);if(!inp)return;
            inp.type=inp.type==='password'?'text':'password';
            this.classList.toggle('fa-eye');this.classList.toggle('fa-eye-slash');
        });
    });

    // alert helper
    function showAlert(boxId,msg,type){
        var el=document.getElementById(boxId);
        el.className='modal-alert '+type;
        el.innerHTML='<i class="fas '+(type==='success'?'fa-check-circle':'fa-exclamation-circle')+'"></i>&nbsp;'+msg;
    }

    // form edit profil
    document.getElementById('formEditProfil').addEventListener('submit',function(e){
        e.preventDefault();
        var sb=document.getElementById('btnSaveProfil');
        sb.disabled=true;sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)})
            .then(function(r){return r.json();})
            .then(function(res){
                showAlert('alertEditProfil',res.message,res.success?'success':'error');
                if(res.success){
                    var init=res.nama.charAt(0).toUpperCase();
                    var ta=document.getElementById('topbarAvatar');if(ta)ta.textContent=init;
                    var tn=document.getElementById('topbarNama');if(tn)tn.textContent=res.nama;
                    document.getElementById('editNama').value=res.nama;
                    document.getElementById('editEmail').value=res.email;
                    setTimeout(function(){closeModal('modalEditProfil');},1500);
                }
            })
            .catch(function(){showAlert('alertEditProfil','Kesalahan jaringan.','error');})
            .finally(function(){sb.disabled=false;sb.innerHTML='<i class="fas fa-save"></i> Simpan';});
    });

    // form ubah password
    document.getElementById('formUbahPassword').addEventListener('submit',function(e){
        e.preventDefault();
        var np=document.getElementById('newPass').value,cp=document.getElementById('confPass').value;
        if(np!==cp){showAlert('alertUbahPassword','Konfirmasi password tidak cocok.','error');return;}
        var sb=document.getElementById('btnSavePassword');
        sb.disabled=true;sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)})
            .then(function(r){return r.json();})
            .then(function(res){
                showAlert('alertUbahPassword',res.message,res.success?'success':'error');
                if(res.success){
                    ['oldPass','newPass','confPass'].forEach(function(id){document.getElementById(id).value='';});
                    setTimeout(function(){closeModal('modalUbahPassword');},1500);
                }
            })
            .catch(function(){showAlert('alertUbahPassword','Kesalahan jaringan.','error');})
            .finally(function(){sb.disabled=false;sb.innerHTML='<i class="fas fa-key"></i> Ubah Password';});
    });
});
</script>
<script>
(function () {
    var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function pad(n) { return n < 10 ? '0' + n : n; }

    function tick() {
        var now = new Date();

        var h = pad(now.getHours());
        var m = pad(now.getMinutes());
        var s = pad(now.getSeconds());

        var dayName  = days[now.getDay()];
        var dateStr  = now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();

        var clockEl = document.getElementById('liveClock');
        var dateEl  = document.getElementById('liveDate');

        if (clockEl) clockEl.textContent = h + ':' + m + ':' + s;
        if (dateEl)  dateEl.textContent  = dayName + ', ' + dateStr;
    }

tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>