<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireAdmin();

if (isset($_GET['action']) && $_GET['action'] == 'get_data' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $id = (int)$_GET['id'];
    $q = mysqli_query($conn, "SELECT * FROM sop WHERE id=$id");
    if ($row = mysqli_fetch_assoc($q)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
    exit();
}

// ============================================================
// AJAX (Profil & Password)
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
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $_SESSION['email'] = $email;
        echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui!', 'nama' => $nama_lengkap, 'email' => $email]);
    } else { echo json_encode(['success' => false, 'message' => 'Gagal menyimpan perubahan.']); }
    exit;
}

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
// LOGIC SOP
// ============================================================

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $judul = trim($_POST['judul']);
    $kid   = (int)$_POST['kategori_id'];
    $desk  = trim($_POST['deskripsi']);
    $lk    = trim($_POST['langkah_kerja']);

    if ($_POST['action'] == 'add') {
        if (empty($judul) || empty($lk) || $kid == 0) {
            setFlashMessage('danger', 'Field wajib tidak boleh kosong!');
            header('Location: sop.php'); exit();
        }
        if (strlen($judul) < 5) {
            setFlashMessage('danger', 'Judul minimal 5 karakter.');
            header('Location: sop.php'); exit();
        }
        if (strlen($lk) < 20) {
            setFlashMessage('danger', 'Langkah kerja minimal 20 karakter.');
            header('Location: sop.php'); exit();
        }
    } else {
        if (empty($judul) || empty($lk) || $kid == 0) {
            setFlashMessage('danger', 'Field wajib tidak boleh kosong!');
            header('Location: sop.php'); exit();
        }
    }

    $judul   = mysqli_real_escape_string($conn, $judul);
    $desk    = mysqli_real_escape_string($conn, $desk);
    $lk      = mysqli_real_escape_string($conn, $lk);
    $st      = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Draft');
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan_admin'] ?? '');
    $fa      = '';
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] == 0) {
        $dir = "../assets/uploads/";
        $ext = pathinfo($_FILES['file_attachment']['name'], PATHINFO_EXTENSION);
        $fn  = time().'_'.uniqid().'.'.$ext;
        if (move_uploaded_file($_FILES['file_attachment']['tmp_name'], $dir.$fn)) $fa = $fn;
    }
    if ($_POST['action'] == 'add') {
        $cb = getUserId();
        if (mysqli_query($conn, "INSERT INTO sop (judul,kategori_id,deskripsi,langkah_kerja,file_attachment,created_by,status,catatan_admin) VALUES ('$judul',$kid,'$desk','$lk','$fa',$cb,'$st','')"))
            setFlashMessage('success', 'SOP ditambahkan!');
        else setFlashMessage('danger', 'Gagal!');
    } elseif ($_POST['action'] == 'edit') {
        $id = (int)$_POST['id'];
        $fu = '';
        if ($fa) {
            $fu  = ", file_attachment='$fa'";
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_attachment FROM sop WHERE id=$id"));
            if ($old && $old['file_attachment'] && file_exists("../assets/uploads/".$old['file_attachment']))
                unlink("../assets/uploads/".$old['file_attachment']);
        }
        if (mysqli_query($conn, "UPDATE sop SET judul='$judul',kategori_id=$kid,deskripsi='$desk',langkah_kerja='$lk',status='$st',catatan_admin='$catatan',updated_at=NOW() $fu WHERE id=$id"))
            setFlashMessage('success', 'SOP diupdate!');
        else setFlashMessage('danger', 'Gagal!');
    }
    header('Location: sop.php'); exit();
}

if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_attachment FROM sop WHERE id=$id"));
    if ($old && $old['file_attachment'] && file_exists("../assets/uploads/".$old['file_attachment']))
        unlink("../assets/uploads/".$old['file_attachment']);
    if (mysqli_query($conn, "DELETE FROM sop WHERE id=$id"))
        setFlashMessage('success', 'SOP dihapus!');
    else setFlashMessage('danger', 'Gagal!');
    header('Location: sop.php'); exit();
}

$result     = mysqli_query($conn, "SELECT s.*,c.nama_kategori,u.nama_lengkap as creator FROM sop s LEFT JOIN categories c ON s.kategori_id=c.id LEFT JOIN users u ON s.created_by=u.id ORDER BY s.created_at DESC");
$result_cat = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori ASC");
$flash      = getFlashMessage();

$cur_nama  = getNamaLengkap();
$cur_email = $_SESSION['email'] ?? '';
$cur_init  = strtoupper(substr($cur_nama, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Manajemen SOP - SOP Digital</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/page-transition.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg:#020617; --sb:rgba(15,23,42,.97); --tb:rgba(15,23,42,.87); --cb:rgba(30,41,59,.75);
            --gb:rgba(255,255,255,.08); --tm:#f8fafc; --tmut:#94a3b8; --tsub:#cbd5e1; --thbg:rgba(0,0,0,.35);
            --trodd:rgba(15,23,42,.55); --treven:rgba(15,23,42,.35); --trhov:rgba(59,130,246,.09);
            --tbor:rgba(255,255,255,.06); --ibg:rgba(0,0,0,.30); --mbg:#1e293b; --mbor:rgba(255,255,255,.10);
            --lf:brightness(0) invert(1); --lbg:rgba(239,68,68,.18); --lc:#fca5a5; --lbor:rgba(239,68,68,.30);
            --sl:#94a3b8; --sa:rgba(59,130,246,.12); --togbg:rgba(30,41,59,.80); --togc:#94a3b8;
            --sbg:rgba(0,0,0,.30); --sbor:rgba(255,255,255,.10);
            --dd-bg:rgba(18,26,48,.99); --dd-sep:rgba(255,255,255,.08);
            --dd-hover:rgba(255,255,255,.06); --dd-text:#e2e8f0; --dd-danger:#f87171;
        }
        [data-theme="light"] {
            --bg:#f0f4f8; --sb:rgba(255,255,255,.98); --tb:rgba(255,255,255,.96); --cb:rgba(255,255,255,.95);
            --gb:rgba(0,0,0,.09); --tm:#0f172a; --tmut:#64748b; --tsub:#334155; --thbg:#e9eef5;
            --trodd:#ffffff; --treven:#f8fafc; --trhov:#eff6ff; --tbor:rgba(0,0,0,.07); --ibg:rgba(255,255,255,.95);
            --mbg:#ffffff; --mbor:rgba(0,0,0,.10); --lf:none; --lbg:rgba(239,68,68,.07); --lc:#dc2626;
            --lbor:rgba(239,68,68,.18); --sl:#64748b; --sa:rgba(59,130,246,.08); --togbg:rgba(241,245,249,.95);
            --togc:#475569; --sbg:rgba(255,255,255,.95); --sbor:rgba(0,0,0,.10);
            --dd-bg:#ffffff; --dd-sep:rgba(0,0,0,.09);
            --dd-hover:rgba(0,0,0,.04); --dd-text:#1e293b; --dd-danger:#dc2626;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body { font-family:'Outfit',sans-serif!important; background-color:var(--bg)!important; color:var(--tm)!important; margin:0; overflow-x:hidden; transition:background-color .35s,color .35s; }
        body::before { content:''; position:fixed; inset:0; z-index:-1; background:radial-gradient(circle at 15% 50%,rgba(59,130,246,.07),transparent 30%); pointer-events:none; }

        /* SIDEBAR */
        .sidebar { background:var(--sb)!important; border-right:1px solid var(--gb)!important; backdrop-filter:blur(12px); }
        .sidebar-header { border-bottom:1px solid var(--gb)!important; padding:20px; }
        .sidebar-header p { color:var(--tmut)!important; margin:0; font-size:12px; }
        .sidebar-menu { list-style:none; margin:0; padding:12px 0; }
        .sidebar-menu li a { display:flex; align-items:center; gap:10px; padding:12px 20px; color:var(--sl)!important; text-decoration:none; border-left:3px solid transparent; font-size:14px; font-weight:500; transition:.25s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background:var(--sa)!important; color:#3b82f6!important; border-left-color:#3b82f6; }

        /* TOPBAR */
        .main-content { background:transparent!important; }
        .topbar { background:var(--tb)!important; border-bottom:1px solid var(--gb)!important; backdrop-filter:blur(12px); display:flex; align-items:center; justify-content:space-between; padding:0 24px; height:64px; position:relative; z-index:1000; overflow:visible; }
        .topbar-left h2 { color:var(--tm)!important; font-size:20px; font-weight:700; margin:0; display:flex; align-items:center; gap:8px; }
        .topbar-right { display:flex; align-items:center; gap:12px; }
        #theme-toggle-btn { all:unset; cursor:pointer; width:40px; height:40px; border-radius:50%; background:var(--togbg)!important; border:1px solid var(--gb)!important; color:var(--togc)!important; display:flex!important; align-items:center; justify-content:center; font-size:17px; box-shadow:0 2px 8px rgba(0,0,0,.15); flex-shrink:0; transition:all .25s; }
        #theme-toggle-btn:hover { color:#3b82f6!important; transform:scale(1.1); }
        #theme-toggle-btn i { pointer-events:none; color:inherit!important; font-size:17px; }

        /* LAYOUT */
        .content-wrapper { padding:24px; }
        .card { background:var(--cb)!important; border:1px solid var(--gb)!important; border-radius:16px!important; box-shadow:0 4px 24px rgba(0,0,0,.10); margin-bottom:24px; overflow:hidden; }
        .card-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid var(--gb); }
        .card-header h3 { color:var(--tm)!important; margin:0; font-size:16px; font-weight:600; display:flex; align-items:center; gap:8px; }
        .card-body { padding:22px 22px 0; }

        /* TABLE */
        .table-responsive { overflow-x:auto; border-radius:10px; overflow:hidden; }
        table { width:100%!important; border-collapse:collapse!important; }
        thead tr { background:var(--thbg)!important; }
        thead th { background:var(--thbg)!important; color:var(--tmut)!important; padding:13px 16px!important; font-size:.75rem!important; font-weight:600!important; text-transform:uppercase!important; letter-spacing:.6px!important; border:none!important; }
        tbody tr:nth-child(odd) td { background:var(--trodd)!important; }
        tbody tr:nth-child(even) td { background:var(--treven)!important; }
        tbody tr:hover td { background:var(--trhov)!important; }
        tbody td { color:var(--tsub)!important; padding:13px 16px!important; border-bottom:1px solid var(--tbor)!important; border-top:none!important; border-left:none!important; border-right:none!important; vertical-align:middle; }
        tbody tr:last-child td { border-bottom:none!important; }

        /* TABLE CELL STYLES */
        .sop-title-cell { display:flex; flex-direction:column; gap:3px; }
        .sop-title-text { font-weight:600; color:var(--tm)!important; font-size:13.5px; line-height:1.4; word-break:break-word; }
        .sop-creator-sub { font-size:11px; color:var(--tmut)!important; display:flex; align-items:center; gap:4px; }
        .action-btns { display:flex; gap:5px; align-items:center; flex-wrap:nowrap; }
        .action-btns .btn-sm { padding:6px 9px!important; font-size:12px!important; border-radius:7px!important; }

        /* BADGES */
        .badge-cat { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.75rem; background:rgba(59,130,246,.18); color:#60a5fa; border:1px solid rgba(59,130,246,.30); }
        [data-theme="light"] .badge-cat { background:rgba(59,130,246,.10); color:#2563eb; border-color:rgba(59,130,246,.20); }
        .s-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:.75rem; font-weight:500; white-space:nowrap; }

        /* BUTTONS */
        .btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:9px!important; border:none!important; color:#fff!important; font-weight:600; font-size:13px; cursor:pointer; text-decoration:none; transition:.25s; }
        .btn:hover { filter:brightness(1.1); transform:translateY(-2px); }
        .btn-success { background:linear-gradient(135deg,#10b981,#059669)!important; box-shadow:0 4px 12px rgba(16,185,129,.3); }
        .btn-info    { background:linear-gradient(135deg,#3b82f6,#2563eb)!important; box-shadow:0 4px 12px rgba(59,130,246,.3); }
        .btn-warning { background:linear-gradient(135deg,#f59e0b,#d97706)!important; }
        .btn-danger  { background:linear-gradient(135deg,#ef4444,#dc2626)!important; box-shadow:0 4px 12px rgba(239,68,68,.3); }
        .btn-sm { padding:6px 12px!important; font-size:12px!important; }

        /* ALERTS */
        .alert { border-radius:10px!important; padding:12px 18px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-size:14px; }
        .alert-success { background:rgba(16,185,129,.12)!important; color:#059669!important; border:1px solid rgba(16,185,129,.25)!important; }
        .alert-danger  { background:rgba(239,68,68,.12)!important; color:#dc2626!important; border:1px solid rgba(239,68,68,.25)!important; }

        /* SEARCH */
        .search-wrap { position:relative; margin-bottom:18px; }
        .search-wrap i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--tmut); }
        .search-wrap input { width:100%; padding:11px 14px 11px 40px; background:var(--sbg)!important; border:1px solid var(--sbor)!important; border-radius:10px; color:var(--tm)!important; font-family:'Outfit',sans-serif; font-size:14px; outline:none; transition:.3s; }
        .search-wrap input:focus { border-color:#3b82f6!important; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
        .search-wrap input::placeholder { color:var(--tmut); }

        /* PAGINATION */
        .pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:16px 22px; border-top:1px solid var(--gb); flex-wrap:wrap; gap:10px; margin-top:0; }
        .pagination-info { font-size:13px; color:var(--tmut); }
        .pagination-info span { color:var(--tm); font-weight:600; }
        .pagination-btns { display:flex; align-items:center; gap:6px; }
        .pg-btn { all:unset; cursor:pointer; min-width:34px; height:34px; border-radius:8px; background:var(--togbg); border:1px solid var(--gb); color:var(--tmut); font-size:13px; font-weight:600; font-family:'Outfit',sans-serif; display:flex; align-items:center; justify-content:center; padding:0 10px; transition:.2s; }
        .pg-btn:hover:not(:disabled) { border-color:#3b82f6; color:#3b82f6; }
        .pg-btn.active { background:linear-gradient(135deg,#3b82f6,#6366f1); border-color:transparent; color:#fff; box-shadow:0 3px 10px rgba(59,130,246,.35); }
        .pg-btn:disabled { opacity:.35; cursor:not-allowed; }
        .pg-btn.pg-arrow { font-size:11px; }

        /* SOP MODAL */
        .modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,.65); backdrop-filter:blur(6px); }
        .modal-content { background:var(--mbg)!important; border:1px solid var(--mbor)!important; border-radius:16px; width:90%; max-width:780px; margin:3% auto; box-shadow:0 20px 50px rgba(0,0,0,.4); max-height:92vh; overflow-y:auto; }
        .modal-header { padding:16px 22px; border-bottom:1px solid var(--mbor); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; background:var(--mbg); z-index:2; border-radius:16px 16px 0 0; }
        .modal-header h3 { color:var(--tm)!important; margin:0; font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; }
        .modal-close { background:none; border:none; color:var(--tmut); font-size:22px; cursor:pointer; line-height:1; padding:0; }
        .modal-close:hover { color:var(--tm); }
        .modal-body { padding:22px; }
        .form-group { margin-bottom:15px; }
        .form-row-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
        .form-row-label .field-name { font-size:13px; font-weight:600; color:var(--tsub)!important; display:flex; align-items:center; gap:5px; }
        .form-row-label .field-name i { font-size:11px; color:#3b82f6; }
        .badge-req  { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(239,68,68,.15); color:#f87171; border:1px solid rgba(239,68,68,.25); }
        .badge-opt  { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(148,163,184,.12); color:#94a3b8; border:1px solid rgba(148,163,184,.20); }
        .badge-info { font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:rgba(59,130,246,.12); color:#60a5fa; border:1px solid rgba(59,130,246,.25); }
        .form-control { width:100%; padding:10px 13px; background:var(--ibg)!important; border:1px solid var(--gb)!important; border-radius:8px; color:var(--tm)!important; font-family:'Outfit',sans-serif; font-size:14px; transition:border-color .25s,box-shadow .25s; }
        .form-control:focus { outline:none; border-color:#3b82f6!important; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
        .form-control::placeholder { color:var(--tmut); }
        textarea.form-control { resize:vertical; }
        select.form-control option { background:var(--mbg); color:var(--tm); }
        .field-hint { font-size:11.5px; color:var(--tmut); margin-top:5px; line-height:1.5; display:flex; align-items:flex-start; gap:5px; }
        .field-hint i { font-size:11px; margin-top:2px; flex-shrink:0; }
        .field-hint.blue i { color:#60a5fa; }
        .field-hint.yellow i { color:#f59e0b; }
        .field-error { color:#ef4444; font-size:11px; margin-top:4px; display:flex; align-items:center; gap:4px; }
        .field-error i { font-size:10px; }
        .warn-banner { display:flex; gap:11px; align-items:flex-start; background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.28); border-radius:10px; padding:12px 14px; margin-bottom:18px; }
        .warn-banner .wi { color:#f59e0b; font-size:14px; margin-top:2px; flex-shrink:0; }
        .warn-banner .wt { font-size:12px; color:#fbbf24; line-height:1.65; }
        .warn-banner .wt strong { display:block; font-size:12.5px; color:#fcd34d; margin-bottom:3px; }
        .steps-guide { background:rgba(59,130,246,.07); border:1px solid rgba(59,130,246,.18); border-radius:8px; padding:11px 14px; margin-bottom:8px; }
        .steps-guide p { color:var(--tmut)!important; font-size:12px; margin:0 0 6px; font-weight:600; }
        .steps-guide ol { margin:0; padding-left:16px; }
        .steps-guide ol li { color:#93c5fd; font-size:12px; line-height:1.7; }
        .confirm-box { background:rgba(239,68,68,.08); border:1px solid rgba(239,68,68,.22); border-radius:10px; padding:12px 14px; margin-bottom:16px; }
        .confirm-box label { display:flex; align-items:flex-start; gap:9px; cursor:pointer; margin:0; }
        .confirm-box input[type="checkbox"] { margin-top:3px; accent-color:#ef4444; flex-shrink:0; }
        .confirm-box .confirm-text { font-size:12px; color:#fca5a5; line-height:1.6; }
        .confirm-box .confirm-text strong { color:#f87171; }
        .modal-body h4 { color:var(--tm)!important; margin:18px 0 8px; }
        .modal-body p { color:var(--tmut)!important; margin:5px 0; }
        .modal-body p strong { color:var(--tm)!important; }
        .modal-body pre { background:var(--ibg)!important; border:none; border-left:4px solid #3b82f6; border-radius:8px; padding:14px; white-space:pre-wrap; font-family:'Outfit',sans-serif; color:var(--tsub)!important; margin:0; font-size:13px; }
        .info-block { background:var(--ibg)!important; border:1px solid var(--gb); border-radius:8px; padding:14px; margin:14px 0; }
        .file-block { background:rgba(59,130,246,.10); border:1px solid rgba(59,130,246,.25); border-radius:8px; padding:12px 14px; margin-top:14px; }
        .form-actions { display:flex; gap:10px; }

        /* DROPDOWN */
        .user-info-wrap { position:relative; }
        .user-trigger { display:flex; align-items:center; gap:9px; padding:4px 10px 4px 4px; border-radius:10px; cursor:pointer; transition:background .18s; user-select:none; }
        .user-trigger:hover { background:var(--dd-hover); }
        .user-avatar { width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg,#3b82f6,#8b5cf6); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0; }
        .user-trigger-name { font-size:13px; font-weight:600; color:var(--tm); }
        .user-trigger-chevron { font-size:10px; color:var(--tmut); transition:transform .22s; margin-left:1px; }
        .user-trigger-chevron.open { transform:rotate(180deg); }
        .user-dropdown { position:absolute; top:calc(100% + 8px); right:0; min-width:190px; background:var(--dd-bg); border:1px solid var(--dd-sep); border-radius:10px; padding:5px 0; box-shadow:0 10px 40px rgba(0,0,0,.25),0 2px 8px rgba(0,0,0,.12); backdrop-filter:blur(24px); opacity:0; pointer-events:none; transform:translateY(-5px) scale(.97); transform-origin:top right; transition:opacity .16s ease,transform .16s ease; z-index:600; }
        .user-dropdown.show { opacity:1; pointer-events:auto; transform:translateY(0) scale(1); }
        .dd-item { display:block; width:100%; padding:11px 20px; font-size:13.5px; font-weight:500; letter-spacing:.01em; color:var(--dd-text); background:none; border:none; text-align:left; cursor:pointer; text-decoration:none; font-family:'Outfit',sans-serif; transition:background .12s; white-space:nowrap; }
        .dd-item:hover { background:var(--dd-hover); }
        .dd-sep { height:1px; background:var(--dd-sep); margin:3px 0; }
        .dd-item-logout { color:var(--dd-danger)!important; font-weight:600; }
        .dd-item-logout:hover { background:rgba(239,68,68,.07)!important; }

        /* PROFILE MODALS */
        .modal-overlay { position:fixed; inset:0; z-index:9999; background:rgba(2,6,23,.55); backdrop-filter:blur(5px); display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .22s ease; padding:20px; }
        .modal-overlay.show { opacity:1; pointer-events:auto; }
        .modal-card { background:var(--sb); border:1px solid var(--gb); border-radius:16px; width:100%; max-width:440px; box-shadow:0 24px 60px rgba(0,0,0,.38); transform:scale(.96) translateY(14px); transition:transform .22s ease; overflow:hidden; }
        .modal-overlay.show .modal-card { transform:scale(1) translateY(0); }
        .modal-header-new { display:flex; align-items:center; gap:12px; padding:18px 20px 14px; border-bottom:1px solid var(--gb); }
        .modal-icon-wrap { width:40px; height:40px; border-radius:10px; flex-shrink:0; background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(139,92,246,.18)); border:1px solid rgba(59,130,246,.28); display:flex; align-items:center; justify-content:center; font-size:15px; color:#60a5fa; }
        .modal-header-new h3 { margin:0 0 2px; font-size:15px; font-weight:700; color:var(--tm); }
        .modal-header-new p { margin:0; font-size:11px; color:var(--tmut); }
        .modal-close-new { margin-left:auto; background:none; border:none; cursor:pointer; color:var(--tmut); font-size:14px; width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; transition:.18s; flex-shrink:0; }
        .modal-close-new:hover { background:rgba(239,68,68,.1); color:#ef4444; }
        .modal-alert { margin:12px 20px 0; padding:9px 12px; border-radius:8px; font-size:12.5px; display:none; align-items:center; gap:7px; }
        .modal-alert.success { background:rgba(16,185,129,.1); color:#10b981; border:1px solid rgba(16,185,129,.2); display:flex; }
        .modal-alert.error   { background:rgba(239,68,68,.1);  color:#ef4444; border:1px solid rgba(239,68,68,.2);  display:flex; }
        .modal-body-new { padding:14px 20px 18px; display:flex; flex-direction:column; gap:13px; }
        .mf-group label { display:block; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--tm); margin-bottom:6px; }
        .mf-wrap { position:relative; }
        .mf-wrap i.mf-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--tmut); font-size:13px; pointer-events:none; z-index:1; }
        .mf-wrap input { width:100%; padding:10px 36px; background:var(--togbg); border:1px solid var(--gb); border-radius:9px; color:var(--tm); font-size:13px; font-family:'Outfit',sans-serif; transition:all .2s; }
        .mf-wrap input:focus { outline:none; border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); background:var(--cb); }
        .mf-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--tmut); font-size:13px; cursor:pointer; z-index:2; }
        .mf-eye:hover { color:#60a5fa; }
        .modal-footer-new { display:flex; gap:8px; justify-content:flex-end; padding:0 20px 18px; }
        .mf-btn-cancel { padding:9px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; background:none; border:1px solid var(--gb); color:var(--tmut); transition:.18s; }
        .mf-btn-cancel:hover { border-color:#ef4444; color:#ef4444; }
        .mf-btn-save { padding:9px 18px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; font-family:'Outfit',sans-serif; background:linear-gradient(90deg,#3b82f6,#8b5cf6); color:#fff; border:none; box-shadow:0 4px 12px rgba(59,130,246,.28); transition:.2s; display:flex; align-items:center; gap:6px; }
        .mf-btn-save:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(139,92,246,.36); }
        .mf-btn-save:disabled { opacity:.65; cursor:not-allowed; transform:none; }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/logo.png" alt="Logo" style="width:220px">
            <p>SOP Digital System</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
            <li><a href="kategori.php"><i class="fas fa-folder"></i><span>Manajemen Kategori</span></a></li>
            <li><a href="sop.php" class="active"><i class="fas fa-file-alt"></i><span>Manajemen SOP</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Manajemen User</span></a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <h2><i class="fas fa-file-alt" style="color:#3b82f6"></i> Manajemen SOP</h2>
            </div>
            <div class="topbar-right">
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

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Daftar SOP</h3>
                    <button onclick="openModal('addModal')" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah SOP
                    </button>
                </div>
                <div class="card-body">
                    <div class="search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" onkeyup="searchTable('searchInput','sopTable')" placeholder="Cari SOP...">
                    </div>
                    <div class="table-responsive">
                        <table id="sopTable">
                            <thead>
                                <tr>
                                    <th width="4%">No</th>
                                    <th width="28%">Judul SOP</th>
                                    <th width="14%">Kategori</th>
                                    <th width="22%">Deskripsi</th>
                                    <th width="10%">Status</th>
                                    <th width="9%">Tanggal</th>
                                    <th width="13%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="sopTableBody">
                                <?php
                                $no = 1;
                                $ss = [
                                    'Draft'     => 'background:rgba(71,85,105,.25);color:#94a3b8;border:1px solid rgba(71,85,105,.4)',
                                    'Review'    => 'background:rgba(245,158,11,.20);color:#f59e0b;border:1px solid rgba(245,158,11,.4)',
                                    'Disetujui' => 'background:rgba(16,185,129,.20);color:#10b981;border:1px solid rgba(16,185,129,.4)',
                                    'Revisi'    => 'background:rgba(239,68,68,.20);color:#ef4444;border:1px solid rgba(239,68,68,.4)'
                                ];
                                $total_rows = mysqli_num_rows($result);
                                while ($row = mysqli_fetch_assoc($result)):
                                    $s     = trim($row['status']);
                                    $style = $ss[$s] ?? $ss['Revisi'];
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <div class="sop-title-cell">
                                            <span class="sop-title-text"><?php echo htmlspecialchars($row['judul']); ?></span>
                                            <span class="sop-creator-sub">
                                                <i class="fas fa-user-circle"></i>
                                                <?php echo htmlspecialchars($row['creator']); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="badge-cat"><?php echo htmlspecialchars($row['nama_kategori']); ?></span></td>
                                    <td style="color:var(--tmut)!important;font-size:12.5px"><?php echo mb_substr(htmlspecialchars($row['deskripsi']), 0, 60).(mb_strlen($row['deskripsi'])>60?'…':''); ?></td>
                                    <td><span class="s-badge" style="<?php echo $style; ?>"><?php echo $s; ?></span></td>
                                    <td style="font-size:12px;color:var(--tmut)!important;white-space:nowrap"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button onclick="viewSOP(<?php echo $row['id']; ?>)" class="btn btn-info btn-sm" title="Lihat Detail"><i class="fas fa-eye"></i></button>
                                            <button onclick="editSOP(<?php echo $row['id']; ?>)" class="btn btn-warning btn-sm" title="Edit SOP"><i class="fas fa-edit"></i></button>
                                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirmDelete(<?php echo $row['id']; ?>,'SOP')" class="btn btn-danger btn-sm" title="Hapus"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                endwhile;
                                if ($total_rows == 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:32px;color:var(--tmut)">
                                        <i class="fas fa-folder-open" style="font-size:24px;display:block;margin-bottom:8px;opacity:.4"></i>
                                        Belum ada SOP
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION BAR -->
                    <div class="pagination-wrap" id="paginationWrap">
                        <div class="pagination-info">
                            Menampilkan <span id="pgFrom">1</span>–<span id="pgTo">20</span> dari <span id="pgTotal">0</span> dokumen
                        </div>
                        <div class="pagination-btns" id="pgBtns"></div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<!-- ═══════════ MODAL TAMBAH SOP ═══════════ -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Tambah Dokumen SOP</h3>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Perhatian! Baca Sebelum Menambah Dokumen SOP!</strong>
                    SOP hanya boleh dibuat oleh pihak yang berwenang dan telah mendapat persetujuan.
                    Pastikan dokumen belum tersedia ditulis lengkap dan akurat, serta mengikuti standar yang berlaku.
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formAddSOP">
                <input type="hidden" name="action" value="add">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-heading"></i> Judul SOP</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <input type="text" name="judul" id="add_judul" class="form-control" placeholder="Contoh: SOP Pengajuan Cuti" required>
                        <div class="field-error" id="error-judul" style="display:none"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-folder"></i> Kategori</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <select name="kategori_id" id="add_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php mysqli_data_seek($result_cat, 0); while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="field-error" id="error-kategori" style="display:none"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-align-left"></i> Deskripsi</span>
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
                    <textarea name="langkah_kerja" id="add_langkah" class="form-control" rows="8" required placeholder="Silahkan isi langkah-langkah pembuatan SOP ini!"></textarea>
                    <div class="field-error" id="error-langkah" style="display:none"><i class="fas fa-exclamation-circle"></i> <span></span></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-tag"></i> Status Awal</span>
                            <span class="badge-info">Perhatikan</span>
                        </div>
                        <select name="status" class="form-control">
                            <option value="Draft">Draft</option>
                            <option value="Review">Review</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Revisi">Revisi</option>
                        </select>
                        <div class="field-hint blue">
                            <i class="fas fa-info-circle"></i>
                            <span>Dokumen baru umumnya dimulai dari <strong style="color:#94a3b8">Draft</strong>.</span>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-paperclip"></i> File Lampiran</span>
                            <span class="badge-opt">Opsional</span>
                        </div>
                        <input type="file" name="file_attachment" class="form-control">
                        <div class="field-hint blue"><i class="fas fa-file-alt"></i><span>Format: PDF, DOCX, atau XLSX.</span></div>
                    </div>
                </div>
                <div class="confirm-box" style="margin-top:16px">
                    <label>
                        <input type="checkbox" id="confirmSOP">
                        <span class="confirm-text"><strong>Saya menyatakan dokumen SOP ini telah mendapat persetujuan, ditulis dengan lengkap dan akurat, serta belum tersedia di sistem ini.</strong></span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" id="btnSimpanSOP" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed"><i class="fas fa-save"></i> Simpan</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-danger"><i class="fas fa-times"></i> Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL VIEW -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Detail SOP</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewContent">
            <div style="text-align:center;padding:30px;color:var(--tmut)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
</div>

<!-- MODAL EDIT SOP -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Dokumen SOP</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warn-banner">
                <i class="fas fa-exclamation-triangle wi"></i>
                <div class="wt">
                    <strong>Perhatian! Mengedit SOP akan tercatat di Riwayat.</strong>
                    Pastikan revisi ini sudah sesuai dengan standar yang berlaku dan benar-benar diperlukan.
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="formEditSOP">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-heading"></i> Judul SOP</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <input type="text" name="judul" id="edit_judul" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-folder"></i> Kategori</span>
                            <span class="badge-req">Wajib di isi!</span>
                        </div>
                        <select name="kategori_id" id="edit_kategori_id" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php mysqli_data_seek($result_cat, 0); while ($cat = mysqli_fetch_assoc($result_cat)): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nama_kategori']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-top:14px">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-align-left"></i> Deskripsi</span>
                        <span class="badge-opt">Opsional</span>
                    </div>
                    <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <div class="form-row-label">
                        <span class="field-name"><i class="fas fa-list-ol"></i> Langkah-langkah Kerja</span>
                        <span class="badge-req">Wajib di isi!</span>
                    </div>
                    <textarea name="langkah_kerja" id="edit_langkah_kerja" class="form-control" rows="8" required></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-tag"></i> Status</span>
                            <span class="badge-info">Ubah Status</span>
                        </div>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="Draft">Draft</option>
                            <option value="Review">Review</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Revisi">Revisi</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <div class="form-row-label">
                            <span class="field-name"><i class="fas fa-paperclip"></i> File Lampiran</span>
                            <span class="badge-opt">Opsional</span>
                        </div>
                        <input type="file" name="file_attachment" class="form-control">
                        <div class="field-hint blue" style="font-size:11px"><i class="fas fa-info-circle"></i><span>Biarkan kosong jika tidak ingin mengganti file lama.</span></div>
                    </div>
                </div>
                <div class="confirm-box" style="margin-top:16px">
                    <label>
                        <input type="checkbox" id="confirmEditSOP">
                        <span class="confirm-text"><strong>Saya menyatakan revisi SOP ini sudah benar dan saya bertanggung jawab atas perubahan data.</strong></span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" id="btnSimpanEditSOP" class="btn btn-success" disabled style="opacity:.45;cursor:not-allowed"><i class="fas fa-save"></i> Simpan Perubahan</button>
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
<script src="../assets/js/page-transition.js"></script>
<script>
(function(){
    if(localStorage.getItem('theme')==='light')
        document.documentElement.setAttribute('data-theme','light');
})();

document.addEventListener('DOMContentLoaded', function(){

    // === THEME TOGGLE ===
    var btn  = document.getElementById('theme-toggle-btn'),
        icon = document.getElementById('theme-icon');
    function sync(){
        icon.className = document.documentElement.getAttribute('data-theme')==='light' ? 'far fa-sun' : 'fas fa-moon';
    }
    sync();
    btn.addEventListener('click', function(){
        var isLight = document.documentElement.getAttribute('data-theme')==='light';
        if(isLight){ document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
        else { document.documentElement.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
        sync();
    });

    // === SOP CHECKBOX (ADD) ===
    var chk  = document.getElementById('confirmSOP'),
        save = document.getElementById('btnSimpanSOP');
    if(chk && save){
        chk.addEventListener('change', function(){ updateSubmitButton(); });
    }

    // === SOP CHECKBOX (EDIT) ===
    var chkEdit  = document.getElementById('confirmEditSOP'),
        saveEdit = document.getElementById('btnSimpanEditSOP');
    if(chkEdit && saveEdit){
        chkEdit.addEventListener('change', function(){
            saveEdit.disabled      = !this.checked;
            saveEdit.style.opacity = this.checked ? '1' : '.45';
            saveEdit.style.cursor  = this.checked ? 'pointer' : 'not-allowed';
        });
    }

    // === VALIDATION (ADD FORM) ===
    const addJudul    = document.getElementById('add_judul');
    const addKategori = document.getElementById('add_kategori');
    const addLangkah  = document.getElementById('add_langkah');
    const errorJudul    = document.getElementById('error-judul');
    const errorKategori = document.getElementById('error-kategori');
    const errorLangkah  = document.getElementById('error-langkah');
    const addForm = document.getElementById('formAddSOP');

    function validateAddForm() {
        let isValid = true;
        if (addJudul.value.trim().length < 5) {
            errorJudul.style.display = 'flex';
            errorJudul.querySelector('span').textContent = 'Judul minimal 5 karakter.';
            isValid = false;
        } else { errorJudul.style.display = 'none'; }

        if (!addKategori.value) {
            errorKategori.style.display = 'flex';
            errorKategori.querySelector('span').textContent = 'Pilih kategori yang sesuai.';
            isValid = false;
        } else { errorKategori.style.display = 'none'; }

        if (addLangkah.value.trim().length < 20) {
            errorLangkah.style.display = 'flex';
            errorLangkah.querySelector('span').textContent = 'Langkah kerja minimal 20 karakter.';
            isValid = false;
        } else { errorLangkah.style.display = 'none'; }

        return isValid;
    }

    function updateSubmitButton() {
        const chk = document.getElementById('confirmSOP');
        const btn = document.getElementById('btnSimpanSOP');
        const enabled = chk.checked && validateAddForm();
        btn.disabled       = !enabled;
        btn.style.opacity  = enabled ? '1' : '.45';
        btn.style.cursor   = enabled ? 'pointer' : 'not-allowed';
    }

    addJudul.addEventListener('input', updateSubmitButton);
    addKategori.addEventListener('change', updateSubmitButton);
    addLangkah.addEventListener('input', updateSubmitButton);
    addForm.addEventListener('submit', function(e) {
        if (!validateAddForm() || !document.getElementById('confirmSOP').checked) {
            e.preventDefault();
            alert('Harap periksa kembali form: semua field wajib terisi dan konfirmasi dicentang.');
            return false;
        }
    });
    updateSubmitButton();

    // === DROPDOWN ===
    var trigger  = document.getElementById('userTrigger'),
        dropdown = document.getElementById('userDropdown'),
        chevron  = document.getElementById('userChevron');

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

    // === PROFILE MODAL LOGIC ===
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
    document.querySelectorAll('[data-close]').forEach(function(el) {
        el.addEventListener('click', function() { closeNewModal(this.getAttribute('data-close')); });
    });
    document.querySelectorAll('.modal-overlay').forEach(function(ov) {
        ov.addEventListener('click', function(e) { if (e.target === ov) closeNewModal(ov.id); });
    });

    // === PASSWORD EYE TOGGLE ===
    document.querySelectorAll('.mf-eye').forEach(function(eye) {
        eye.addEventListener('click', function() {
            var inp = document.getElementById(this.dataset.target);
            if(!inp) return;
            inp.type = inp.type === 'password' ? 'text' : 'password';
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    });

    // === ALERT HELPER ===
    function showAlert(boxId, msg, type) {
        var el = document.getElementById(boxId);
        el.className = 'modal-alert ' + type;
        el.innerHTML = '<i class="fas ' + (type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle') + '"></i>&nbsp;' + msg;
    }

    // === FORM: EDIT PROFIL ===
    document.getElementById('formEditProfil').addEventListener('submit', function(e) {
        e.preventDefault();
        var sb = document.getElementById('btnSaveProfil');
        sb.disabled = true; sb.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href, { method:'POST', body:new FormData(this) })
        .then(function(r){ return r.json(); })
        .then(function(res){
            showAlert('alertEditProfil', res.message, res.success ? 'success' : 'error');
            if(res.success){
                var init = res.nama.charAt(0).toUpperCase();
                var ta = document.getElementById('topbarAvatar'); if(ta) ta.textContent = init;
                var tn = document.getElementById('topbarNama');   if(tn) tn.textContent = res.nama;
                document.getElementById('editNama').value  = res.nama;
                document.getElementById('editEmail').value = res.email;
                setTimeout(function(){ closeNewModal('modalEditProfil'); }, 1500);
            }
        }).catch(function(){ showAlert('alertEditProfil','Kesalahan jaringan.','error'); })
        .finally(function(){ sb.disabled = false; sb.innerHTML = '<i class="fas fa-save"></i> Simpan'; });
    });

    // === FORM: UBAH PASSWORD ===
    document.getElementById('formUbahPassword').addEventListener('submit', function(e) {
        e.preventDefault();
        var np = document.getElementById('newPass').value,
            cp = document.getElementById('confPass').value;
        if(np !== cp){ showAlert('alertUbahPassword','Konfirmasi password tidak cocok.','error'); return; }
        var sb = document.getElementById('btnSavePassword');
        sb.disabled = true; sb.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Menyimpan...';
        fetch(window.location.href, { method:'POST', body:new FormData(this) })
        .then(function(r){ return r.json(); })
        .then(function(res){
            showAlert('alertUbahPassword', res.message, res.success ? 'success' : 'error');
            if(res.success){
                ['oldPass','newPass','confPass'].forEach(function(id){ document.getElementById(id).value = ''; });
                setTimeout(function(){ closeNewModal('modalUbahPassword'); }, 1500);
            }
        }).catch(function(){ showAlert('alertUbahPassword','Kesalahan jaringan.','error'); })
        .finally(function(){ sb.disabled = false; sb.innerHTML = '<i class="fas fa-key"></i> Ubah Password'; });
    });
});

// === SOP MODAL FUNCTIONS ===
function openModal(id) { var el = document.getElementById(id); if(el) el.style.display = 'block'; }
function closeModal(id) { var el = document.getElementById(id); if(el) el.style.display = 'none'; }

function viewSOP(id){
    document.getElementById('viewContent').innerHTML =
        '<div style="text-align:center;padding:30px;color:var(--tmut)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    openModal('viewModal');
    fetch('sop_ajax.php?action=view&id='+id).then(r=>r.text()).then(d=>{ document.getElementById('viewContent').innerHTML = d; });
}

function editSOP(id){
    openModal('editModal');
    document.getElementById('formEditSOP').reset();
    document.getElementById('edit_id').value = id;
    var chk = document.getElementById('confirmEditSOP');
    var btn = document.getElementById('btnSimpanEditSOP');
    if(chk){ chk.checked = false; btn.disabled = true; btn.style.opacity = '.45'; btn.style.cursor = 'not-allowed'; }
    fetch(window.location.href + '?action=get_data&id=' + id)
        .then(r => r.json())
        .then(data => {
            if(data.error){ alert(data.error); closeModal('editModal'); return; }
            document.getElementById('edit_judul').value         = data.judul || '';
            document.getElementById('edit_kategori_id').value   = data.kategori_id || '';
            document.getElementById('edit_deskripsi').value     = data.deskripsi || '';
            document.getElementById('edit_langkah_kerja').value = data.langkah_kerja || '';
            document.getElementById('edit_status').value        = data.status || 'Draft';
        })
        .catch(e => console.error("Error fetching SOP data:", e));
}

function confirmDelete(id, type){
    return confirm("Apakah Anda yakin ingin menghapus " + type + " ini?");
}

// === PAGINATION ===
(function () {
    var PER_PAGE = 20;
    var currentPage = 1;
    var allRows = [];
    var filteredRows = [];

    function init() {
        var tbody = document.getElementById('sopTableBody');
        if (!tbody) return;
        allRows = Array.from(tbody.querySelectorAll('tr'));
        filteredRows = allRows.slice();
        render();
    }

    function render() {
        var total = filteredRows.length;
        var totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > totalPages) currentPage = 1;
        var start = (currentPage - 1) * PER_PAGE;
        var end   = Math.min(start + PER_PAGE, total);

        allRows.forEach(function(r) { r.style.display = 'none'; });
        filteredRows.forEach(function(r, i) {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        var pgTotal = document.getElementById('pgTotal');
        var pgFrom  = document.getElementById('pgFrom');
        var pgTo    = document.getElementById('pgTo');
        if (pgTotal) pgTotal.textContent = total;
        if (pgFrom)  pgFrom.textContent  = total === 0 ? 0 : start + 1;
        if (pgTo)    pgTo.textContent    = end;

        var wrap = document.getElementById('pgBtns');
        if (!wrap) return;
        wrap.innerHTML = '';

        var prev = document.createElement('button');
        prev.className = 'pg-btn pg-arrow';
        prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prev.disabled  = currentPage === 1;
        prev.onclick   = function() { if (currentPage > 1) { currentPage--; render(); } };
        wrap.appendChild(prev);

        buildPageList(currentPage, totalPages).forEach(function(p) {
            if (p === '...') {
                var dot = document.createElement('span');
                dot.textContent = '…';
                dot.style.cssText = 'color:var(--tmut);font-size:13px;padding:0 6px;line-height:34px;';
                wrap.appendChild(dot);
            } else {
                var b = document.createElement('button');
                b.className = 'pg-btn' + (p === currentPage ? ' active' : '');
                b.textContent = p;
                b.onclick = (function(pg) {
                    return function() { currentPage = pg; render(); };
                })(p);
                wrap.appendChild(b);
            }
        });

        var next = document.createElement('button');
        next.className = 'pg-btn pg-arrow';
        next.innerHTML = '<i class="fas fa-chevron-right"></i>';
        next.disabled  = currentPage === totalPages;
        next.onclick   = function() { if (currentPage < totalPages) { currentPage++; render(); } };
        wrap.appendChild(next);
    }

    function buildPageList(cur, total) {
        if (total <= 7) {
            var arr = [];
            for (var i = 1; i <= total; i++) arr.push(i);
            return arr;
        }
        var list = [1];
        if (cur > 3) list.push('...');
        for (var p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) list.push(p);
        if (cur < total - 2) list.push('...');
        list.push(total);
        return list;
    }

    // Override searchTable agar pagination-aware
    window.searchTable = function(inputId, tableId) {
        var val = (document.getElementById(inputId).value || '').toUpperCase();
        filteredRows = allRows.filter(function(r) {
            var td = r.querySelector('td:nth-child(2)');
            return td && (td.textContent || td.innerText).toUpperCase().indexOf(val) > -1;
        });
        currentPage = 1;
        render();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
</body>
</html>