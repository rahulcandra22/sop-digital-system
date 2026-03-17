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
    if (!$user_id || empty($nama_lengkap) || empty($email)) { echo json_encode(['success'=>false,'message'=>'Data tidak boleh kosong.']); exit; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>'Format email tidak valid.']); exit; }
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $chk->bind_param("si",$email,$user_id); $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { echo json_encode(['success'=>false,'message'=>'Email sudah digunakan akun lain.']); exit; }
    $upd = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, username=? WHERE id=?");
    $upd->bind_param("sssi",$nama_lengkap,$email,$email,$user_id);
    if ($upd->execute()) { $_SESSION['nama_lengkap']=$nama_lengkap; $_SESSION['email']=$email; echo json_encode(['success'=>true,'message'=>'Profil berhasil diperbarui!','nama'=>$nama_lengkap,'email'=>$email]); }
    else { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan perubahan.']); }
    exit;
}
// ============================================================
// AJAX: Ubah Password
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    header('Content-Type: application/json');
    $user_id=getUserId();
    $old=$_POST['old_password']??''; $new=$_POST['new_password']??''; $conf=$_POST['conf_password']??'';
    if (empty($old)||empty($new)||empty($conf)) { echo json_encode(['success'=>false,'message'=>'Semua field wajib diisi.']); exit; }
    if (strlen($new)<6) { echo json_encode(['success'=>false,'message'=>'Password baru minimal 6 karakter.']); exit; }
    if ($new!==$conf) { echo json_encode(['success'=>false,'message'=>'Konfirmasi password tidak cocok.']); exit; }
    $sel=$conn->prepare("SELECT password FROM users WHERE id=? LIMIT 1");
    $sel->bind_param("i",$user_id); $sel->execute(); $sel->bind_result($hash); $sel->fetch(); $sel->close();
    if (!password_verify($old,$hash)) { echo json_encode(['success'=>false,'message'=>'Password lama tidak sesuai.']); exit; }
    $nh=password_hash($new,PASSWORD_DEFAULT);
    $upd=$conn->prepare("UPDATE users SET password=? WHERE id=?");
    $upd->bind_param("si",$nh,$user_id);
    if ($upd->execute()) echo json_encode(['success'=>true,'message'=>'Password berhasil diubah!']);
    else echo json_encode(['success'=>false,'message'=>'Gagal menyimpan password.']);
    exit;
}

if (!isset($_GET['id'])) { header('Location: browse_sop.php'); exit(); }

$id     = intval($_GET['id']);
$sql    = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s
           LEFT JOIN categories c ON s.kategori_id = c.id
           LEFT JOIN users u ON s.created_by = u.id
           WHERE s.id = $id";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) == 0) { header('Location: browse_sop.php'); exit(); }
$sop = mysqli_fetch_assoc($result);

$cur_nama  = getNamaLengkap();
$cur_email = $_SESSION['email'] ?? '';
$cur_init  = strtoupper(substr($cur_nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sop['judul']); ?> - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page-transition.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:#020617;--sb:rgba(15,23,42,.97);--tb:rgba(15,23,42,.87);--cb:rgba(30,41,59,.75);
            --gb:rgba(255,255,255,.08);--tm:#f8fafc;--tmut:#94a3b8;--tsub:#cbd5e1;
            --thbg:rgba(0,0,0,.35);--trodd:rgba(15,23,42,.55);--treven:rgba(15,23,42,.35);
            --trhov:rgba(59,130,246,.09);--tbor:rgba(255,255,255,.06);--lf:brightness(0) invert(1);
            --lbg:rgba(239,68,68,.18);--lc:#fca5a5;--lbor:rgba(239,68,68,.30);
            --sl:#94a3b8;--sa:rgba(59,130,246,.12);--togbg:rgba(30,41,59,.80);--togc:#94a3b8;
            --dd-bg:rgba(18,26,48,.99);--dd-sep:rgba(255,255,255,.08);--dd-hover:rgba(255,255,255,.06);--dd-text:#e2e8f0;--dd-danger:#f87171;
        }
        [data-theme="light"] {
            --bg:#f0f4f8;--sb:rgba(255,255,255,.98);--tb:rgba(255,255,255,.96);--cb:rgba(255,255,255,.95);
            --gb:rgba(0,0,0,.09);--tm:#0f172a;--tmut:#64748b;--tsub:#334155;
            --thbg:#e9eef5;--trodd:#ffffff;--treven:#f8fafc;--trhov:#eff6ff;
            --tbor:rgba(0,0,0,.07);--lf:none;--lbg:rgba(239,68,68,.07);--lc:#dc2626;--lbor:rgba(239,68,68,.18);
            --sl:#64748b;--sa:rgba(59,130,246,.08);--togbg:rgba(241,245,249,.95);--togc:#475569;
            --dd-bg:#ffffff;--dd-sep:rgba(0,0,0,.09);--dd-hover:rgba(0,0,0,.04);--dd-text:#1e293b;--dd-danger:#dc2626;
        }
        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;}
        body::before{content:'';position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%);pointer-events:none;}

        /* Sidebar */
        .dashboard-wrapper{display:flex;min-height:100vh;}
        .sidebar{width:260px;min-width:260px;background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;flex-direction:column;}
        .sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;text-align:center;}
        .sidebar-header p{color:var(--tmut)!important;margin:0;font-size:13px;}
        .sidebar-menu{list-style:none;margin:0;padding:12px 0;}
        .sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}

        /* Topbar */
        .main-content{flex:1;display:flex;flex-direction:column;min-width:0;background:transparent!important;}
        .topbar{background:var(--tb)!important;border-bottom:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:64px;position:relative;z-index:1000;overflow:visible;}
        .topbar-left h2{color:var(--tm)!important;font-size:20px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
        .topbar-right{display:flex;align-items:center;gap:12px;}
        #theme-toggle-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;box-shadow:0 2px 8px rgba(0,0,0,.15);flex-shrink:0;transition:all .25s;}
        #theme-toggle-btn:hover{color:#3b82f6!important;transform:scale(1.1);}

        /* USER TRIGGER */
        .user-info-wrap{position:relative;}
        .user-trigger{display:flex;align-items:center;gap:9px;padding:4px 10px 4px 4px;border-radius:10px;cursor:pointer;transition:background .18s;user-select:none;}
        .user-trigger:hover{background:var(--dd-hover);}
        .user-avatar{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
        .user-trigger-name{font-size:13px;font-weight:600;color:var(--tm);}
        .user-trigger-chevron{font-size:10px;color:var(--tmut);transition:transform .22s;margin-left:1px;}
        .user-trigger-chevron.open{transform:rotate(180deg);}

        /* DROPDOWN */
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;min-width:190px;background:var(--dd-bg);border:1px solid var(--dd-sep);border-radius:10px;padding:5px 0;box-shadow:0 10px 40px rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.12);backdrop-filter:blur(24px);opacity:0;pointer-events:none;transform:translateY(-5px) scale(.97);transform-origin:top right;transition:opacity .16s ease,transform .16s ease;z-index:600;}
        .user-dropdown.show{opacity:1;pointer-events:auto;transform:translateY(0) scale(1);}
        .dd-item{display:block;width:100%;padding:11px 20px;font-size:13.5px;font-weight:500;letter-spacing:.01em;color:var(--dd-text);background:none;border:none;text-align:left;cursor:pointer;text-decoration:none;font-family:'Outfit',sans-serif;transition:background .12s;white-space:nowrap;}
        .dd-item:hover{background:var(--dd-hover);}
        .dd-sep{height:1px;background:var(--dd-sep);margin:3px 0;}
        .dd-item-logout{color:var(--dd-danger)!important;font-weight:600;}
        .dd-item-logout:hover{background:rgba(239,68,68,.07)!important;}

        /* MODAL */
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

        /* Page content */
        .content-wrapper{padding:24px;display:flex;flex-direction:column;gap:16px;}
        .card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,.10);overflow:hidden;}
        .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:8px!important;border:none!important;color:#fff!important;font-weight:500;font-size:14px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
        .btn-back{background:var(--trodd);color:var(--tsub)!important;border:1px solid var(--gb)!important;}
        .btn-success{background:linear-gradient(135deg,#10b981,#059669)!important;box-shadow:0 4px 12px rgba(16,185,129,.3);}
        .btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706)!important;box-shadow:0 4px 12px rgba(245,158,11,.3);}
        .btn-edit{background:linear-gradient(135deg,#f59e0b,#d97706)!important;margin-left:auto;}
        .card-header-glow{background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:white;padding:20px 24px;border-bottom:1px solid var(--gb);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
        .card-header-glow h2{margin:0;text-shadow:0 0 10px rgba(0,0,0,0.3);font-size:22px;}
        .card-header-glow span{padding:6px 14px;background:rgba(255,255,255,0.2);border-radius:20px;font-size:13px;font-weight:500;}
        .card-body{padding:24px;}
        .meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:24px;padding:18px;background:var(--treven);border-radius:12px;border:1px solid var(--gb);}
        .meta-label{color:var(--tmut);font-size:13px;margin:0;font-weight:500;}
        .meta-value{color:var(--tm);font-weight:600;margin:6px 0 0 0;font-size:15px;}
        .revision-box{background:rgba(239,68,68,0.1);border:1px solid #ef4444;border-left:5px solid #ef4444;padding:20px;border-radius:12px;margin-bottom:24px;animation:pulse-border 2s infinite;}
        @keyframes pulse-border{0%{border-color:#ef4444;}50%{border-color:transparent;}100%{border-color:#ef4444;}}
        .section-box{padding:20px;border-radius:12px;margin-bottom:24px;background:var(--trodd);border:1px solid var(--gb);}
        .section-box:last-child{margin-bottom:0;}
        .section-blue{background:rgba(59,130,246,0.05);border-left:4px solid #3b82f6;}
        .section-green{background:rgba(16,185,129,0.05);border-left:4px solid #10b981;}
        .section-violet{background:rgba(139,92,246,0.05);border-left:4px solid #8b5cf6;}
        .section-title{color:var(--tm);margin-top:0;margin-bottom:14px;display:flex;align-items:center;gap:8px;font-size:18px;font-weight:600;}
        .section-box p{color:var(--tsub);line-height:1.6;margin:0;font-size:15px;}
        .section-box pre{font-family:'Outfit',sans-serif;white-space:pre-wrap;margin:0;line-height:1.6;font-size:15px;color:var(--tsub);}
    </style>
</head>
<body class="dashboard-page">
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width:220px;">
            <p>SOP Digital System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse_sop.php" class="active"><i class="fas fa-file-alt"></i><span>Daftar SOP</span></a></li>
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

                <!-- USER DROPDOWN -->
                <div class="user-info-wrap">
                    <div class="user-trigger" id="userTrigger">
                        <div class="user-avatar" id="topbarAvatar"><?php echo $cur_init; ?></div>
                        <span class="user-trigger-name" id="topbarNama"><?php echo htmlspecialchars($cur_nama); ?></span>
                        <i class="fas fa-chevron-down user-trigger-chevron" id="userChevron"></i>
                    </div>
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
            <div style="display:flex;gap:10px;align-items:center;">
                <a href="browse_sop.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <a href="print_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-success"><i class="fas fa-print"></i> Cetak</a>
                <?php if ($sop['status'] == 'Revisi'): ?>
                    <a href="edit_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Perbaiki Sekarang</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header-glow">
                    <h2><?php echo htmlspecialchars($sop['judul']); ?></h2>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <span style="background:rgba(255,255,255,0.1);"><?php echo strtoupper($sop['status']); ?></span>
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
                        <h3 style="color:#ef4444;margin-top:0;font-size:18px;display:flex;align-items:center;gap:10px;">
                            <i class="fas fa-exclamation-circle"></i> Catatan Revisi Admin
                        </h3>
                        <p style="color:var(--tm);font-style:italic;line-height:1.6;margin-bottom:15px;">
                            "<?php echo nl2br(htmlspecialchars($sop['catatan_admin'])); ?>"
                        </p>
                        <a href="edit_sop.php?id=<?php echo $sop['id']; ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-tools"></i> Klik untuk Memperbaiki Dokumen
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($sop['deskripsi'])): ?>
                    <div class="section-box section-blue">
                        <h3 class="section-title"><i class="fas fa-info-circle" style="color:#3b82f6;"></i> Deskripsi</h3>
                        <p><?php echo nl2br(htmlspecialchars($sop['deskripsi'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="section-box section-green">
                        <h3 class="section-title"><i class="fas fa-tasks" style="color:#10b981;"></i> Langkah-langkah Kerja</h3>
                        <pre><?php echo htmlspecialchars($sop['langkah_kerja']); ?></pre>
                    </div>

                    <?php if (!empty($sop['file_attachment'])): ?>
                    <div class="section-box section-violet">
                        <h3 class="section-title"><i class="fas fa-paperclip" style="color:#8b5cf6;"></i> File Lampiran</h3>
                        <p style="margin-bottom:12px;"><strong>File:</strong> <?php echo htmlspecialchars($sop['file_attachment']); ?></p>
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
                <div class="mf-group"><label>Nama Lengkap</label><div class="mf-wrap"><i class="fas fa-id-card mf-icon"></i><input type="text" name="nama_lengkap" id="editNama" value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required></div></div>
                <div class="mf-group"><label>Email <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label><div class="mf-wrap"><i class="fas fa-envelope mf-icon"></i><input type="email" name="email" id="editEmail" value="<?php echo htmlspecialchars($cur_email); ?>" placeholder="email@sinergi.co.id" required></div></div>
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
                <div class="mf-group"><label>Password Lama</label><div class="mf-wrap"><i class="fas fa-lock mf-icon"></i><input type="password" name="old_password" id="oldPass" placeholder="Password saat ini"><i class="fas fa-eye mf-eye" data-target="oldPass"></i></div></div>
                <div class="mf-group"><label>Password Baru</label><div class="mf-wrap"><i class="fas fa-lock mf-icon"></i><input type="password" name="new_password" id="newPass" placeholder="Min. 6 karakter"><i class="fas fa-eye mf-eye" data-target="newPass"></i></div></div>
                <div class="mf-group"><label>Konfirmasi Password Baru</label><div class="mf-wrap"><i class="fas fa-lock mf-icon"></i><input type="password" name="conf_password" id="confPass" placeholder="Ulangi password baru"><i class="fas fa-eye mf-eye" data-target="confPass"></i></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="mf-btn-cancel" data-close="modalUbahPassword">Batal</button>
                <button type="submit" class="mf-btn-save" id="btnSavePassword"><i class="fas fa-key"></i> Ubah Password</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script src="../assets/js/page-transition.js"></script>
<script>
(function(){if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');})();
document.addEventListener('DOMContentLoaded',function(){
    var btn=document.getElementById('theme-toggle-btn'),icon=document.getElementById('theme-icon');
    function sync(){if(icon)icon.className=document.documentElement.getAttribute('data-theme')==='light'?'far fa-sun':'fas fa-moon';}
    sync();
    if(btn)btn.addEventListener('click',function(){var l=document.documentElement.getAttribute('data-theme')==='light';if(l){document.documentElement.removeAttribute('data-theme');localStorage.setItem('theme','dark');}else{document.documentElement.setAttribute('data-theme','light');localStorage.setItem('theme','light');}sync();});

    // dropdown
    var trigger=document.getElementById('userTrigger'),dropdown=document.getElementById('userDropdown'),chevron=document.getElementById('userChevron');
    trigger.addEventListener('click',function(e){e.stopPropagation();var o=dropdown.classList.toggle('show');chevron.classList.toggle('open',o);});
    document.addEventListener('click',function(e){if(!dropdown.contains(e.target)&&!trigger.contains(e.target)){dropdown.classList.remove('show');chevron.classList.remove('open');}});

    // modal
    function openModal(id){document.getElementById(id).classList.add('show');dropdown.classList.remove('show');chevron.classList.remove('open');var a=document.querySelector('#'+id+' .modal-alert');if(a)a.className='modal-alert';}
    function closeModal(id){document.getElementById(id).classList.remove('show');}
    document.getElementById('openEditProfil').addEventListener('click',function(){openModal('modalEditProfil');});
    document.getElementById('openUbahPassword').addEventListener('click',function(){openModal('modalUbahPassword');});
    document.querySelectorAll('[data-close]').forEach(function(el){el.addEventListener('click',function(){closeModal(this.getAttribute('data-close'));});});
    document.querySelectorAll('.modal-overlay').forEach(function(ov){ov.addEventListener('click',function(e){if(e.target===ov)closeModal(ov.id);});});
    document.querySelectorAll('.mf-eye').forEach(function(eye){eye.addEventListener('click',function(){var i=document.getElementById(this.dataset.target);if(!i)return;i.type=i.type==='password'?'text':'password';this.classList.toggle('fa-eye');this.classList.toggle('fa-eye-slash');});});

    function showAlert(boxId,msg,type){var el=document.getElementById(boxId);el.className='modal-alert '+type;el.innerHTML='<i class="fas '+(type==='success'?'fa-check-circle':'fa-exclamation-circle')+'"></i>&nbsp;'+msg;}

    document.getElementById('formEditProfil').addEventListener('submit',function(e){
        e.preventDefault();var sb=document.getElementById('btnSaveProfil');sb.disabled=true;sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)}).then(function(r){return r.json();}).then(function(res){
            showAlert('alertEditProfil',res.message,res.success?'success':'error');
            if(res.success){var init=res.nama.charAt(0).toUpperCase();var ta=document.getElementById('topbarAvatar');if(ta)ta.textContent=init;var tn=document.getElementById('topbarNama');if(tn)tn.textContent=res.nama;document.getElementById('editNama').value=res.nama;document.getElementById('editEmail').value=res.email;setTimeout(function(){closeModal('modalEditProfil');},1500);}
        }).catch(function(){showAlert('alertEditProfil','Kesalahan jaringan.','error');}).finally(function(){sb.disabled=false;sb.innerHTML='<i class="fas fa-save"></i> Simpan';});
    });

    document.getElementById('formUbahPassword').addEventListener('submit',function(e){
        e.preventDefault();var np=document.getElementById('newPass').value,cp=document.getElementById('confPass').value;
        if(np!==cp){showAlert('alertUbahPassword','Konfirmasi password tidak cocok.','error');return;}
        var sb=document.getElementById('btnSavePassword');sb.disabled=true;sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)}).then(function(r){return r.json();}).then(function(res){
            showAlert('alertUbahPassword',res.message,res.success?'success':'error');
            if(res.success){['oldPass','newPass','confPass'].forEach(function(id){document.getElementById(id).value='';});setTimeout(function(){closeModal('modalUbahPassword');},1500);}
        }).catch(function(){showAlert('alertUbahPassword','Kesalahan jaringan.','error');}).finally(function(){sb.disabled=false;sb.innerHTML='<i class="fas fa-key"></i> Ubah Password';});
    });
});
</script>
</body>
</html>