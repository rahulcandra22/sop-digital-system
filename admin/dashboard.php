<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

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
    if ($upd->execute()) { $_SESSION['nama_lengkap']=$nama_lengkap; $_SESSION['email']=$email; echo json_encode(['success'=>true,'message'=>'Profil berhasil diperbarui!','nama'=>$nama_lengkap,'email'=>$email]); }
    else { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan perubahan.']); }
    exit;
}

// AJAX: Ubah Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_password') {
    header('Content-Type: application/json');
    $user_id = getUserId();
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
    if ($upd->execute()) { echo json_encode(['success'=>true,'message'=>'Password berhasil diubah!']); }
    else { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan password.']); }
    exit;
}

// ============================================================
// DATA
// ============================================================
$total_sop      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop"))['t'];
$total_kategori = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM categories"))['t'];
$total_user     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM users WHERE role='user'"))['t'];

$notif_query = mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE status IN ('Draft','Review','Revisi')");
$total_notif = mysqli_fetch_assoc($notif_query)['t'];

$st_draft     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE status='Draft'"))['t'];
$st_review    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE status='Review'"))['t'];
$st_disetujui = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE status='Disetujui'"))['t'];
$st_revisi    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as t FROM sop WHERE status='Revisi'"))['t'];

$cat_data = [];
$cat_res = mysqli_query($conn,"SELECT c.nama_kategori, COUNT(s.id) as jumlah FROM categories c LEFT JOIN sop s ON c.id=s.kategori_id GROUP BY c.id ORDER BY jumlah DESC LIMIT 6");
while ($r = mysqli_fetch_assoc($cat_res)) $cat_data[] = $r;

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
    <title>Dashboard Admin - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/page-transition.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        body{font-family:'Outfit',sans-serif!important;background-color:var(--bg)!important;color:var(--tm)!important;margin:0;overflow-x:hidden;transition:background-color .35s,color .35s;}
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
        .topbar-right{display:flex;align-items:center;gap:14px;}
        .top-action-btn{all:unset;cursor:pointer;width:40px;height:40px;border-radius:50%;background:var(--togbg)!important;border:1px solid var(--gb)!important;color:var(--togc)!important;display:flex!important;align-items:center;justify-content:center;font-size:17px;box-shadow:0 2px 8px rgba(0,0,0,.15);flex-shrink:0;transition:all .25s;text-decoration:none;position:relative;}
        .top-action-btn:hover{color:#3b82f6!important;transform:scale(1.1);}
        .top-action-btn i{pointer-events:none;color:inherit!important;}
        .notif-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;height:18px;min-width:18px;padding:0 5px;border-radius:20px;display:flex;align-items:center;justify-content:center;border:2px solid var(--tb);animation:pulse-red 2s infinite;}
        @keyframes pulse-red{0%{box-shadow:0 0 0 0 rgba(239,68,68,.7);}70%{box-shadow:0 0 0 6px rgba(239,68,68,0);}100%{box-shadow:0 0 0 0 rgba(239,68,68,0);}}

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
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:18px;margin-bottom:24px;}

        /* Dark mode stat cards — explicit values, tidak bisa di-override style.css */
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
        .stat-card::after{content:'';position:absolute;bottom:-20px;right:-20px;width:90px;height:90px;border-radius:50%;opacity:.07;pointer-events:none;}
        .stat-card.blue-card::after{background:#3b82f6;}
        .stat-card.green-card::after{background:#10b981;}
        .stat-card.orange-card::after{background:#f59e0b;}
        .stat-card.purple-card::after{background:#8b5cf6;}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 14px 32px rgba(0,0,0,.22);}
        [data-theme="light"] .stat-card:hover{box-shadow:0 10px 24px rgba(0,0,0,.10);}

        .stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
        .stat-icon.blue{background:rgba(59,130,246,.20);color:#60a5fa;}
        .stat-icon.green{background:rgba(16,185,129,.20);color:#34d399;}
        .stat-icon.orange{background:rgba(249,115,22,.20);color:#fb923c;}
        .stat-icon.purple{background:rgba(139,92,246,.20);color:#a78bfa;}
        [data-theme="light"] .stat-icon.blue{background:rgba(59,130,246,.12);color:#2563eb;}
        [data-theme="light"] .stat-icon.green{background:rgba(16,185,129,.12);color:#059669;}
        [data-theme="light"] .stat-icon.orange{background:rgba(249,115,22,.12);color:#d97706;}
        [data-theme="light"] .stat-icon.purple{background:rgba(139,92,246,.12);color:#7c3aed;}

        .stat-info{flex:1;}
        .stat-info h3{color:var(--tm)!important;font-size:30px;font-weight:800;margin:0 0 2px;line-height:1;font-variant-numeric:tabular-nums;}
        .stat-info p{color:var(--tmut)!important;margin:0;font-size:13px;}
        .stat-trend{font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:20px;margin-top:6px;}
        .stat-trend.up{background:rgba(16,185,129,.18);color:#10b981;}
        .stat-trend.info{background:rgba(59,130,246,.16);color:#60a5fa;}
        [data-theme="light"] .stat-trend.up{background:rgba(16,185,129,.10);color:#059669;}
        [data-theme="light"] .stat-trend.info{background:rgba(59,130,246,.10);color:#2563eb;}

        /* ═══ STATUS BREAKDOWN ═══ */
        .status-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
        .status-card{border-radius:14px;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;border:1px solid;transition:.3s;}
        .status-card:hover{transform:translateY(-3px);}
        /* Dark mode */
        .status-card.sc-draft {background:rgba(71,85,105,.22);border-color:rgba(148,163,184,.22);}
        .status-card.sc-review{background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.32);}
        .status-card.sc-ok    {background:rgba(16,185,129,.14);border-color:rgba(16,185,129,.32);}
        .status-card.sc-revisi{background:rgba(239,68,68,.14); border-color:rgba(239,68,68,.32);}
        /* Light mode */
        [data-theme="light"] .status-card.sc-draft {background:rgba(71,85,105,.07); border-color:rgba(71,85,105,.15);}
        [data-theme="light"] .status-card.sc-review{background:rgba(245,158,11,.07);border-color:rgba(245,158,11,.22);}
        [data-theme="light"] .status-card.sc-ok    {background:rgba(16,185,129,.07);border-color:rgba(16,185,129,.22);}
        [data-theme="light"] .status-card.sc-revisi{background:rgba(239,68,68,.07); border-color:rgba(239,68,68,.22);}

        .sc-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
        .sc-draft .sc-label{color:#94a3b8;}
        .sc-review .sc-label{color:#f59e0b;}
        .sc-ok .sc-label{color:#10b981;}
        .sc-revisi .sc-label{color:#ef4444;}
        .sc-num{font-size:30px;font-weight:800;line-height:1;font-variant-numeric:tabular-nums;}
        .sc-draft .sc-num{color:#e2e8f0;}
        .sc-review .sc-num{color:#fbbf24;}
        .sc-ok .sc-num{color:#34d399;}
        .sc-revisi .sc-num{color:#f87171;}
        [data-theme="light"] .sc-draft .sc-num{color:#475569;}
        [data-theme="light"] .sc-review .sc-num{color:#d97706;}
        [data-theme="light"] .sc-ok .sc-num{color:#059669;}
        [data-theme="light"] .sc-revisi .sc-num{color:#dc2626;}
        .sc-icon{font-size:30px;opacity:.2;}

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

        /* 2-col grid */
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}
        .two-col .card{margin-bottom:0;}

        /* ═══ TABLE ═══ */
        .table-responsive{overflow-x:auto;border-radius:10px;overflow:hidden;}
        table{width:100%!important;border-collapse:collapse!important;}
        thead tr{background:rgba(0,0,0,.3)!important;}
        [data-theme="light"] thead tr{background:#e9eef5!important;}
        thead th{color:var(--tmut)!important;padding:12px 16px!important;font-size:.72rem!important;font-weight:600!important;text-transform:uppercase!important;letter-spacing:.6px!important;border:none!important;}
        tbody tr:nth-child(odd) td{background:rgba(15,23,42,.5)!important;}
        tbody tr:nth-child(even) td{background:rgba(15,23,42,.3)!important;}
        [data-theme="light"] tbody tr:nth-child(odd) td{background:rgba(255,255,255,.95)!important;}
        [data-theme="light"] tbody tr:nth-child(even) td{background:rgba(248,250,252,.9)!important;}
        tbody tr:hover td{background:rgba(59,130,246,.09)!important;}
        tbody td{color:var(--tsub)!important;padding:12px 16px!important;border-bottom:1px solid rgba(255,255,255,.05)!important;border-top:none!important;border-left:none!important;border-right:none!important;vertical-align:middle;}
        [data-theme="light"] tbody td{border-bottom-color:rgba(0,0,0,.05)!important;}
        tbody tr:last-child td{border-bottom:none!important;}
        .badge-cat{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.70rem;background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.30);}
        [data-theme="light"] .badge-cat{background:rgba(59,130,246,.10);color:#2563eb;border-color:rgba(59,130,246,.2);}
        .s-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.70rem;font-weight:500;white-space:nowrap;}

        /* ═══ BUTTONS ═══ */
        .btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px!important;border:none!important;color:#fff!important;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;transition:.25s;}
        .btn:hover{filter:brightness(1.1);transform:translateY(-2px);}
        .btn-success{background:linear-gradient(135deg,#10b981,#059669)!important;box-shadow:0 4px 12px rgba(16,185,129,.3);}
        .btn-info{background:linear-gradient(135deg,#3b82f6,#2563eb)!important;box-shadow:0 4px 12px rgba(59,130,246,.3);}
        .btn-sm{padding:6px 14px!important;font-size:12px!important;}

        /* ═══ ALERTS ═══ */
        .alert{border-radius:10px!important;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;}
        .alert-success{background:rgba(16,185,129,.12)!important;color:#059669!important;border:1px solid rgba(16,185,129,.25)!important;}
        .alert-danger{background:rgba(239,68,68,.12)!important;color:#dc2626!important;border:1px solid rgba(239,68,68,.25)!important;}

        /* ═══ QUICK ACTIONS ═══ */
        .quick-actions-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
        .qa-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:22px 14px;text-decoration:none;border-radius:14px;color:#fff;font-weight:600;font-size:13px;transition:.3s;box-shadow:0 4px 14px rgba(0,0,0,.2);position:relative;overflow:hidden;}
        .qa-btn::before{content:'';position:absolute;inset:0;background:rgba(255,255,255,.07);opacity:0;transition:.25s;}
        .qa-btn:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,.25);}
        .qa-btn:hover::before{opacity:1;}
        .qa-btn i{font-size:22px;}
        .qa-btn-1{background:linear-gradient(135deg,#10b981,#059669);}
        .qa-btn-2{background:linear-gradient(135deg,#3b82f6,#2563eb);}
        .qa-btn-3{background:linear-gradient(135deg,#f59e0b,#d97706);}

        /* ═══ ACTIVITY FEED ═══ */
        .activity-list{display:flex;flex-direction:column;}
        .activity-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.05);}
        [data-theme="light"] .activity-item{border-bottom-color:rgba(0,0,0,.05);}
        .activity-item:last-child{border-bottom:none;padding-bottom:0;}
        .activity-dot{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
        .activity-dot.blue{background:rgba(59,130,246,.18);color:#60a5fa;}
        .activity-dot.green{background:rgba(16,185,129,.18);color:#34d399;}
        .activity-dot.orange{background:rgba(245,158,11,.18);color:#fbbf24;}
        .activity-dot.red{background:rgba(239,68,68,.18);color:#f87171;}
        .activity-dot.gray{background:rgba(148,163,184,.15);color:#94a3b8;}
        .activity-content{flex:1;min-width:0;}
        .activity-title{font-size:13px;font-weight:600;color:var(--tm);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .activity-meta{font-size:11.5px;color:var(--tmut);display:flex;align-items:center;gap:5px;flex-wrap:wrap;}

        /* ═══ CHART ═══ */
        .chart-wrap{position:relative;height:220px;display:flex;align-items:center;justify-content:center;}
        .donut-center{position:absolute;text-align:center;pointer-events:none;}
        .donut-center .dc-num{font-size:30px;font-weight:800;color:var(--tm);line-height:1;}
        .donut-center .dc-lbl{font-size:11px;color:var(--tmut);margin-top:2px;}

        /* ═══ SUMMARY ROWS ═══ */
        .summary-row{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.05);}
        [data-theme="light"] .summary-row{border-bottom-color:rgba(0,0,0,.05);}
        .summary-row:last-child{border-bottom:none;padding-bottom:0;}

        @media(max-width:900px){.two-col{grid-template-columns:1fr;}.status-grid{grid-template-columns:repeat(2,1fr);}.quick-actions-grid{grid-template-columns:1fr;}}
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
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Manajemen Kategori</span></a></li>
            <li><a href="sop.php"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-chart-line" style="color:#3b82f6"></i> Dashboard</h2>
            </div>
            <div class="topbar-right">
                <a href="sop.php" class="top-action-btn" title="Notifikasi SOP">
                    <i class="fas fa-bell"></i>
                    <?php if ($total_notif > 0): ?>
                        <span class="notif-badge"><?php echo $total_notif; ?></span>
                    <?php endif; ?>
                </a>
                <button type="button" class="top-action-btn" id="theme-toggle-btn" title="Ganti Tema">
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

            <!-- GREETING -->
            <div class="greeting-banner">
                <div class="greeting-left">
                    <div class="greeting-hi"><?php echo $greeting_icon; ?> <?php echo $greeting; ?>,</div>
                    <div class="greeting-name">Selamat datang, <span><?php echo htmlspecialchars(explode(' ', $cur_nama)[0]); ?>!</span></div>
                    <div class="greeting-sub">
                        <i class="fas fa-shield-alt"></i> Administrator
                        &nbsp;·&nbsp;
                        <i class="fas fa-database"></i> <?php echo $total_sop; ?> SOP aktif
                        &nbsp;·&nbsp;
                        <i class="fas fa-exclamation-circle" style="color:<?php echo $total_notif > 0 ? '#f59e0b' : '#94a3b8'; ?>"></i>
                        <?php echo $total_notif; ?> perlu perhatian
                    </div>
                </div>
                <div class="greeting-right">
                    <div class="greeting-clock" id="liveClock"><?php echo date('H:i:s'); ?></div>
                    <div class="greeting-date" id="liveDate"><?php echo date('d M Y'); ?></div>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card blue-card">
                    <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3 class="counter" data-target="<?php echo $total_sop; ?>">0</h3>
                        <p>Total SOP</p>
                        <span class="stat-trend info"><i class="fas fa-layer-group"></i> <?php echo $total_kategori; ?> kategori</span>
                    </div>
                </div>
                <div class="stat-card green-card">
                    <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 class="counter" data-target="<?php echo $st_disetujui; ?>">0</h3>
                        <p>SOP Disetujui</p>
                        <span class="stat-trend up"><i class="fas fa-arrow-up"></i> <?php echo $total_sop > 0 ? round($st_disetujui/$total_sop*100) : 0; ?>% dari total</span>
                    </div>
                </div>
                <div class="stat-card orange-card">
                    <div class="stat-icon orange"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 class="counter" data-target="<?php echo $total_user; ?>">0</h3>
                        <p>Total User</p>
                        <span class="stat-trend info"><i class="fas fa-user-shield"></i> Role aktif</span>
                    </div>
                </div>
                <div class="stat-card purple-card">
                    <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3 class="counter" data-target="<?php echo $total_notif; ?>">0</h3>
                        <p>Perlu Tindakan</p>
                        <span class="stat-trend" style="background:rgba(239,68,68,.16);color:#f87171;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:3px;padding:2px 9px;border-radius:20px;margin-top:6px;">
                            <i class="fas fa-exclamation-circle"></i> Draft/Review/Revisi
                        </span>
                    </div>
                </div>
            </div>

            <!-- STATUS BREAKDOWN -->
            <div class="status-grid">
                <div class="status-card sc-draft">
                    <div><div class="sc-label">Draft</div><div class="sc-num"><?php echo $st_draft; ?></div></div>
                    <i class="fas fa-pencil-alt sc-icon" style="color:#94a3b8"></i>
                </div>
                <div class="status-card sc-review">
                    <div><div class="sc-label">Review</div><div class="sc-num"><?php echo $st_review; ?></div></div>
                    <i class="fas fa-search sc-icon" style="color:#f59e0b"></i>
                </div>
                <div class="status-card sc-ok">
                    <div><div class="sc-label">Disetujui</div><div class="sc-num"><?php echo $st_disetujui; ?></div></div>
                    <i class="fas fa-check-double sc-icon" style="color:#10b981"></i>
                </div>
                <div class="status-card sc-revisi">
                    <div><div class="sc-label">Revisi</div><div class="sc-num"><?php echo $st_revisi; ?></div></div>
                    <i class="fas fa-redo sc-icon" style="color:#ef4444"></i>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="two-col">
                <!-- Donut Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie" style="color:#8b5cf6"></i> Distribusi Status SOP</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrap">
                            <canvas id="donutChart"></canvas>
                            <div class="donut-center">
                                <div class="dc-num"><?php echo $total_sop; ?></div>
                                <div class="dc-lbl">Total SOP</div>
                            </div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px;">
                            <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--tsub)"><span style="width:10px;height:10px;border-radius:3px;background:#94a3b8;flex-shrink:0"></span>Draft (<?php echo $st_draft; ?>)</div>
                            <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--tsub)"><span style="width:10px;height:10px;border-radius:3px;background:#f59e0b;flex-shrink:0"></span>Review (<?php echo $st_review; ?>)</div>
                            <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--tsub)"><span style="width:10px;height:10px;border-radius:3px;background:#10b981;flex-shrink:0"></span>Disetujui (<?php echo $st_disetujui; ?>)</div>
                            <div style="display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--tsub)"><span style="width:10px;height:10px;border-radius:3px;background:#ef4444;flex-shrink:0"></span>Revisi (<?php echo $st_revisi; ?>)</div>
                        </div>
                    </div>
                </div>
                <!-- Bar Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar" style="color:#3b82f6"></i> SOP per Kategori</h3>
                    </div>
                    <div class="card-body">
                        <div style="position:relative;height:268px;">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT + QUICK ACTIONS ROW -->
            <div class="two-col">
                <!-- Recent SOP -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-history" style="color:#10b981"></i> SOP Terbaru</h3>
                        <a href="sop.php" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Semua</a>
                    </div>
                    <div class="card-body" style="padding:8px 22px 16px">
                        <?php
                        $res = mysqli_query($conn,"SELECT s.*,c.nama_kategori FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id ORDER BY s.created_at DESC LIMIT 6");
                        $ss  = ['Draft'=>'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)','Review'=>'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)','Disetujui'=>'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)','Revisi'=>'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'];
                        $dot_colors = ['Draft'=>'gray','Review'=>'orange','Disetujui'=>'green','Revisi'=>'red'];
                        $dot_icons  = ['Draft'=>'fa-pencil-alt','Review'=>'fa-search','Disetujui'=>'fa-check','Revisi'=>'fa-redo'];
                        ?>
                        <div class="activity-list">
                            <?php if ($res && mysqli_num_rows($res)>0): while($row=mysqli_fetch_assoc($res)): $s=trim($row['status']); ?>
                            <div class="activity-item">
                                <div class="activity-dot <?php echo $dot_colors[$s]??'gray'; ?>">
                                    <i class="fas <?php echo $dot_icons[$s]??'fa-file'; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title" title="<?php echo htmlspecialchars($row['judul']); ?>">
                                        <?php echo htmlspecialchars(mb_substr($row['judul'],0,38).(mb_strlen($row['judul'])>38?'…':'')); ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="badge-cat" style="font-size:10px;padding:1px 7px"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                                        <span class="s-badge" style="<?php echo $ss[$s]??$ss['Revisi']; ?>;font-size:10px;padding:1px 7px"><?php echo $s; ?></span>
                                        <span>· <?php echo date('d/m/Y',strtotime($row['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                            <div style="text-align:center;padding:30px;color:var(--tmut);font-size:13px">Belum ada data</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right column -->
                <div style="display:flex;flex-direction:column;gap:20px;">
                    <!-- Quick Actions -->
                    <div class="card" style="margin-bottom:0">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt" style="color:#f59e0b"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions-grid">
                                <a href="sop.php" class="qa-btn qa-btn-1"><i class="fas fa-plus-circle"></i><span>Tambah SOP</span></a>
                                <a href="kategori.php" class="qa-btn qa-btn-2"><i class="fas fa-folder-plus"></i><span>Tambah Kategori</span></a>
                                <a href="users.php" class="qa-btn qa-btn-3"><i class="fas fa-user-plus"></i><span>Tambah User</span></a>
                            </div>
                        </div>
                    </div>
                    <!-- Summary -->
                    <div class="card" style="margin-bottom:0">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle" style="color:#60a5fa"></i> Ringkasan Sistem</h3>
                        </div>
                        <div class="card-body" style="padding:14px 22px 18px">
                            <?php
                            $items = [
                                ['label'=>'Total Dokumen SOP',    'val'=>$total_sop,      'icon'=>'fa-file-alt',        'color'=>'#60a5fa'],
                                ['label'=>'Kategori Tersedia',     'val'=>$total_kategori, 'icon'=>'fa-folder',          'color'=>'#34d399'],
                                ['label'=>'Pengguna Terdaftar',    'val'=>$total_user,     'icon'=>'fa-users',           'color'=>'#fb923c'],
                                ['label'=>'SOP Butuh Tindakan',    'val'=>$total_notif,    'icon'=>'fa-exclamation-circle','color'=>'#f87171'],
                            ];
                            foreach($items as $it):
                            ?>
                            <div class="summary-row">
                                <div style="display:flex;align-items:center;gap:9px;">
                                    <i class="fas <?php echo $it['icon']; ?>" style="color:<?php echo $it['color']; ?>;width:15px;text-align:center;font-size:13px"></i>
                                    <span style="font-size:13px;color:var(--tsub)"><?php echo $it['label']; ?></span>
                                </div>
                                <span style="font-size:15px;font-weight:700;color:var(--tm)"><?php echo $it['val']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
                    <label>NAMA LENGKAP</label>
                    <div class="mf-wrap">
                        <i class="fas fa-id-card mf-icon"></i>
                        <input type="text" name="nama_lengkap" id="editNama" value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required>
                    </div>
                </div>
                <div class="mf-group">
                    <label>EMAIL <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label>
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
                    <label>PASSWORD LAMA</label>
                    <div class="mf-wrap">
                        <i class="fas fa-lock mf-icon"></i>
                        <input type="password" name="old_password" id="oldPass" placeholder="Password saat ini">
                        <i class="fas fa-eye mf-eye" data-target="oldPass"></i>
                    </div>
                </div>
                <div class="mf-group">
                    <label>PASSWORD BARU</label>
                    <div class="mf-wrap">
                        <i class="fas fa-lock mf-icon"></i>
                        <input type="password" name="new_password" id="newPass" placeholder="Min. 6 karakter">
                        <i class="fas fa-eye mf-eye" data-target="newPass"></i>
                    </div>
                </div>
                <div class="mf-group">
                    <label>KONFIRMASI PASSWORD BARU</label>
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
<script src="../assets/js/page-transition.js"></script>
<script src="../assets/js/script.js"></script>
<script>
(function(){ if(localStorage.getItem('theme')==='light') document.documentElement.setAttribute('data-theme','light'); })();

document.addEventListener('DOMContentLoaded', function(){
    // THEME
    var btn=document.getElementById('theme-toggle-btn'), icon=document.getElementById('theme-icon');
    function sync(){ icon.className = document.documentElement.getAttribute('data-theme')==='light' ? 'far fa-sun' : 'fas fa-moon'; }
    sync();
    btn.addEventListener('click', function(){
        var l = document.documentElement.getAttribute('data-theme')==='light';
        if(l){ document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
        else { document.documentElement.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
        sync();
    });

    // DROPDOWN
    var trigger=document.getElementById('userTrigger'), dropdown=document.getElementById('userDropdown'), chevron=document.getElementById('userChevron');
    trigger.addEventListener('click', function(e){ e.stopPropagation(); var o=dropdown.classList.toggle('show'); chevron.classList.toggle('open',o); });
    document.addEventListener('click', function(e){ if(!dropdown.contains(e.target)&&!trigger.contains(e.target)){ dropdown.classList.remove('show'); chevron.classList.remove('open'); } });

    // MODAL HELPERS
    function openModal(id){ document.getElementById(id).classList.add('show'); dropdown.classList.remove('show'); chevron.classList.remove('open'); var a=document.querySelector('#'+id+' .modal-alert'); if(a) a.className='modal-alert'; }
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
            if(res.success){ var i=res.nama.charAt(0).toUpperCase(); var ta=document.getElementById('topbarAvatar'); if(ta)ta.textContent=i; var tn=document.getElementById('topbarNama'); if(tn)tn.textContent=res.nama; document.getElementById('editNama').value=res.nama; document.getElementById('editEmail').value=res.email; setTimeout(()=>closeModal('modalEditProfil'),1500); }
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

    // ANIMATED COUNTERS
    document.querySelectorAll('.counter').forEach(function(el){
        var target = parseInt(el.getAttribute('data-target')) || 0;
        if(target === 0){ el.textContent='0'; return; }
        var start=0, step=target/(1200/16);
        var t = setInterval(function(){ start+=step; if(start>=target){ el.textContent=target; clearInterval(t); } else { el.textContent=Math.floor(start); } }, 16);
    });

    // DONUT CHART
    var donutCtx = document.getElementById('donutChart').getContext('2d');
    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Draft','Review','Disetujui','Revisi'],
            datasets: [{
                data: [<?php echo $st_draft; ?>,<?php echo $st_review; ?>,<?php echo $st_disetujui; ?>,<?php echo $st_revisi; ?>],
                backgroundColor: ['rgba(148,163,184,.65)','rgba(245,158,11,.75)','rgba(16,185,129,.75)','rgba(239,68,68,.75)'],
                borderColor: ['rgba(148,163,184,.9)','rgba(245,158,11,.9)','rgba(16,185,129,.9)','rgba(239,68,68,.9)'],
                borderWidth: 2, hoverOffset: 8
            }]
        },
        options: {
            cutout: '70%',
            plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c=>' '+c.label+': '+c.raw+' SOP' } } },
            animation: { animateRotate:true, duration:1000 }
        }
    });

    // BAR CHART
    var barCtx = document.getElementById('barChart').getContext('2d');
    var catLabels = <?php echo json_encode(array_column($cat_data,'nama_kategori')); ?>;
    var catValues = <?php echo json_encode(array_column($cat_data,'jumlah')); ?>;
    var shortLabels = catLabels.map(l => l.length > 13 ? l.substring(0,11)+'…' : l);
    var isLight = document.documentElement.getAttribute('data-theme')==='light';

    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: shortLabels.length ? shortLabels : ['—'],
            datasets: [{
                label: 'Jumlah SOP',
                data: catValues.length ? catValues : [0],
                backgroundColor: ['rgba(59,130,246,.75)','rgba(139,92,246,.75)','rgba(16,185,129,.75)','rgba(245,158,11,.75)','rgba(239,68,68,.75)','rgba(20,184,166,.75)'],
                borderRadius: 8, borderSkipped: false,
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins: { legend:{display:false}, tooltip:{ callbacks:{ label: c=>' '+c.raw+' SOP' } } },
            scales: {
                x: { grid:{display:false}, ticks:{color:'#94a3b8',font:{family:'Outfit',size:11}}, border:{display:false} },
                y: { grid:{color:'rgba(255,255,255,.05)'}, ticks:{color:'#94a3b8',stepSize:1,font:{family:'Outfit',size:11}}, border:{display:false} }
            },
            animation: { duration:900, easing:'easeInOutQuart' }
        }
    });
});

// LIVE CLOCK
(function(){
    var days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var months=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    function pad(n){ return n<10?'0'+n:n; }
    function tick(){
        var now=new Date();
        var cl=document.getElementById('liveClock'), dl=document.getElementById('liveDate');
        if(cl) cl.textContent=pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
        if(dl) dl.textContent=days[now.getDay()]+', '+now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear();
    }
    tick(); setInterval(tick,1000);
})();
</script>
</body>
</html>