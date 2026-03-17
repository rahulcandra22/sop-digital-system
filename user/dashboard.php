<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';
requireLogin();

// AJAX: Update Profil
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
    if ($upd->execute()) {
        $_SESSION['nama_lengkap']=$nama_lengkap; $_SESSION['email']=$email;
        echo json_encode(['success'=>true,'message'=>'Profil berhasil diperbarui!','nama'=>$nama_lengkap,'email'=>$email]);
    } else { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan perubahan.']); }
    exit;
}

// AJAX: Ubah Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    header('Content-Type: application/json');
    $user_id       = getUserId();
    $old_password  = $_POST['old_password'] ?? '';
    $new_password  = $_POST['new_password'] ?? '';
    $conf_password = $_POST['conf_password'] ?? '';
    if (empty($old_password)||empty($new_password)||empty($conf_password)) { echo json_encode(['success'=>false,'message'=>'Semua field wajib diisi.']); exit; }
    if (strlen($new_password)<6) { echo json_encode(['success'=>false,'message'=>'Password baru minimal 6 karakter.']); exit; }
    if ($new_password!==$conf_password) { echo json_encode(['success'=>false,'message'=>'Konfirmasi password tidak cocok.']); exit; }
    $sel = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $sel->bind_param("i",$user_id); $sel->execute(); $sel->bind_result($hash); $sel->fetch(); $sel->close();
    if (!password_verify($old_password,$hash)) { echo json_encode(['success'=>false,'message'=>'Password lama tidak sesuai.']); exit; }
    $new_hash = password_hash($new_password,PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $upd->bind_param("si",$new_hash,$user_id);
    if ($upd->execute()) { echo json_encode(['success'=>true,'message'=>'Password berhasil diubah!']); }
    else { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan password.']); }
    exit;
}

$user_id = getUserId();

$sql_notif    = "SELECT COUNT(*) as total FROM sop WHERE created_by = $user_id AND status = 'Revisi'";
$result_notif = mysqli_query($conn, $sql_notif);
$notif_count  = mysqli_fetch_assoc($result_notif)['total'];

$total_sop      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM sop"))['total'];
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as total FROM categories"))['total'];

// SOP milik user
$my_sop_total    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE created_by=$user_id"))['t'];
$my_sop_disetujui= mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE created_by=$user_id AND status='Disetujui'"))['t'];

$result_recent = mysqli_query($conn,"SELECT s.*, c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id WHERE s.created_by=$user_id ORDER BY s.created_at DESC LIMIT 6");

$result_cat = mysqli_query($conn,"SELECT c.*, COUNT(s.id) as jumlah_sop FROM categories c LEFT JOIN sop s ON c.id=s.kategori_id GROUP BY c.id ORDER BY jumlah_sop DESC, c.nama_kategori ASC");

$flash     = getFlashMessage();
$cur_nama  = getNamaLengkap();
$cur_email = $_SESSION['email'] ?? '';
$cur_init  = strtoupper(substr($cur_nama, 0, 1));

$hour = (int)date('H');
$greeting      = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : ($hour < 20 ? 'Selamat Sore' : 'Selamat Malam'));
$greeting_icon = $hour < 12 ? '🌅' : ($hour < 17 ? '☀️' : ($hour < 20 ? '🌇' : '🌙'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page-transition.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/page-transition.js"></script>
    <style>
        /* ═══ VARIABLES ═══ */
        :root {
            --bg:#020617; --sb:rgba(15,23,42,.97); --tb:rgba(15,23,42,.87);
            --gb:rgba(255,255,255,.08); --tm:#f8fafc; --tmut:#94a3b8; --tsub:#cbd5e1;
            --thbg:rgba(0,0,0,.35); --trodd:rgba(15,23,42,.55); --treven:rgba(15,23,42,.35);
            --trhov:rgba(59,130,246,.09); --tbor:rgba(255,255,255,.06); --ibg:rgba(0,0,0,.30);
            --mbg:#1e293b; --mbor:rgba(255,255,255,.10); --lf:brightness(0) invert(1);
            --sl:#94a3b8; --sa:rgba(59,130,246,.12); --togbg:rgba(30,41,59,.80); --togc:#94a3b8;
            --cb:rgba(22,33,55,.80);
            --dd-bg:rgba(18,26,48,.99); --dd-sep:rgba(255,255,255,.08);
            --dd-hover:rgba(255,255,255,.06); --dd-text:#e2e8f0; --dd-danger:#f87171;
        }
        [data-theme="light"] {
            --bg:#f0f4f8; --sb:rgba(255,255,255,.98); --tb:rgba(255,255,255,.96);
            --gb:rgba(0,0,0,.09); --tm:#0f172a; --tmut:#64748b; --tsub:#334155;
            --thbg:#e9eef5; --trodd:#ffffff; --treven:#f8fafc; --trhov:#eff6ff;
            --tbor:rgba(0,0,0,.07); --ibg:rgba(255,255,255,.95); --mbg:#ffffff; --mbor:rgba(0,0,0,.10);
            --lf:none; --sl:#64748b; --sa:rgba(59,130,246,.08); --togbg:rgba(241,245,249,.95); --togc:#475569;
            --cb:rgba(255,255,255,.92);
            --dd-bg:#ffffff; --dd-sep:rgba(0,0,0,.09);
            --dd-hover:rgba(0,0,0,.04); --dd-text:#1e293b; --dd-danger:#dc2626;
        }

        *,*::before,*::after{box-sizing:border-box;}
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;scroll-behavior:smooth;}
        body::before{content:'';position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%),radial-gradient(circle at 85% 20%,rgba(139,92,246,.06),transparent 30%);pointer-events:none;}

        /* ═══ SIDEBAR ═══ */
        .sidebar{background:var(--sb)!important;border-right:1px solid var(--gb)!important;backdrop-filter:blur(12px);}
        .sidebar-header{border-bottom:1px solid var(--gb)!important;padding:20px;}
        .sidebar-header p{color:var(--tmut)!important;margin:0;font-size:12px;}
        .sidebar-menu{list-style:none;margin:0;padding:12px 0;}
        .sidebar-menu li a{display:flex;align-items:center;gap:10px;padding:12px 20px;color:var(--sl)!important;text-decoration:none;border-left:3px solid transparent;font-size:14px;font-weight:500;transition:.25s;}
        .sidebar-menu li a:hover,.sidebar-menu li a.active{background:var(--sa)!important;color:#3b82f6!important;border-left-color:#3b82f6;}

        /* ═══ TOPBAR ═══ */
        .main-content{background:transparent!important;}
        .topbar{background:var(--tb)!important;border-bottom:1px solid var(--gb)!important;backdrop-filter:blur(12px);display:flex;align-items:center;justify-content:space-between;padding:0 24px;height:64px;position:relative;z-index:1000;overflow:visible;}
        .topbar-left h2{color:var(--tm)!important;font-size:20px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px;}
        .topbar-right{display:flex;align-items:center;gap:12px;}

        @keyframes ring-pulse{0%{transform:scale(1);box-shadow:0 0 0 0 rgba(239,68,68,.7);}70%{transform:scale(1.1);box-shadow:0 0 0 10px rgba(239,68,68,0);}100%{transform:scale(1);box-shadow:0 0 0 0 rgba(239,68,68,0);}}
        .notif-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg);border:1px solid var(--gb);color:var(--togc);display:flex;align-items:center;justify-content:center;font-size:17px;transition:.3s;position:relative;text-decoration:none;}
        .notif-btn:hover{color:#3b82f6;transform:translateY(-2px);}
        .notif-badge{position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid var(--tb);animation:ring-pulse 2s infinite;}

        #theme-toggle-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;transition:all .25s;}
        #theme-toggle-btn:hover{color:#3b82f6!important;transform:scale(1.1);}

        /* ═══ DROPDOWN ═══ */
        .user-info-wrap{position:relative;}
        .user-trigger{display:flex;align-items:center;gap:9px;padding:4px 10px 4px 4px;border-radius:10px;cursor:pointer;transition:background .18s;user-select:none;}
        .user-trigger:hover{background:var(--dd-hover);}
        .user-avatar{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
        .user-trigger-name{font-size:13px;font-weight:600;color:var(--tm);}
        .user-trigger-chevron{font-size:10px;color:var(--tmut);transition:transform .22s;margin-left:1px;}
        .user-trigger-chevron.open{transform:rotate(180deg);}
        .user-dropdown{position:absolute;top:calc(100% + 8px);right:0;min-width:190px;background:var(--dd-bg);border:1px solid var(--dd-sep);border-radius:10px;padding:5px 0;box-shadow:0 10px 40px rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.12);backdrop-filter:blur(24px);opacity:0;pointer-events:none;transform:translateY(-5px) scale(.97);transform-origin:top right;transition:opacity .16s ease,transform .16s ease;z-index:600;}
        .user-dropdown.show{opacity:1;pointer-events:auto;transform:translateY(0) scale(1);}
        .dd-item{display:block;width:100%;padding:11px 20px;font-size:13.5px;font-weight:500;color:var(--dd-text);background:none;border:none;text-align:left;cursor:pointer;text-decoration:none;font-family:'Outfit',sans-serif;transition:background .12s;white-space:nowrap;}
        .dd-item:hover{background:var(--dd-hover);}
        .dd-sep{height:1px;background:var(--dd-sep);margin:3px 0;}
        .dd-item-logout{color:var(--dd-danger)!important;font-weight:600;}
        .dd-item-logout:hover{background:rgba(239,68,68,.07)!important;}

        /* ═══ MODALS ═══ */
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
        .mf-wrap input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
        .mf-eye{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--tmut);font-size:13px;cursor:pointer;z-index:2;}
        .mf-eye:hover{color:#60a5fa;}
        .modal-footer{display:flex;gap:8px;justify-content:flex-end;padding:0 20px 18px;}
        .mf-btn-cancel{padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;background:none;border:1px solid var(--gb);color:var(--tmut);transition:.18s;}
        .mf-btn-cancel:hover{border-color:#ef4444;color:#ef4444;}
        .mf-btn-save{padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Outfit',sans-serif;background:linear-gradient(90deg,#3b82f6,#8b5cf6);color:#fff;border:none;box-shadow:0 4px 12px rgba(59,130,246,.28);transition:.2s;display:flex;align-items:center;gap:6px;}
        .mf-btn-save:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(139,92,246,.36);}
        .mf-btn-save:disabled{opacity:.65;cursor:not-allowed;transform:none;}

        /* ═══ LAYOUT ═══ */
        .content-wrapper{padding:24px;}

        /* ═══ GREETING BANNER ═══ */
        .greeting-banner{
            background:linear-gradient(135deg,rgba(59,130,246,.18) 0%,rgba(139,92,246,.16) 60%,rgba(15,23,42,.85) 100%);
            border:1px solid rgba(59,130,246,.25);
            border-radius:20px;padding:24px 28px;margin-bottom:24px;
            display:flex;align-items:center;justify-content:space-between;gap:20px;
            position:relative;overflow:hidden;
        }
        .greeting-banner::before{content:'';position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.15),transparent 70%);pointer-events:none;}
        .greeting-banner::after{content:'';position:absolute;bottom:-30px;left:35%;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,.10),transparent 70%);pointer-events:none;}
        [data-theme="light"] .greeting-banner{background:linear-gradient(135deg,rgba(59,130,246,.10) 0%,rgba(139,92,246,.08) 60%,rgba(240,244,248,.9) 100%);border-color:rgba(59,130,246,.18);}
        .greeting-left{flex:1;position:relative;z-index:1;}
        .greeting-hi{font-size:13px;font-weight:500;color:var(--tmut);margin-bottom:4px;}
        .greeting-name{font-size:26px;font-weight:800;color:#cbd5e1;margin-bottom:8px;line-height:1.2;}
        .greeting-name span{background:linear-gradient(90deg,#3b82f6,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .greeting-sub{font-size:13px;color:var(--tmut);display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
        .greeting-sub i{color:#3b82f6;}
        .greeting-right{text-align:right;flex-shrink:0;position:relative;z-index:1;}
        .greeting-clock{font-size:42px;font-weight:800;color:var(--tm);letter-spacing:-2px;line-height:1;font-variant-numeric:tabular-nums;}
        .greeting-date{font-size:12px;color:var(--tmut);margin-top:5px;}

        /* ═══ STAT CARDS ═══ */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:24px;}
        .stat-card{
            background:rgba(22,33,62,.85) !important;
            border:1px solid rgba(255,255,255,.09) !important;
            border-radius:16px !important;
            padding:20px;display:flex;align-items:center;gap:16px;
            transition:.3s;position:relative;overflow:hidden;
            box-shadow:0 4px 20px rgba(0,0,0,.25);
        }
        [data-theme="light"] .stat-card{
            background:rgba(255,255,255,.92) !important;
            border:1px solid rgba(0,0,0,.08) !important;
            box-shadow:0 4px 16px rgba(0,0,0,.07);
        }
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 14px 32px rgba(0,0,0,.22);}
        [data-theme="light"] .stat-card:hover{box-shadow:0 10px 24px rgba(0,0,0,.10);}
        .stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
        .stat-icon.blue{background:rgba(59,130,246,.20);color:#60a5fa;}
        .stat-icon.green{background:rgba(16,185,129,.20);color:#34d399;}
        .stat-icon.purple{background:rgba(139,92,246,.20);color:#a78bfa;}
        .stat-icon.teal{background:rgba(20,184,166,.20);color:#2dd4bf;}
        [data-theme="light"] .stat-icon.blue{background:rgba(59,130,246,.12);color:#2563eb;}
        [data-theme="light"] .stat-icon.green{background:rgba(16,185,129,.12);color:#059669;}
        [data-theme="light"] .stat-icon.purple{background:rgba(139,92,246,.12);color:#7c3aed;}
        [data-theme="light"] .stat-icon.teal{background:rgba(20,184,166,.12);color:#0d9488;}
        .stat-info h3{color:var(--tm)!important;font-size:28px;font-weight:800;margin:0 0 2px;line-height:1;font-variant-numeric:tabular-nums;}
        .stat-info p{color:var(--tmut)!important;margin:0;font-size:13px;}

        /* ═══ CARD BASE ═══ */
        .card{
            background:rgba(22,33,62,.80) !important;
            border:1px solid rgba(255,255,255,.08) !important;
            border-radius:16px !important;
            box-shadow:0 4px 20px rgba(0,0,0,.18);
            margin-bottom:24px;overflow:hidden;
        }
        [data-theme="light"] .card{
            background:rgba(255,255,255,.92) !important;
            border:1px solid rgba(0,0,0,.08) !important;
            box-shadow:0 4px 16px rgba(0,0,0,.06);
        }
        .card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid rgba(255,255,255,.07);}
        [data-theme="light"] .card-header{border-bottom-color:rgba(0,0,0,.07);}
        .card-header h3{color:var(--tm)!important;margin:0;font-size:16px;font-weight:600;display:flex;align-items:center;gap:8px;}
        .card-body{padding:22px;}

        /* ═══ KATEGORI CARDS ═══ */
        .cat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;}
        .cat-card-item{
            background:rgba(30,42,70,.80);
            border:1px solid rgba(255,255,255,.08);
            padding:18px 20px;border-radius:14px;
            text-decoration:none;display:block;
            transition:all .3s ease;position:relative;overflow:hidden;
        }
        [data-theme="light"] .cat-card-item{background:rgba(248,250,252,.95);border-color:rgba(0,0,0,.08);}
        .cat-card-item::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#3b82f6,#8b5cf6);opacity:0;transition:.3s;}
        .cat-card-item:hover{transform:translateY(-4px);border-color:rgba(59,130,246,.4);box-shadow:0 10px 28px rgba(59,130,246,.15);}
        .cat-card-item:hover::before{opacity:1;}
        .cat-icon{width:38px;height:38px;border-radius:10px;background:rgba(59,130,246,.18);border:1px solid rgba(59,130,246,.25);display:flex;align-items:center;justify-content:center;font-size:16px;color:#60a5fa;margin-bottom:12px;transition:.3s;}
        [data-theme="light"] .cat-icon{background:rgba(59,130,246,.10);color:#2563eb;}
        .cat-card-item:hover .cat-icon{background:rgba(59,130,246,.28);transform:scale(1.05);}
        .cat-card-name{font-weight:700;font-size:14px;color:var(--tm);margin-bottom:5px;}
        .cat-card-count{font-size:12px;color:var(--tmut);display:flex;align-items:center;gap:5px;}

        /* ═══ TABLE ═══ */
        .table-responsive{overflow-x:auto;border-radius:10px;overflow:hidden;}
        table{width:100%!important;border-collapse:collapse!important;}
        thead tr{background:rgba(59,130,246,.12)!important;}
        [data-theme="light"] thead tr{background:rgba(59,130,246,.07)!important;}
        thead th{color:#94a3b8!important;padding:12px 16px!important;font-size:.72rem!important;font-weight:600!important;text-transform:uppercase!important;letter-spacing:.6px!important;border:none!important;text-align:left;}
        tbody tr:nth-child(odd) td{background:rgba(30,42,70,.45)!important;}
        tbody tr:nth-child(even) td{background:rgba(22,33,55,.25)!important;}
        [data-theme="light"] tbody tr:nth-child(odd) td{background:rgba(255,255,255,.95)!important;}
        [data-theme="light"] tbody tr:nth-child(even) td{background:rgba(248,250,252,.9)!important;}
        tbody tr:hover td{background:rgba(59,130,246,.09)!important;}
        tbody td{color:var(--tsub)!important;padding:13px 16px!important;border-bottom:1px solid rgba(255,255,255,.05)!important;border-top:none!important;border-left:none!important;border-right:none!important;vertical-align:middle;}
        [data-theme="light"] tbody td{border-bottom-color:rgba(0,0,0,.05)!important;}
        tbody tr:last-child td{border-bottom:none!important;}

        /* ═══ SOP TITLE CELL ═══ */
        .sop-title-cell{display:flex;flex-direction:column;gap:2px;}
        .sop-title-text{font-weight:600;color:var(--tm)!important;font-size:13.5px;line-height:1.35;}
        .sop-title-date{font-size:11px;color:var(--tmut)!important;}

        /* ═══ BADGES ═══ */
        .badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;white-space:nowrap;}

        /* ═══ BUTTONS ═══ */
        .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px!important;border:none!important;color:#fff!important;font-weight:600;font-size:12px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
        .btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;box-shadow:0 3px 10px rgba(59,130,246,.3);}
        .btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706)!important;}
        .btn-sm{padding:6px 12px!important;font-size:12px!important;}

        /* ═══ ALERTS ═══ */
        .alert{border-radius:10px!important;padding:12px 18px;margin-bottom:20px;font-size:14px;display:flex;align-items:center;gap:10px;}
        .alert-success{background:rgba(16,185,129,.12)!important;color:#059669!important;border:1px solid rgba(16,185,129,.25)!important;}

        /* ═══ REVISI ALERT ═══ */
        .revisi-alert{
            background:rgba(239,68,68,.10);
            border:1px solid rgba(239,68,68,.28);
            border-radius:14px;padding:14px 18px;
            display:flex;align-items:center;gap:12px;
            margin-bottom:24px;
        }
        [data-theme="light"] .revisi-alert{background:rgba(239,68,68,.06);}
        .revisi-alert i{color:#f87171;font-size:18px;flex-shrink:0;}
        .revisi-alert-text strong{display:block;font-size:13.5px;font-weight:700;color:#f87171;margin-bottom:2px;}
        .revisi-alert-text span{font-size:12.5px;color:var(--tmut);}

        @media(max-width:768px){.stats-grid{grid-template-columns:1fr 1fr;}}

        /* ═══ FORCE OVERRIDE TABLE HEADER ═══ */
#status-sop table thead tr,
#status-sop table thead tr th {
    background: #ffffff !important;
    border-bottom: 1px solid #fbfbfb !important;
}
#status-sop table thead th {
    color: #7dd3fc !important;
    font-weight: 700 !important;
}
[data-theme="light"] #status-sop table thead tr,
[data-theme="light"] #status-sop table thead tr th {
    background: rgb(255, 255, 255) !important;
    border-bottom: 1px solid #ffffff !important;
}
[data-theme="light"] #status-sop table thead th {
    color: #2563eb !important;
}
#status-sop table tbody tr:nth-child(odd) td {
    background: rgba(30,50,90,.35) !important;
}
#status-sop table tbody tr:nth-child(even) td {
    background: rgba(20,35,65,.20) !important;
}
[data-theme="light"] #status-sop table tbody tr:nth-child(odd) td {
    background: rgba(239,246,255,.80) !important;
}
[data-theme="light"] #status-sop table tbody tr:nth-child(even) td {
    background: rgba(255,255,255,.95) !important;
}
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
                    <i class="fas fa-info-circle"></i> <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>

            <!-- GREETING BANNER -->
            <div class="greeting-banner">
                <div class="greeting-left">
                    <div class="greeting-hi"><?php echo $greeting_icon; ?> <?php echo $greeting; ?>,</div>
                    <div class="greeting-name">Selamat datang, <span><?php echo htmlspecialchars(explode(' ', $cur_nama)[0]); ?>!</span></div>
                    <div class="greeting-sub">
                        <i class="fas fa-user-circle"></i> User
                        &nbsp;·&nbsp;
                        <i class="fas fa-database"></i> <?php echo $total_sop; ?> SOP tersedia
                        <?php if ($notif_count > 0): ?>
                        &nbsp;·&nbsp;
                        <i class="fas fa-exclamation-circle" style="color:#f59e0b"></i>
                        <span style="color:#fbbf24;font-weight:600"><?php echo $notif_count; ?> dokumen perlu direvisi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="greeting-right">
                    <div class="greeting-clock" id="liveClock"><?php echo date('H:i:s'); ?></div>
                    <div class="greeting-date" id="liveDate"><?php echo date('d M Y'); ?></div>
                </div>
            </div>

            <!-- REVISI ALERT (only if ada revisi) -->
            <?php if ($notif_count > 0): ?>
            <div class="revisi-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="revisi-alert-text">
                    <strong><?php echo $notif_count; ?> Dokumen Memerlukan Revisi</strong>
                    <span>Silakan periksa dan perbaiki dokumen SOP Anda di tabel Status SOP di bawah ini.</span>
                </div>
                <a href="#status-sop" class="btn btn-sm" style="background:linear-gradient(135deg,#ef4444,#dc2626)!important;margin-left:auto;flex-shrink:0">
                    <i class="fas fa-arrow-down"></i> Lihat
                </a>
            </div>
            <?php endif; ?>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_sop; ?></h3>
                        <p>Total SOP Sistem</p>
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
                    <div class="stat-icon teal"><i class="fas fa-file-check" style="font-size:20px"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $my_sop_total; ?></h3>
                        <p>SOP Saya</p>
                    </div>
                </div>
            </div>

            <!-- KATEGORI -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-folder-open" style="color:#60a5fa"></i> Kategori SOP</h3>
                    <a href="kategori.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="cat-grid">
                        <?php mysqli_data_seek($result_cat, 0); while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                        <a href="browse_sop.php?kategori=<?php echo $cat['id']; ?>" class="cat-card-item">
                            <div class="cat-icon"><i class="fas fa-folder"></i></div>
                            <div class="cat-card-name"><?php echo htmlspecialchars($cat['nama_kategori']); ?></div>
                            <div class="cat-card-count"><i class="fas fa-file-alt"></i><?php echo $cat['jumlah_sop']; ?> SOP</div>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- STATUS SOP SAYA -->
            <div class="card" id="status-sop">
                <div class="card-header">
                    <h3><i class="fas fa-history" style="color:#10b981"></i> Status SOP Saya</h3>
                    <a href="browse_sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Lihat SOP</a>
                </div>
                <div class="card-body" style="padding:0">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Judul SOP</th>
                                    <th width="15%">Kategori</th>
                                    <th width="12%">Status</th>
                                    <th width="11%">Tanggal</th>
                                    <th width="18%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no=1;
                                $ss=['Draft'=>'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)','Review'=>'background:rgba(245,158,11,.15);color:#f59e0b;border:1px solid rgba(245,158,11,.3)','Disetujui'=>'background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.3)','Revisi'=>'background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.3)'];
                                $dot=['Draft'=>'gray','Review'=>'orange','Disetujui'=>'green','Revisi'=>'red'];
                                while($row=mysqli_fetch_assoc($result_recent)): $status=$row['status'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <div class="sop-title-cell">
                                            <span class="sop-title-text"><?php echo htmlspecialchars(mb_substr($row['judul'],0,45).(mb_strlen($row['judul'])>45?'…':'')); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td>
                                    <td><span class="badge" style="<?php echo $ss[$status]??$ss['Draft']; ?>"><?php echo $status; ?></span></td>
                                    <td style="font-size:12px;color:var(--tmut)!important;white-space:nowrap"><?php echo date('d/m/Y',strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div style="display:flex;gap:5px;">
                                            <a href="view_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm" title="Lihat"><i class="fas fa-eye"></i></a>
                                            <?php if($status=='Revisi'): ?>
                                            <a href="edit_sop.php?id=<?php echo $row['id']; ?>" class="btn btn-sm" title="Perbaiki" style="background:linear-gradient(135deg,#ef4444,#dc2626)!important">
                                                <i class="fas fa-edit"></i> Perbaiki
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result_recent)==0): ?>
                                <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--tmut)">
                                    <i class="fas fa-file-alt" style="font-size:24px;display:block;margin-bottom:8px;opacity:.3"></i>
                                    Belum ada SOP yang dibuat.
                                </td></tr>
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
                        <input type="text" name="nama_lengkap" id="editNama" value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required>
                    </div>
                </div>
                <div class="mf-group">
                    <label>Email <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label>
                    <div class="mf-wrap">
                        <i class="fas fa-envelope mf-icon"></i>
                        <input type="email" name="email" id="editEmail" value="<?php echo htmlspecialchars($cur_email); ?>" placeholder="email@sinergi.co.id" required>
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
(function(){ if(localStorage.getItem('theme')==='light') document.documentElement.setAttribute('data-theme','light'); })();

document.addEventListener('DOMContentLoaded', function(){

    // THEME
    var btn=document.getElementById('theme-toggle-btn'), icon=document.getElementById('theme-icon');
    function syncIcon(){ if(icon) icon.className = document.documentElement.getAttribute('data-theme')==='light' ? 'far fa-sun' : 'fas fa-moon'; }
    syncIcon();
    if(btn) btn.addEventListener('click', function(){
        var l = document.documentElement.getAttribute('data-theme')==='light';
        if(l){ document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
        else { document.documentElement.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
        syncIcon();
    });

    // NOTIFIKASI
    var nb = document.getElementById('notif-btn');
    if(nb) nb.addEventListener('click', function(e){
        if(parseInt(this.getAttribute('data-count'))===0){
            e.preventDefault();
            Swal.fire({
                title:'Tidak Ada Notifikasi', text:'Semua dokumen aman!', icon:'info',
                confirmButtonColor:'#3b82f6',
                background: document.documentElement.getAttribute('data-theme')==='light' ? '#ffffff' : '#1e293b',
                color: document.documentElement.getAttribute('data-theme')==='light' ? '#0f172a' : '#f8fafc'
            });
        }
    });

    // DROPDOWN
    var trigger=document.getElementById('userTrigger'), dropdown=document.getElementById('userDropdown'), chevron=document.getElementById('userChevron');
    trigger.addEventListener('click', function(e){ e.stopPropagation(); var o=dropdown.classList.toggle('show'); chevron.classList.toggle('open',o); });
    document.addEventListener('click', function(e){ if(!dropdown.contains(e.target)&&!trigger.contains(e.target)){ dropdown.classList.remove('show'); chevron.classList.remove('open'); } });

    // MODAL HELPERS
    function openModal(id){ document.getElementById(id).classList.add('show'); dropdown.classList.remove('show'); chevron.classList.remove('open'); var al=document.querySelector('#'+id+' .modal-alert'); if(al) al.className='modal-alert'; }
    function closeModal(id){ document.getElementById(id).classList.remove('show'); }

    document.getElementById('openEditProfil').addEventListener('click', function(){ openModal('modalEditProfil'); });
    document.getElementById('openUbahPassword').addEventListener('click', function(){ openModal('modalUbahPassword'); });
    document.querySelectorAll('[data-close]').forEach(function(el){ el.addEventListener('click', function(){ closeModal(this.getAttribute('data-close')); }); });
    document.querySelectorAll('.modal-overlay').forEach(function(ov){ ov.addEventListener('click', function(e){ if(e.target===ov) closeModal(ov.id); }); });

    // EYE TOGGLE
    document.querySelectorAll('.mf-eye').forEach(function(eye){
        eye.addEventListener('click', function(){ var i=document.getElementById(this.dataset.target); if(!i) return; i.type=i.type==='password'?'text':'password'; this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash'); });
    });

    // ALERT HELPER
    function showAlert(id,msg,type){ var el=document.getElementById(id); el.className='modal-alert '+type; el.innerHTML='<i class="fas '+(type==='success'?'fa-check-circle':'fa-exclamation-circle')+'"></i>&nbsp;'+msg; }

    // EDIT PROFIL
    document.getElementById('formEditProfil').addEventListener('submit', function(e){
        e.preventDefault(); var sb=document.getElementById('btnSaveProfil'); sb.disabled=true; sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(res=>{
            showAlert('alertEditProfil',res.message,res.success?'success':'error');
            if(res.success){ var init=res.nama.charAt(0).toUpperCase(); var ta=document.getElementById('topbarAvatar'); if(ta)ta.textContent=init; var tn=document.getElementById('topbarNama'); if(tn)tn.textContent=res.nama; document.getElementById('editNama').value=res.nama; document.getElementById('editEmail').value=res.email; setTimeout(()=>closeModal('modalEditProfil'),1500); }
        }).catch(()=>showAlert('alertEditProfil','Kesalahan jaringan.','error')).finally(()=>{ sb.disabled=false; sb.innerHTML='<i class="fas fa-save"></i> Simpan'; });
    });

    // UBAH PASSWORD
    document.getElementById('formUbahPassword').addEventListener('submit', function(e){
        e.preventDefault(); var np=document.getElementById('newPass').value, cp=document.getElementById('confPass').value;
        if(np!==cp){ showAlert('alertUbahPassword','Konfirmasi password tidak cocok.','error'); return; }
        var sb=document.getElementById('btnSavePassword'); sb.disabled=true; sb.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href,{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(res=>{
            showAlert('alertUbahPassword',res.message,res.success?'success':'error');
            if(res.success){ ['oldPass','newPass','confPass'].forEach(id=>document.getElementById(id).value=''); setTimeout(()=>closeModal('modalUbahPassword'),1500); }
        }).catch(()=>showAlert('alertUbahPassword','Kesalahan jaringan.','error')).finally(()=>{ sb.disabled=false; sb.innerHTML='<i class="fas fa-key"></i> Ubah Password'; });
    });
});

// LIVE CLOCK
(function(){
    var days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    function pad(n){ return n<10?'0'+n:n; }
    function tick(){
        var now=new Date();
        var h=pad(now.getHours()), m=pad(now.getMinutes()), s=pad(now.getSeconds());
        var dayStr=days[now.getDay()]+', '+now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear();
        var c1=document.getElementById('liveClock'), d1=document.getElementById('liveDate');
        var c2=document.getElementById('liveClock2'), d2=document.getElementById('liveDate2');
        if(c1) c1.textContent=h+':'+m+':'+s;
        if(d1) d1.textContent=dayStr;
        if(c2) c2.textContent=h+':'+m;
        if(d2) d2.textContent=dayStr;
    }
    tick(); setInterval(tick,1000);
})();
</script>
</body>
</html>