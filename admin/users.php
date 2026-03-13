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
        $_SESSION['nama_lengkap'] = $nama_lengkap; 
        $_SESSION['email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!', 'nama' => $nama_lengkap, 'email' => $email]);
    } else { echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan.']); }
    exit;
}

// AJAX: Ubah Password
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

// ============================================================
// LOGIC MANAJEMEN USER 
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nama     = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $role     = $_POST['role'];
        
        // Validasi sisi server
        if (strlen($username) < 3) {
            setFlashMessage('danger', 'Username minimal 3 karakter.');
            header('Location: users.php'); exit();
        }
        if (strlen($_POST['password']) < 6) {
            setFlashMessage('danger', 'Password minimal 6 karakter.');
            header('Location: users.php'); exit();
        }
        if (strlen($nama) < 3) {
            setFlashMessage('danger', 'Nama lengkap minimal 3 karakter.');
            header('Location: users.php'); exit();
        }
        // Cek duplikasi username
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            setFlashMessage('danger', 'Username sudah digunakan.');
            header('Location: users.php'); exit();
        }

        if (mysqli_query($conn, "INSERT INTO users (username, password, role, nama_lengkap) VALUES ('$username', '$password', '$role', '$nama')")) {
            setFlashMessage('success', 'User ditambahkan!');
        } else {
            setFlashMessage('danger', 'Gagal!');
        }
        header('Location: users.php');
        exit();
        
    } elseif ($_POST['action'] == 'edit') {
        $id       = (int)$_POST['id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $nama     = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
        $role     = $_POST['role'];
        $pu       = '';
        
        // Validasi sisi server
        if (strlen($username) < 3) {
            setFlashMessage('danger', 'Username minimal 3 karakter.');
            header('Location: users.php'); exit();
        }
        if (strlen($nama) < 3) {
            setFlashMessage('danger', 'Nama lengkap minimal 3 karakter.');
            header('Location: users.php'); exit();
        }
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
            setFlashMessage('danger', 'Password minimal 6 karakter.');
            header('Location: users.php'); exit();
        }
        // Cek duplikasi username kecuali dirinya sendiri
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $id");
        if (mysqli_num_rows($cek) > 0) {
            setFlashMessage('danger', 'Username sudah digunakan.');
            header('Location: users.php'); exit();
        }
        
        if (!empty($_POST['password'])) {
            $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $pu = ", password='$pw'";
        }
        
        if (mysqli_query($conn, "UPDATE users SET username='$username', nama_lengkap='$nama', role='$role' $pu WHERE id=$id")) {
            setFlashMessage('success', 'User diupdate!');
        } else {
            setFlashMessage('danger', 'Gagal!');
        }
        header('Location: users.php');
        exit();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    if ($id != getUserId()) {
        if (mysqli_query($conn, "DELETE FROM users WHERE id=$id")) {
            setFlashMessage('success', 'User dihapus!');
        } else {
            setFlashMessage('danger', 'Gagal!');
        }
    } else {
        setFlashMessage('danger', 'Tidak bisa hapus akun sendiri!');
    }
    header('Location: users.php');
    exit();
}

 $result = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
 $flash  = getFlashMessage();

// Data User untuk JS/Modal Baru
 $cur_nama  = getNamaLengkap();
 $cur_email = $_SESSION['email'] ?? '';
 $cur_init  = strtoupper(substr($cur_nama, 0, 1));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manajemen User - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* === EXISTING CSS === */
        :root {
            --bg: #020617; --sb: rgba(15, 23, 42, .97); --tb: rgba(15, 23, 42, .87); --cb: rgba(30, 41, 59, .75);
            --gb: rgba(255, 255, 255, .08); --tm: #f8fafc; --tmut: #94a3b8; --tsub: #cbd5e1; --thbg: rgba(0, 0, 0, .35);
            --trodd: rgba(15, 23, 42, .55); --treven: rgba(15, 23, 42, .35); --trhov: rgba(59, 130, 246, .09);
            --tbor: rgba(255, 255, 255, .06); --ibg: rgba(0, 0, 0, .30); --mbg: #1e293b; --mbor: rgba(255, 255, 255, .10);
            --lf: brightness(0) invert(1); --lbg: rgba(239, 68, 68, .18); --lc: #fca5a5; --lbor: rgba(239, 68, 68, .30);
            --sl: #94a3b8; --sa: rgba(59, 130, 246, .12); --togbg: rgba(30, 41, 59, .80); --togc: #94a3b8;
            --sbg: rgba(0, 0, 0, .30); --sbor: rgba(255, 255, 255, .10);

            /* Dropdown & Modal Vars */
            --dd-bg: rgba(18, 26, 48, .99); --dd-sep: rgba(255, 255, 255, .08);
            --dd-hover: rgba(255, 255, 255, .06); --dd-text: #e2e8f0; --dd-danger: #f87171;
        }
        [data-theme="light"] {
            --bg: #f0f4f8; --sb: rgba(255, 255, 255, .98); --tb: rgba(255, 255, 255, .96); --cb: rgba(255, 255, 255, .95);
            --gb: rgba(0, 0, 0, .09); --tm: #0f172a; --tmut: #64748b; --tsub: #334155; --thbg: #e9eef5;
            --trodd: #ffffff; --treven: #f8fafc; --trhov: #eff6ff; --tbor: rgba(0, 0, 0, .07); --ibg: rgba(255, 255, 255, .95);
            --mbg: #ffffff; --mbor: rgba(0, 0, 0, .10); --lf: none; --lbg: rgba(239, 68, 68, .07); --lc: #dc2626;
            --lbor: rgba(239, 68, 68, .18); --sl: #64748b; --sa: rgba(59, 130, 246, .08); --togbg: rgba(241, 245, 249, .95);
            --togc: #475569; --sbg: rgba(255, 255, 255, .95); --sbor: rgba(0, 0, 0, .10);

            --dd-bg: #ffffff; --dd-sep: rgba(0, 0, 0, .09);
            --dd-hover: rgba(0, 0, 0, .04); --dd-text: #1e293b; --dd-danger: #dc2626;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif !important; background-color: var(--bg) !important; color: var(--tm) !important; margin: 0; overflow-x: hidden; transition: background-color .35s, color .35s; }
        body::before { content: ''; position: fixed; inset: 0; z-index: -1; background: radial-gradient(circle at 15% 50%, rgba(59, 130, 246, .07), transparent 30%); pointer-events: none; }
        .sidebar { background: var(--sb) !important; border-right: 1px solid var(--gb) !important; backdrop-filter: blur(12px); }
        .sidebar-header { border-bottom: 1px solid var(--gb) !important; padding: 20px; }
        .sidebar-header p { color: var(--tmut) !important; margin: 0; font-size: 12px; }
        .sidebar-menu { list-style: none; margin: 0; padding: 12px 0; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: var(--sl) !important; text-decoration: none; border-left: 3px solid transparent; font-size: 14px; font-weight: 500; transition: .25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: var(--sa) !important; color: #3b82f6 !important; border-left-color: #3b82f6; }
        .main-content { background: transparent !important; }
        .topbar { background: var(--tb) !important; border-bottom: 1px solid var(--gb) !important; backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; height: 64px; position: relative; z-index: 1000; overflow: visible; }
        .topbar-left h2 { color: var(--tm) !important; font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        #theme-toggle-btn { all: unset; cursor: pointer; width: 40px; height: 40px; border-radius: 50%; background: var(--togbg) !important; border: 1px solid var(--gb) !important; color: var(--togc) !important; display: flex !important; align-items: center; justify-content: center; font-size: 17px; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); flex-shrink: 0; transition: all .25s; }
        #theme-toggle-btn:hover { color: #3b82f6 !important; transform: scale(1.1); }
        #theme-toggle-btn i { pointer-events: none; color: inherit !important; font-size: 17px; }
        .content-wrapper { padding: 24px; }
        .card { background: var(--cb) !important; border: 1px solid var(--gb) !important; border-radius: 16px !important; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); margin-bottom: 24px; overflow: hidden; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 22px; border-bottom: 1px solid var(--gb); }
        .card-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .card-body { padding: 22px; }
        .table-responsive { overflow-x: auto; border-radius: 10px; overflow: hidden; }
        table { width: 100% !important; border-collapse: collapse !important; }
        thead tr { background: var(--thbg) !important; }
        thead th { background: var(--thbg) !important; color: var(--tmut) !important; padding: 13px 16px !important; font-size: .75rem !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: .6px !important; border: none !important; }
        tbody tr:nth-child(odd) td { background: var(--trodd) !important; }
        tbody tr:nth-child(even) td { background: var(--treven) !important; }
        tbody tr:hover td { background: var(--trhov) !important; }
        tbody td { color: var(--tsub) !important; padding: 14px 16px !important; border-bottom: 1px solid var(--tbor) !important; border-top: none !important; border-left: none !important; border-right: none !important; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none !important; }
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 20px; font-size: .75rem; font-weight: 600; }
        .badge-admin { background: rgba(16, 185, 129, .18); color: #10b981; border: 1px solid rgba(16, 185, 129, .35); }
        .badge-user { background: rgba(59, 130, 246, .15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, .30); }
        [data-theme="light"] .badge-admin { background: rgba(16, 185, 129, .12); color: #059669; border-color: rgba(16, 185, 129, .25); }
        [data-theme="light"] .badge-user { background: rgba(59, 130, 246, .10); color: #2563eb; border-color: rgba(59, 130, 246, .20); }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 9px !important; border: none !important; color: #fff !important; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: .25s; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .btn-success { background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 12px rgba(16, 185, 129, .3); }
        .btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; box-shadow: 0 4px 12px rgba(239, 68, 68, .3); }
        .btn-sm { padding: 6px 12px !important; font-size: 12px !important; }
        .alert { border-radius: 10px !important; padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert-success { background: rgba(16, 185, 129, .12) !important; color: #059669 !important; border: 1px solid rgba(16, 185, 129, .25) !important; }
        .alert-danger { background: rgba(239, 68, 68, .12) !important; color: #dc2626 !important; border: 1px solid rgba(239, 68, 68, .25) !important; }
        .search-wrap { position: relative; margin-bottom: 18px; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--tmut); }
        .search-wrap input { width: 100%; padding: 11px 14px 11px 40px; background: var(--sbg) !important; border: 1px solid var(--sbor) !important; border-radius: 10px; color: var(--tm) !important; font-family: 'Outfit', sans-serif; font-size: 14px; outline: none; transition: .3s; }
        .search-wrap input:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, .15); }
        .search-wrap input::placeholder { color: var(--tmut); }
        
        /* ─── Modal Existing (Users) ─── */
        .modal { display: none; position: fixed; z-index: 9999; inset: 0; background: rgba(0, 0, 0, .65); backdrop-filter: blur(6px); }
        .modal-content { background: var(--mbg) !important; border: 1px solid var(--mbor) !important; border-radius: 16px; width: 90%; max-width: 580px; margin: 5% auto; box-shadow: 0 20px 50px rgba(0, 0, 0, .4); }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid var(--mbor); display: flex; align-items: center; justify-content: space-between; }
        .modal-header h3 { color: var(--tm) !important; margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .close { color: var(--tmut); font-size: 26px; cursor: pointer; line-height: 1; }
        .close:hover { color: var(--tm); }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 7px; color: var(--tsub) !important; font-weight: 600; font-size: 13px; }
        .form-control { width: 100%; padding: 11px 14px; background: var(--ibg) !important; border: 1px solid var(--gb) !important; border-radius: 8px; color: var(--tm) !important; font-family: 'Outfit', sans-serif; font-size: 14px; transition: .3s; }
        .form-control:focus { outline: none; border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, .15); }
        .form-control::placeholder { color: var(--tmut); }

        /* ─── Banner peringatan & Konfirmasi ─── */
        .warn-banner { display:flex; gap:11px; align-items:flex-start; background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.28); border-radius:10px; padding:12px 14px; margin-bottom:18px; }
        .warn-banner .wi { color:#f59e0b; font-size:14px; margin-top:2px; flex-shrink:0; }
        .warn-banner .wt { font-size:12px; color:#fbbf24; line-height:1.65; }
        .warn-banner .wt strong { display:block; font-size:12.5px; color:#fcd34d; margin-bottom:3px; }

        .confirm-box { background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.22); border-radius:10px; padding:12px 14px; margin-bottom:16px; }
        .confirm-box label { display:flex; align-items:flex-start; gap:9px; cursor:pointer; margin:0; }
        .confirm-box input[type="checkbox"] { margin-top:3px; accent-color:#ef4444; flex-shrink:0; }
        .confirm-box .confirm-text { font-size:12px; color:#fca5a5; line-height:1.6; }
        .confirm-box .confirm-text strong { color:#f87171; }

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

        /* === NEW STYLES: Dropdown & Profile Modals === */
        .user-info-wrap{ position:relative; }
        .user-trigger{ display:flex; align-items:center; gap:9px; padding:4px 10px 4px 4px; border-radius:10px; cursor:pointer; transition:background .18s; user-select:none; }
        .user-trigger:hover{ background:var(--dd-hover); }
        .user-avatar{ width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg,#3b82f6,#8b5cf6); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; }
        .user-trigger-name{ font-size:13px; font-weight:600; color:var(--tm); }
        .user-trigger-chevron{ font-size:10px; color:var(--tmut); transition:transform .22s; margin-left:1px; }
        .user-trigger-chevron.open{ transform:rotate(180deg); }

        .user-dropdown{
            position:absolute; top:calc(100% + 8px); right:0;
            min-width:190px;
            background:var(--dd-bg);
            border:1px solid var(--dd-sep);
            border-radius:10px;
            padding:5px 0;
            box-shadow:0 10px 40px rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.12);
            backdrop-filter:blur(24px);
            opacity:0; pointer-events:none;
            transform:translateY(-5px) scale(.97);
            transform-origin:top right;
            transition:opacity .16s ease,transform .16s ease;
            z-index:600;
        }
        .user-dropdown.show{ opacity:1; pointer-events:auto; transform:translateY(0) scale(1); }

        .dd-item{
            display:block; width:100%;
            padding:11px 20px;
            font-size:13.5px; font-weight:500; letter-spacing:.01em;
            color:var(--dd-text);
            background:none; border:none;
            text-align:left; cursor:pointer;
            text-decoration:none;
            font-family:'Outfit',sans-serif;
            transition:background .12s;
            white-space:nowrap;
        }
        .dd-item:hover{ background:var(--dd-hover); }
        .dd-sep{ height:1px; background:var(--dd-sep); margin:3px 0; }
        .dd-item-logout{ color:var(--dd-danger)!important; font-weight:600; }
        .dd-item-logout:hover{ background:rgba(239,68,68,.07)!important; }

        .modal-overlay{ position:fixed; inset:0; z-index:10000; background:rgba(2,6,23,.55); backdrop-filter:blur(5px); display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .22s ease; padding:20px; }
        .modal-overlay.show{ opacity:1; pointer-events:auto; }
        .modal-card{ background:var(--sb); border:1px solid var(--gb); border-radius:16px; width:100%; max-width:440px; box-shadow:0 24px 60px rgba(0,0,0,.38); transform:scale(.96) translateY(14px); transition:transform .22s ease; overflow:hidden; }
        .modal-overlay.show .modal-card{ transform:scale(1) translateY(0); }

        .modal-header-new{ display:flex; align-items:center; gap:12px; padding:18px 20px 14px; border-bottom:1px solid var(--gb); }
        .modal-icon-wrap{ width:40px; height:40px; border-radius:10px; flex-shrink:0; background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(139,92,246,.18)); border:1px solid rgba(59,130,246,.28); display:flex; align-items:center; justify-content:center; font-size:15px; color:#60a5fa; }
        .modal-header-new h3{ margin:0 0 2px; font-size:15px; font-weight:700; color:var(--tm); }
        .modal-header-new p{ margin:0; font-size:11px; color:var(--tmut); }
        .modal-close-new{ margin-left:auto; background:none; border:none; cursor:pointer; color:var(--tmut); font-size:14px; width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; transition:.18s; flex-shrink:0; }
        .modal-close-new:hover{ background:rgba(239,68,68,.1); color:#ef4444; }

        .modal-alert{ margin:12px 20px 0; padding:9px 12px; border-radius:8px; font-size:12.5px; display:none; align-items:center; gap:7px; }
        .modal-alert.success{ background:rgba(16,185,129,.1); color:#10b981; border:1px solid rgba(16,185,129,.2); display:flex; }
        .modal-alert.error{ background:rgba(239,68,68,.1); color:#ef4444; border:1px solid rgba(239,68,68,.2); display:flex; }

        .modal-body-new{ padding:14px 20px 18px; display:flex; flex-direction:column; gap:13px; }
        .mf-group label{ display:block; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--tm); margin-bottom:6px; }
        .mf-wrap{ position:relative; }
        .mf-wrap i.mf-icon{ position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--tmut); font-size:13px; pointer-events:none; z-index:1; }
        .mf-wrap input{ width:100%; padding:10px 36px; background:var(--togbg); border:1px solid var(--gb); border-radius:9px; color:var(--tm); font-size:13px; font-family:'Outfit',sans-serif; transition:all .2s; }
        .mf-wrap input:focus{ outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); background:var(--cb); }
        .mf-eye{ position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--tmut); font-size:13px; cursor:pointer; z-index:2; }
        .mf-eye:hover{ color:#60a5fa; }

        .modal-footer-new{ display:flex; gap:8px; justify-content:flex-end; padding:0 20px 18px; }
        .mf-btn-cancel{ padding:9px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; background:none; border:1px solid var(--gb); color:var(--tmut); transition:.18s; }
        .mf-btn-cancel:hover{ border-color:#ef4444; color:#ef4444; }
        .mf-btn-save{ padding:9px 18px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; background:linear-gradient(90deg,#3b82f6,#8b5cf6); color:#fff; border:none; box-shadow:0 4px 12px rgba(59,130,246,.28); transition:.2s; display:flex; align-items:center; gap:6px; }
        .mf-btn-save:hover{ transform:translateY(-1px); box-shadow:0 6px 16px rgba(139,92,246,.36); }
        .mf-btn-save:disabled{ opacity:.65; cursor:not-allowed; transform:none; }
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
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Manajemen Kategori</span></a></li>
            <li><a href="sop.php"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-users" style="color:#3b82f6"></i> Manajemen User</h2>
            </div>
            <div class="topbar-right">
                <button type="button" id="theme-toggle-btn" title="Ganti Tema"><i class="fas fa-moon" id="theme-icon"></i></button>
                
                <!-- USER TRIGGER + DROPDOWN START -->
                <div class="user-info-wrap">
                    <div class="user-trigger" id="userTrigger">
                        <div class="user-avatar" id="topbarAvatar"><?php echo $cur_init; ?></div>
                        <span class="user-trigger-name" id="topbarNama"><?php echo htmlspecialchars($cur_nama); ?></span>
                        <i class="fas fa-chevron-down user-trigger-chevron" id="userChevron"></i>
                    </div>

                    <!-- Dropdown Menu -->
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

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar User</h3>
                    <button onclick="openModal('addModal')" class="btn btn-success"><i class="fas fa-user-plus"></i> Tambah User</button>
                </div>
                <div class="card-body">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" onkeyup="searchTable('searchInput','userTable')" placeholder="Cari User...">
                    </div>
                    <div class="table-responsive">
                        <table id="userTable">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="28%">Username</th>
                                    <th width="30%">Nama Lengkap</th>
                                    <th width="15%">Role</th>
                                    <th width="14%">Tanggal</th>
                                    <th width="8%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1; 
                                while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td style="color:var(--tm)!important"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td style="font-weight:600;color:var(--tm)!important"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td>
                                        <?php if ($row['role'] == 'admin'): ?>
                                            <span class="badge badge-admin"><i class="fas fa-user-shield"></i> Admin</span>
                                        <?php else: ?>
                                            <span class="badge badge-user"><i class="fas fa-user"></i> User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--tmut)!important"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <button onclick='editUser(<?php echo json_encode($row); ?>)' class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                                        <?php if ($row['id'] != getUserId()): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirmDelete(<?php echo $row['id']; ?>,'user')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                                        <?php endif; ?>
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

<!-- MODAL: Tambah User -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tambah User</h3>
            <span class="close" onclick="closeModal('addModal')">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Banner Peringatan -->
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Perhatian! Pastikan Data Akun Benar!</strong>
                    Jangan memberikan akses Admin (Hak Akses Penuh) kepada sembarang orang.
                </div>
            </div>

            <form method="POST" id="formAddUser">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="add_username" class="form-control" required>
                    <div class="field-error" id="error-add-username" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="add_password" class="form-control" required>
                    <div class="field-error" id="error-add-password" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="add_nama" class="form-control" required>
                    <div class="field-error" id="error-add-nama" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="add_role" class="form-control" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <!-- Kotak Konfirmasi -->
                <div class="confirm-box">
                    <label>
                        <input type="checkbox" id="confirmAddUser">
                        <span class="confirm-text">
                            <strong>Saya yakin data yang diisi sudah benar dan bertanggung jawab atas akses yang diberikan.</strong>
                        </span>
                    </label>
                </div>

                <div style="display:flex;gap:10px">
                    <button type="submit" id="btnAddUser" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Edit User -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit User</h3>
            <span class="close" onclick="closeModal('editModal')">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Banner Peringatan Edit -->
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Peringatan Edit Data User!</strong>
                    Mengubah password akan mengakibatkan user tersebut logout otomatis dari sistem.
                </div>
            </div>

            <form method="POST" id="formEditUser">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                    <div class="field-error" id="error-edit-username" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password <small style="color:var(--tmut);font-weight:400">(kosongkan jika tidak diubah)</small></label>
                    <input type="password" name="password" id="edit_password" class="form-control">
                    <div class="field-error" id="error-edit-password" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_lengkap" class="form-control" required>
                    <div class="field-error" id="error-edit-nama" style="display: none;">
                        <i class="fas fa-exclamation-circle"></i> <span></span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" class="form-control" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <!-- Kotak Konfirmasi Edit -->
                <div class="confirm-box">
                    <label>
                        <input type="checkbox" id="confirmEditUser">
                        <span class="confirm-text">
                            <strong>Saya menyatakan perubahan data ini sudah benar dan saya bertanggung jawab atasnya.</strong>
                        </span>
                    </label>
                </div>

                <div style="display:flex;gap:10px">
                    <button type="submit" id="btnEditUser" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed"><i class="fas fa-save"></i> Simpan Perubahan</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Edit Profil -->
<div class="modal-overlay" id="modalEditProfil">
    <div class="modal-card">
        <div class="modal-header-new">
            <div class="modal-icon-wrap"><i class="fas fa-user-edit"></i></div>
            <div><h3>Edit Profil</h3><p>Perubahan tersimpan langsung ke sistem</p></div>
            <button class="modal-close-new" data-close="modalEditProfil"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-alert" id="alertEditProfil"></div>
        <form id="formEditProfil" autocomplete="off">
            <input type="hidden" name="action" value="update_profile">
            <div class="modal-body-new">
                <div class="mf-group">
                    <label>NAMA LENGKAP</label>
                    <div class="mf-wrap">
                        <i class="fas fa-id-card mf-icon"></i>
                        <input type="text" name="nama_lengkap" id="editNama"
                               value="<?php echo htmlspecialchars($cur_nama); ?>" placeholder="Nama lengkap" required>
                    </div>
                </div>
                <div class="mf-group">
                    <label>EMAIL <span style="font-size:10px;opacity:.5;text-transform:none;">(juga sebagai username)</span></label>
                    <div class="mf-wrap">
                        <i class="fas fa-envelope mf-icon"></i>
                        <input type="email" name="email" id="editEmail"
                               value="<?php echo htmlspecialchars($cur_email); ?>" placeholder="email@sinergi.co.id" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer-new">
                <button type="button" class="mf-btn-cancel" data-close="modalEditProfil">Batal</button>
                <button type="submit" class="mf-btn-save" id="btnSaveProfil"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Ubah Password -->
<div class="modal-overlay" id="modalUbahPassword">
    <div class="modal-card">
        <div class="modal-header-new">
            <div class="modal-icon-wrap"><i class="fas fa-lock"></i></div>
            <div><h3>Ubah Password</h3><p>Gunakan password yang kuat dan unik</p></div>
            <button class="modal-close-new" data-close="modalUbahPassword"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-alert" id="alertUbahPassword"></div>
        <form id="formUbahPassword" autocomplete="off">
            <input type="hidden" name="action" value="update_password">
            <div class="modal-body-new">
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
            <div class="modal-footer-new">
                <button type="button" class="mf-btn-cancel" data-close="modalUbahPassword">Batal</button>
                <button type="submit" class="mf-btn-save" id="btnSavePassword"><i class="fas fa-key"></i> Ubah Password</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script>
    (function() {
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    })();

    document.addEventListener('DOMContentLoaded', function() {
        // === THEME TOGGLE ===
        var btn = document.getElementById('theme-toggle-btn'),
            icon = document.getElementById('theme-icon');

        function sync() {
            icon.className = document.documentElement.getAttribute('data-theme') === 'light' ? 'fas fa-sun' : 'fas fa-moon';
        }
        
        sync();
        
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

        // === USER DROPDOWN ===
        var trigger = document.getElementById('userTrigger'),
            dropdown = document.getElementById('userDropdown'),
            chevron = document.getElementById('userChevron');

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            var open = dropdown.classList.toggle('show');
            chevron.classList.toggle('open', open);
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
                dropdown.classList.remove('show');
                chevron.classList.remove('open');
            }
        });

        // === MODAL PROFILE LOGIC ===
        function openNewModal(id) {
            document.getElementById(id).classList.add('show');
            dropdown.classList.remove('show'); 
            chevron.classList.remove('open');
            var alertBox = document.querySelector('#' + id + ' .modal-alert');
            if(alertBox) alertBox.className = 'modal-alert';
        }
        function closeNewModal(id) { document.getElementById(id).classList.remove('show'); }
        document.getElementById('openEditProfil').addEventListener('click', function() { openNewModal('modalEditProfil'); });
        document.getElementById('openUbahPassword').addEventListener('click', function() { openNewModal('modalUbahPassword'); });
        document.querySelectorAll('[data-close]').forEach(function(el) { el.addEventListener('click', function() { closeNewModal(this.getAttribute('data-close')); }); });
        document.querySelectorAll('.modal-overlay').forEach(function(ov) { ov.addEventListener('click', function(e) { if (e.target === ov) closeNewModal(ov.id); }); });
        
        // Profile Forms
        function showAlert(boxId, msg, type) {
            var el = document.getElementById(boxId);
            el.className = 'modal-alert ' + type;
            el.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i>&nbsp;' + msg;
        }
        document.getElementById('formEditProfil').addEventListener('submit', function(e) {
            e.preventDefault();
            var sb = document.getElementById('btnSaveProfil');
            sb.disabled = true; sb.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
            fetch(window.location.href, { method: 'POST', body: new FormData(this) })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                showAlert('alertEditProfil', res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    var init = res.nama.charAt(0).toUpperCase();
                    var ta = document.getElementById('topbarAvatar'); if(ta) ta.textContent = init;
                    var tn = document.getElementById('topbarNama'); if(tn) tn.textContent = res.nama;
                    document.getElementById('editNama').value = res.nama; document.getElementById('editEmail').value = res.email;
                    setTimeout(function() { closeNewModal('modalEditProfil'); }, 1500);
                }
            }).catch(function() { showAlert('alertEditProfil', 'Kesalahan jaringan.', 'error'); })
            .finally(function() { sb.disabled = false; sb.innerHTML = '<i class="fas fa-save"></i> Simpan'; });
        });
        document.getElementById('formUbahPassword').addEventListener('submit', function(e) {
            e.preventDefault();
            var np = document.getElementById('newPass').value, cp = document.getElementById('confPass').value;
            if(np !== cp) { showAlert('alertUbahPassword', 'Konfirmasi password tidak cocok.', 'error'); return; }
            var sb = document.getElementById('btnSavePassword');
            sb.disabled = true; sb.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
            fetch(window.location.href, { method: 'POST', body: new FormData(this) })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                showAlert('alertUbahPassword', res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    ['oldPass', 'newPass', 'confPass'].forEach(function(id) { document.getElementById(id).value = ''; });
                    setTimeout(function() { closeNewModal('modalUbahPassword'); }, 1500);
                }
            }).catch(function() { showAlert('alertUbahPassword', 'Kesalahan jaringan.', 'error'); })
            .finally(function() { sb.disabled = false; sb.innerHTML = '<i class="fas fa-key"></i> Ubah Password'; });
        });
        document.querySelectorAll('.mf-eye').forEach(function(eye) {
            eye.addEventListener('click', function() {
                var inp = document.getElementById(this.dataset.target);
                if(!inp) return;
                inp.type = inp.type === 'password' ? 'text' : 'password';
                this.classList.toggle('fa-eye'); this.classList.toggle('fa-eye-slash');
            });
        });

        // === VALIDATION FOR ADD USER FORM ===
        const addUsername = document.getElementById('add_username');
        const addPassword = document.getElementById('add_password');
        const addNama = document.getElementById('add_nama');
        const addRole = document.getElementById('add_role'); // not strictly needed but included
        const errorAddUsername = document.getElementById('error-add-username');
        const errorAddPassword = document.getElementById('error-add-password');
        const errorAddNama = document.getElementById('error-add-nama');
        const chkAdd = document.getElementById('confirmAddUser');
        const btnAdd = document.getElementById('btnAddUser');
        const addForm = document.getElementById('formAddUser');

        function validateAddForm() {
            let isValid = true;

            // Username
            const usernameVal = addUsername.value.trim();
            if (usernameVal.length < 3) {
                errorAddUsername.style.display = 'flex';
                errorAddUsername.querySelector('span').textContent = 'Username minimal 3 karakter.';
                isValid = false;
            } else {
                errorAddUsername.style.display = 'none';
            }

            // Password
            const passwordVal = addPassword.value.trim();
            if (passwordVal.length < 6) {
                errorAddPassword.style.display = 'flex';
                errorAddPassword.querySelector('span').textContent = 'Password minimal 6 karakter.';
                isValid = false;
            } else {
                errorAddPassword.style.display = 'none';
            }

            // Nama lengkap
            const namaVal = addNama.value.trim();
            if (namaVal.length < 3) {
                errorAddNama.style.display = 'flex';
                errorAddNama.querySelector('span').textContent = 'Nama lengkap minimal 3 karakter.';
                isValid = false;
            } else {
                errorAddNama.style.display = 'none';
            }

            return isValid;
        }

        function updateAddButton() {
            const valid = validateAddForm();
            const enabled = chkAdd.checked && valid;
            btnAdd.disabled = !enabled;
            btnAdd.style.opacity = enabled ? '1' : '.45';
            btnAdd.style.cursor = enabled ? 'pointer' : 'not-allowed';
        }

        addUsername.addEventListener('input', updateAddButton);
        addPassword.addEventListener('input', updateAddButton);
        addNama.addEventListener('input', updateAddButton);
        chkAdd.addEventListener('change', updateAddButton);
        updateAddButton(); // initial state

        addForm.addEventListener('submit', function(e) {
            if (!validateAddForm() || !chkAdd.checked) {
                e.preventDefault();
                alert('Harap periksa kembali: semua field harus diisi dengan benar (minimal 3 karakter untuk username dan nama, password minimal 6 karakter) dan konfirmasi harus dicentang.');
                return false;
            }
        });

        // === VALIDATION FOR EDIT USER FORM ===
        const editUsername = document.getElementById('edit_username');
        const editPassword = document.getElementById('edit_password');
        const editNama = document.getElementById('edit_nama_lengkap');
        const editRole = document.getElementById('edit_role');
        const errorEditUsername = document.getElementById('error-edit-username');
        const errorEditPassword = document.getElementById('error-edit-password');
        const errorEditNama = document.getElementById('error-edit-nama');
        const chkEdit = document.getElementById('confirmEditUser');
        const btnEdit = document.getElementById('btnEditUser');
        const editForm = document.getElementById('formEditUser');

        function validateEditForm() {
            let isValid = true;

            // Username
            const usernameVal = editUsername.value.trim();
            if (usernameVal.length < 3) {
                errorEditUsername.style.display = 'flex';
                errorEditUsername.querySelector('span').textContent = 'Username minimal 3 karakter.';
                isValid = false;
            } else {
                errorEditUsername.style.display = 'none';
            }

            // Password (optional, but if filled must be >=6)
            const passwordVal = editPassword.value.trim();
            if (passwordVal.length > 0 && passwordVal.length < 6) {
                errorEditPassword.style.display = 'flex';
                errorEditPassword.querySelector('span').textContent = 'Password minimal 6 karakter jika diisi.';
                isValid = false;
            } else {
                errorEditPassword.style.display = 'none';
            }

            // Nama lengkap
            const namaVal = editNama.value.trim();
            if (namaVal.length < 3) {
                errorEditNama.style.display = 'flex';
                errorEditNama.querySelector('span').textContent = 'Nama lengkap minimal 3 karakter.';
                isValid = false;
            } else {
                errorEditNama.style.display = 'none';
            }

            return isValid;
        }

        function updateEditButton() {
            const valid = validateEditForm();
            const enabled = chkEdit.checked && valid;
            btnEdit.disabled = !enabled;
            btnEdit.style.opacity = enabled ? '1' : '.45';
            btnEdit.style.cursor = enabled ? 'pointer' : 'not-allowed';
        }

        editUsername.addEventListener('input', updateEditButton);
        editPassword.addEventListener('input', updateEditButton);
        editNama.addEventListener('input', updateEditButton);
        chkEdit.addEventListener('change', updateEditButton);
        updateEditButton(); // initial state

        editForm.addEventListener('submit', function(e) {
            if (!validateEditForm() || !chkEdit.checked) {
                e.preventDefault();
                alert('Harap periksa kembali: semua field harus diisi dengan benar (username dan nama minimal 3 karakter, password jika diisi minimal 6 karakter) dan konfirmasi harus dicentang.');
                return false;
            }
        });
    });

    // === EXISTING: FUNCTIONS ===
    function openModal(id) {
        var el = document.getElementById(id);
        if(el) {
            el.style.display = 'block';
            if(id === 'addModal') {
                var c = document.getElementById('confirmAddUser');
                var b = document.getElementById('btnAddUser');
                if(c) { c.checked = false; b.disabled = true; b.style.opacity = '.45'; b.style.cursor = 'not-allowed'; }
                // Reset error displays
                document.getElementById('error-add-username').style.display = 'none';
                document.getElementById('error-add-password').style.display = 'none';
                document.getElementById('error-add-nama').style.display = 'none';
            }
            if(id === 'editModal') {
                var c = document.getElementById('confirmEditUser');
                var b = document.getElementById('btnEditUser');
                if(c) { c.checked = false; b.disabled = true; b.style.opacity = '.45'; b.style.cursor = 'not-allowed'; }
                document.getElementById('error-edit-username').style.display = 'none';
                document.getElementById('error-edit-password').style.display = 'none';
                document.getElementById('error-edit-nama').style.display = 'none';
            }
        }
    }
    function closeModal(id) {
        var el = document.getElementById(id);
        if(el) el.style.display = 'none';
    }
    function searchTable(inputId, tableId) {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById(inputId);
        filter = input.value.toUpperCase();
        table = document.getElementById(tableId);
        tr = table.getElementsByTagName("tr");
        for (i = 1; i < tr.length; i++) {
            var tds = tr[i].getElementsByTagName("td");
            var found = false;
            if (tds[1]) {
                if (tds[1].textContent.toUpperCase().indexOf(filter) > -1) found = true;
            }
            if (!found && tds[2]) {
                if (tds[2].textContent.toUpperCase().indexOf(filter) > -1) found = true;
            }
            tr[i].style.display = found ? "" : "none";
        }
    }
    function editUser(u) {
        document.getElementById('edit_id').value = u.id;
        document.getElementById('edit_username').value = u.username;
        document.getElementById('edit_nama_lengkap').value = u.nama_lengkap;
        document.getElementById('edit_role').value = u.role;
        document.getElementById('edit_password').value = '';
        openModal('editModal');
    }
    function confirmDelete(id, type){
        return confirm("Apakah Anda yakin ingin menghapus " + type + " ini?");
    }
</script>
</body>
</html>