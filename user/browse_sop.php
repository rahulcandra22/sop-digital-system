<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
requireUser();

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
    $user_id=$getUserId=getUserId();
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

$user_id = getUserId();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $kid   = (int)$_POST['kategori_id'];
    $desk  = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    $lk    = mysqli_real_escape_string($conn, trim($_POST['langkah_kerja']));
    $st    = 'Review'; $cb = $user_id; $fa = '';

    // Validasi sisi server
    if (empty($judul) || empty($lk) || $kid == 0) { setFlashMessage('danger','Field wajib tidak boleh kosong!'); header('Location: browse_sop.php'); exit(); }
    if (strlen($judul) < 5) { setFlashMessage('danger','Judul minimal 5 karakter.'); header('Location: browse_sop.php'); exit(); }
    if (strlen($lk) < 20) { setFlashMessage('danger','Langkah kerja minimal 20 karakter.'); header('Location: browse_sop.php'); exit(); }

    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] == 0) {
        $dir = "../assets/uploads/";
        $ext = pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION);
        $fn  = time().'_'.uniqid().'.'.$ext;
        if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $dir.$fn)) $fa = $fn;
    }
    if (mysqli_query($conn, "INSERT INTO sop (judul,kategori_id,deskripsi,langkah_kerja,file_attachment,created_by,status) VALUES ('$judul',$kid,'$desk','$lk','$fa',$cb,'$st')"))
        setFlashMessage('success','SOP berhasil diajukan dan sedang menunggu Review Admin!');
    else setFlashMessage('danger','Gagal mengajukan SOP!');
    header('Location: browse_sop.php'); exit();
}

$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search          = isset($_GET['search'])   ? $_GET['search']   : '';
$where           = "WHERE (s.status = 'Disetujui' OR s.created_by = $user_id)";
if ($kategori_filter) $where .= " AND s.kategori_id = ".intval($kategori_filter);
if ($search) { $ss=mysqli_real_escape_string($conn,$search); $where.=" AND (s.judul LIKE '%$ss%' OR s.deskripsi LIKE '%$ss%' OR c.nama_kategori LIKE '%$ss%')"; }
$result     = mysqli_query($conn,"SELECT s.*,c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id $where ORDER BY s.created_at DESC");
$result_cat = mysqli_query($conn,"SELECT * FROM categories ORDER BY nama_kategori ASC");
$flash      = getFlashMessage();

$cur_nama  = getNamaLengkap();
$cur_email = $_SESSION['email'] ?? '';
$cur_init  = strtoupper(substr($cur_nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar SOP - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page-transition.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:#020617;--sb:rgba(15,23,42,.97);--tb:rgba(15,23,42,.87);--cb:rgba(30,41,59,.75);
            --gb:rgba(255,255,255,.08);--tm:#f8fafc;--tmut:#94a3b8;--tsub:#cbd5e1;--ibg:rgba(0,0,0,.30);
            --mbg:#1e293b;--mbor:rgba(255,255,255,.10);--lf:brightness(0) invert(1);
            --lbg:rgba(239,68,68,.18);--lc:#fca5a5;--lbor:rgba(239,68,68,.30);
            --sl:#94a3b8;--sa:rgba(59,130,246,.12);--togbg:rgba(30,41,59,.80);--togc:#94a3b8;
            --dd-bg:rgba(18,26,48,.99);--dd-sep:rgba(255,255,255,.08);--dd-hover:rgba(255,255,255,.06);--dd-text:#e2e8f0;--dd-danger:#f87171;
        }
        [data-theme="light"] {
            --bg:#f0f4f8;--sb:rgba(255,255,255,.98);--tb:rgba(255,255,255,.96);--cb:rgba(255,255,255,.95);
            --gb:rgba(0,0,0,.09);--tm:#0f172a;--tmut:#64748b;--tsub:#334155;--ibg:rgba(255,255,255,.95);
            --mbg:#ffffff;--mbor:rgba(0,0,0,.10);--lf:none;
            --lbg:rgba(239,68,68,.07);--lc:#dc2626;--lbor:rgba(239,68,68,.18);
            --sl:#64748b;--sa:rgba(59,130,246,.08);--togbg:rgba(241,245,249,.95);--togc:#475569;
            --dd-bg:#ffffff;--dd-sep:rgba(0,0,0,.09);--dd-hover:rgba(0,0,0,.04);--dd-text:#1e293b;--dd-danger:#dc2626;
        }
        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;}
        body::before{content:'';position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%);pointer-events:none;}
        .sidebar{background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);}
        .sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;}
        .sidebar-header p{color:var(--tmut)!important;margin:0;font-size:12px;}
        .sidebar-menu{list-style:none;margin:0;padding:12px 0;}
        .sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}
        .main-content{background:transparent!important;}
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

        /* page content */
        .content-wrapper{padding:24px;}
        .card{background:var(--cb)!important;border:1px solid var(--gb)!important;border-radius:16px!important;box-shadow:0 4px 24px rgba(0,0,0,.10);margin-bottom:24px;}
        .card-header{padding:18px 22px;border-bottom:1px solid var(--gb);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;}
        .card-header h3{color:var(--tm)!important;margin:0;font-size:16px;font-weight:600;}
        .card-body{padding:22px;}
        .form-control{width:100%;padding:11px 14px;background:var(--ibg)!important;border:1px solid var(--gb)!important;border-radius:8px;color:var(--tm)!important;font-family:'Outfit',sans-serif;font-size:14px;transition:.3s;}
        .form-control:focus{outline:none;border-color:#3b82f6!important;box-shadow:0 0 0 3px rgba(59,130,246,.15);}
        .form-group{margin-bottom:15px;}
        .form-group label{color:var(--tsub)!important;margin-bottom:8px;display:block;font-weight:600;font-size:13px;}
        textarea.form-control{resize:vertical;}
        select.form-control option{background:var(--mbg);color:var(--tm);}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px!important;border:none!important;color:#fff!important;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
        .btn-success{background:linear-gradient(135deg,#10b981,#059669)!important;box-shadow:0 4px 12px rgba(16,185,129,.3);}
        .btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626)!important;box-shadow:0 4px 12px rgba(239,68,68,.3);}
        .btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;box-shadow:0 4px 12px rgba(59,130,246,.3);}
        .btn-sm{padding:6px 12px!important;font-size:12px!important;}
        .alert{border-radius:10px!important;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
        .alert-success{background:rgba(16,185,129,.12)!important;color:#059669!important;border:1px solid rgba(16,185,129,.25)!important;}
        .alert-danger{background:rgba(239,68,68,.12)!important;color:#dc2626!important;border:1px solid rgba(239,68,68,.25)!important;}
        .badge{padding:5px 12px;border-radius:20px;font-size:12px;background:var(--sa);color:#3b82f6;border:1px solid rgba(59,130,246,.3);}
        .s-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;float:right;}
        .sop-card{background:var(--cb);border:1px solid var(--gb);border-radius:12px;padding:20px;transition:all .3s ease;position:relative;overflow:hidden;}
        .sop-card:hover{border-color:#3b82f6;transform:translateY(-5px);box-shadow:0 10px 20px rgba(0,0,0,.1);}
        .sop-card h4{color:var(--tm);margin-bottom:10px;font-size:16px;font-weight:600;padding-right:60px;}
        .sop-card p{color:var(--tsub);font-size:13px;line-height:1.6;margin-bottom:15px;}
        .sop-meta{display:flex;justify-content:space-between;align-items:center;padding-top:15px;border-top:1px solid var(--gb);}
        .sop-meta small{color:var(--tmut);}
        .empty-state{text-align:center;padding:60px 20px;color:var(--tmut);}
        .empty-state h3{color:var(--tm);margin-top:10px;}

        /* SOP Modal */
        .sop-modal{display:none;position:fixed;z-index:8000;inset:0;background:rgba(0,0,0,.65);backdrop-filter:blur(6px);}
        .sop-modal-content{background:var(--mbg)!important;border:1px solid var(--mbor)!important;border-radius:16px;width:90%;max-width:700px;margin:3% auto;box-shadow:0 20px 50px rgba(0,0,0,.4);max-height:92vh;overflow-y:auto;}
        .sop-modal-header{padding:16px 22px;border-bottom:1px solid var(--mbor);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--mbg);z-index:2;border-radius:16px 16px 0 0;}
        .sop-modal-header h3{color:var(--tm)!important;margin:0;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;}
        .sop-modal-close{background:none;border:none;color:var(--tmut);font-size:22px;cursor:pointer;line-height:1;padding:0;}
        .sop-modal-close:hover{color:var(--tm);}
        .sop-modal-body{padding:22px;}
        .form-row-label{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;}
        .form-row-label .field-name{font-size:13px;font-weight:600;color:var(--tsub)!important;display:flex;align-items:center;gap:5px;}
        .form-row-label .field-name i{font-size:11px;color:#3b82f6;}
        .badge-req{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.25);}
        .badge-opt{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(148,163,184,.12);color:#94a3b8;border:1px solid rgba(148,163,184,.20);}
        .field-hint{font-size:11.5px;color:var(--tmut);margin-top:5px;line-height:1.5;display:flex;align-items:flex-start;gap:5px;}
        .field-hint i{font-size:11px;margin-top:2px;flex-shrink:0;}
        .field-hint.blue i{color:#60a5fa;}
        .field-hint.yellow i{color:#f59e0b;}
        /* NEW: Field error style */
        .field-error {
            color: #ef4444;
            font-size: 11px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .field-error i {
            font-size: 10px;
        }
        .warn-banner{display:flex;gap:11px;align-items:flex-start;background:rgba(245,158,11,.10);border:1px solid rgba(245,158,11,.28);border-radius:10px;padding:12px 14px;margin-bottom:18px;}
        .warn-banner .wi{color:#f59e0b;font-size:14px;margin-top:2px;flex-shrink:0;}
        .warn-banner .wt{font-size:12px;color:#fbbf24;line-height:1.65;}
        .warn-banner .wt strong{display:block;font-size:12.5px;color:#fcd34d;margin-bottom:3px;}
        .steps-guide{background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.18);border-radius:8px;padding:11px 14px;margin-bottom:8px;}
        .steps-guide p{color:var(--tmut)!important;font-size:12px;margin:0 0 6px;font-weight:600;}
        .steps-guide ol{margin:0;padding-left:16px;}
        .steps-guide ol li{color:#93c5fd;font-size:12px;line-height:1.7;}
        .confirm-box{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.22);border-radius:10px;padding:12px 14px;margin-bottom:16px;}
        .confirm-box label{display:flex;align-items:flex-start;gap:9px;cursor:pointer;margin:0;}
        .confirm-box input[type="checkbox"]{margin-top:3px;accent-color:#ef4444;flex-shrink:0;}
        .confirm-box .confirm-text{font-size:12px;color:#fca5a5;line-height:1.6;}
        .confirm-box .confirm-text strong{color:#f87171;}
        .form-actions{display:flex;gap:10px;}
    </style>
</head>
<body class="dashboard-page">
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width:220px">
            <p>SOP Digital System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="browse_sop.php" class="active"><i class="fas fa-file-alt"></i><span>Daftar SOP</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Kategori SOP</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-file-alt" style="color:#3b82f6"></i> Daftar SOP</h2>
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
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas <?php echo $flash['type']=='success'?'fa-check-circle':'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" style="display:grid;grid-template-columns:1fr 1fr auto;gap:15px;align-items:end">
                        <div class="form-group" style="margin:0">
                            <input type="text" name="search" class="form-control" placeholder="Cari Judul SOP..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group" style="margin:0">
                            <select name="kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                <?php mysqli_data_seek($result_cat,0); while($cat=mysqli_fetch_assoc($result_cat)): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($kategori_filter==$cat['id'])?'selected':''; ?>><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="display:flex;gap:10px">
                            <button type="submit" class="btn btn-info"><i class="fas fa-search"></i> Cari</button>
                            <a href="browse_sop.php" class="btn btn-danger"><i class="fas fa-redo"></i> Reset</a>
                            <button type="button" onclick="openSopModal('addModal')" class="btn btn-success"><i class="fas fa-plus"></i> Ajukan SOP Baru</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-file-alt"></i> Daftar Seluruh Dokumen SOP</h3></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">
                        <?php
                        $ss=['Draft'=>'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)','Review'=>'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)','Disetujui'=>'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)','Revisi'=>'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'];
                        while($row=mysqli_fetch_assoc($result)):
                            $s=trim($row['status']); $style=$ss[$s]??$ss['Revisi'];
                        ?>
                        <div class="sop-card">
                            <div style="margin-bottom:10px">
                                <span class="badge"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                                <span class="s-badge" style="<?php echo $style; ?>"><?php echo htmlspecialchars($s); ?></span>
                            </div>
                            <h4><?php echo htmlspecialchars($row['judul']); ?></h4>
                            <p><?php echo substr(htmlspecialchars($row['deskripsi']),0,100).'...'; ?></p>
                            <div class="sop-meta">
                                <small><i class="fas fa-calendar"></i> <?php echo date('d/m/Y',strtotime($row['created_at'])); ?></small>
                                <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Detail</a>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php if(mysqli_num_rows($result)==0): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox" style="font-size:64px;margin-bottom:20px;opacity:.3"></i>
                        <h3>Tidak ada SOP ditemukan</h3>
                        <p>Belum ada SOP yang Disetujui atau Anda belum pernah mengajukan SOP.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- SOP Modal -->
<div id="addModal" class="sop-modal">
    <div class="sop-modal-content">
        <div class="sop-modal-header">
            <h3><i class="fas fa-paper-plane"></i> Ajukan SOP Baru</h3>
            <button class="sop-modal-close" onclick="closeSopModal('addModal')">&times;</button>
        </div>
        <div class="sop-modal-body">
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Perhatian! Baca Sebelum Mengajukan SOP!</strong>
                    SOP yang Anda ajukan akan masuk ke notifikasi Admin dan harus disetujui sebelum dapat digunakan.
                    Pastikan dokumen belum tersedia di sistem, ditulis dengan lengkap dan akurat, serta sesuai dengan standar penulisan yang berlaku.
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formAjukanSOP">
                <input type="hidden" name="action" value="add">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-heading"></i> Judul SOP</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <input type="text" name="judul" id="add_judul" class="form-control" placeholder="Contoh: SOP Pengajuan Cuti" required>
                        <div class="field-error" id="error-judul" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-folder"></i> Kategori</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <select name="kategori_id" id="add_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php mysqli_data_seek($result_cat,0); while($cat=mysqli_fetch_assoc($result_cat)): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="field-error" id="error-kategori" style="display: none;">
                            <i class="fas fa-exclamation-circle"></i> <span></span>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-align-left"></i> Deskripsi Singkat</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <textarea name="deskripsi" class="form-control" rows="3" placeholder="Silahkan isi tujuan dan deskripsi dalam pembuatan SOP ini..."></textarea>
                </div>
                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-list-ol"></i> Langkah-langkah Kerja</span>
                        <span class="badge-req">Wajib di isi!</span>
                    </div>
                    <div class="steps-guide">
                        <p><i class="fas fa-book-open" style="margin-right:5px"></i> Panduan Penulisan:</p>
                        <ol>
                            <li>Tulis setiap langkah secara berurutan, satu baris per langkah</li>
                            <li>Awali dengan nomor atau kata kerja aktif</li>
                            <li>Gunakan bahasa yang jelas, singkat, dan mudah dipahami</li>
                        </ol>
                    </div>
                    <textarea name="langkah_kerja" id="add_langkah" class="form-control" rows="7" required placeholder="Silahkan isi langkah-langkah dalam pembuatan SOP ini..."></textarea>
                    <div class="field-error" id="error-langkah" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-paperclip"></i> File Lampiran</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <input type="file" name="file_attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg">
                    <div class="field-hint blue"><i class="fas fa-file-alt"></i><span>Format: PDF, Word, Excel, atau Gambar (JPG/PNG).</span></div>
                </div>
                <div class="confirm-box">
                    <label>
                        <input type="checkbox" id="confirmSOP">
                        <span class="confirm-text"><strong>Saya menyatakan SOP ini belum tersedia sistem, ditulis lengkap dan akurat serta siap untuk di-review oleh Admin.</strong>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" id="btnAjukan" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed"><i class="fas fa-paper-plane"></i> Ajukan SOP</button>
                    <button type="button" onclick="closeSopModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
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
                    <div class="mf-wrap"><i class="fas fa-id-card mf-icon"></i><input type="text" name="nama_lengkap" id="editNama" value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required></div>
                </div>
                <div class="mf-group">
                    <label>Email <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label>
                    <div class="mf-wrap"><i class="fas fa-envelope mf-icon"></i><input type="email" name="email" id="editEmail" value="<?php echo htmlspecialchars($cur_email); ?>" placeholder="email@sinergi.co.id" required></div>
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
    function sync(){icon.className=document.documentElement.getAttribute('data-theme')==='light'?'far fa-sun':'fas fa-moon';}
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

    // === VALIDATION FOR SOP SUBMISSION FORM ===
    const addJudul = document.getElementById('add_judul');
    const addKategori = document.getElementById('add_kategori');
    const addLangkah = document.getElementById('add_langkah');
    const errorJudul = document.getElementById('error-judul');
    const errorKategori = document.getElementById('error-kategori');
    const errorLangkah = document.getElementById('error-langkah');
    const chkSOP = document.getElementById('confirmSOP');
    const btnAjukan = document.getElementById('btnAjukan');
    const sopForm = document.getElementById('formAjukanSOP');

    function validateAddForm() {
        let isValid = true;

        // Judul
        const judulVal = addJudul.value.trim();
        if (judulVal.length < 5) {
            errorJudul.style.display = 'flex';
            errorJudul.querySelector('span').textContent = 'Judul minimal 5 karakter.';
            isValid = false;
        } else {
            errorJudul.style.display = 'none';
        }

        // Kategori
        if (!addKategori.value) {
            errorKategori.style.display = 'flex';
            errorKategori.querySelector('span').textContent = 'Pilih kategori yang cocok dengan judul SOP anda.';
            isValid = false;
        } else {
            errorKategori.style.display = 'none';
        }

        // Langkah kerja
        const langkahVal = addLangkah.value.trim();
        if (langkahVal.length < 20) {
            errorLangkah.style.display = 'flex';
            errorLangkah.querySelector('span').textContent = 'Langkah kerja minimal 20 karakter.';
            isValid = false;
        } else {
            errorLangkah.style.display = 'none';
        }

        return isValid;
    }

    function updateSubmitButton() {
        const valid = validateAddForm();
        const enabled = chkSOP.checked && valid;
        btnAjukan.disabled = !enabled;
        btnAjukan.style.opacity = enabled ? '1' : '.45';
        btnAjukan.style.cursor = enabled ? 'pointer' : 'not-allowed';
    }

    addJudul.addEventListener('input', updateSubmitButton);
    addKategori.addEventListener('change', updateSubmitButton);
    addLangkah.addEventListener('input', updateSubmitButton);
    chkSOP.addEventListener('change', updateSubmitButton);
    updateSubmitButton(); // initial state

    sopForm.addEventListener('submit', function(e) {
        if (!validateAddForm() || !chkSOP.checked) {
            e.preventDefault();
            alert('Harap periksa kembali: pastikan judul minimal 5 karakter, kategori dipilih, langkah kerja minimal 20 karakter, dan konfirmasi telah dicentang.');
            return false;
        }
    });

});
function openSopModal(id){document.getElementById(id).style.display='block';}
function closeSopModal(id){document.getElementById(id).style.display='none';}
window.onclick=function(e){var m=document.getElementById('addModal');if(e.target==m)m.style.display='none';};
</script>
</body>
</html>