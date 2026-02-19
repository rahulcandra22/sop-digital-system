<?php
require_once '../config/database.php';
require_once '../includes/session.php';
requireLogin();

if (!isset($_GET['id'])) {
    die("SOP tidak ditemukan.");
}

$id = intval($_GET['id']);
$sql = "SELECT s.*, c.nama_kategori, u.nama_lengkap as creator FROM sop s 
        LEFT JOIN categories c ON s.kategori_id = c.id 
        LEFT JOIN users u ON s.created_by = u.id 
        WHERE s.id = $id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    die("Data tidak ditemukan.");
}

$sop = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak SOP - <?php echo htmlspecialchars($sop['judul']); ?></title>
<style>
    @page {
        size: A4;
        margin: 20mm;
    }

    /* Background gelap seperti tema app */
    html, body {
        margin: 0;
        padding: 20px;
        background: #020617 !important; /* ← INI YANG PENTING, body harus sama */
        min-height: 100vh;
    }

    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #000;
        line-height: 1.6;
    }

    /* Kertas putih di tengah */
    .paper {
        background: #ffffff;
        max-width: 794px;
        margin: 0 auto;
        padding: 40px;
        box-shadow: 0 0 60px rgba(59, 130, 246, 0.2), 0 0 100px rgba(0,0,0,0.8);
        border-radius: 4px;
        color: #000;
    }

    /* Saat print: background gelap tidak ikut tercetak */
    @media print {
        html, body {
            background: #ffffff !important;
            padding: 0 !important;
        }
        .paper {
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
    }

    .header {
        text-align: center;
        margin-bottom: 20px;
    }

    .header img {
        height: 55px;
    }

    .header h2 {
        margin: 10px 0 0 0;
        font-size: 16px;
        border-top: 2px solid #000;
        border-bottom: 1px solid #000;
        padding: 8px 0;
        text-transform: uppercase;
    }

    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        margin-bottom: 20px;
    }

    .meta-table td {
        border: 1px solid #000;
        padding: 6px 10px;
        vertical-align: top;
        text-align: left;
    }

    .section-title {
        width: 100%;
        border: 1px solid #000;
        padding: 6px 10px;
        font-weight: bold;
        text-transform: uppercase;
        box-sizing: border-box;
        background: #fff;
        margin-top: -1px;
    }

    .content-area {
        width: 100%;
        padding: 10px 10px 20px 10px;
        text-align: left;
        white-space: pre-wrap;
        box-sizing: border-box;
        border: none;
        margin: 0;
    }

    .sig-table {
        width: 100%;
        margin-top: 30px;
        border-collapse: collapse;
    }

    .sig-table td {
        width: 50%;
        text-align: center;
        border: none;
        padding: 0;
    }

    .sig-space {
        height: 70px;
    }

    .footer {
        position: fixed;
        bottom: 10mm;
        width: 100%;
        text-align: center;
        font-size: 10px;
        color: #333;
    }
</style>
</head>
<body>
<div class="paper"> <!-- ← KERTAS PUTIH DI TENGAH -->

    <div class="header">
        <img src="../assets/images/logo.png" alt="Logo">
        <h2>STANDARD OPERATING PROCEDURE (SOP)</h2>
    </div>

    <table class="meta-table">
        <tr>
            <td width="25%"><strong>Judul SOP</strong></td>
            <td><?php echo htmlspecialchars($sop['judul']); ?></td>
        </tr>
        <tr>
            <td><strong>Kategori</strong></td>
            <td><?php echo htmlspecialchars($sop['nama_kategori']); ?></td>
        </tr>
        <tr>
            <td><strong>Dibuat Oleh</strong></td>
            <td><?php echo htmlspecialchars($sop['creator']); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Dibuat</strong></td>
            <td><?php echo date('d F Y', strtotime($sop['created_at'])); ?></td>
        </tr>
        <tr>
            <td><strong>Terakhir Update</strong></td>
            <td><?php echo date('d F Y', strtotime($sop['updated_at'])); ?></td>
        </tr>
    </table>

    <div class="section-title">DESKRIPSI</div>
    <div class="content-area"><?php echo nl2br(htmlspecialchars($sop['deskripsi'])); ?></div>

    <div class="section-title">LANGKAH-LANGKAH KERJA</div>
    <div class="content-area"><?php echo nl2br(htmlspecialchars($sop['langkah_kerja'])); ?></div>

    <table class="sig-table">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <div class="sig-space"></div>
                <p><strong>( <?php echo htmlspecialchars($sop['creator']); ?> )</strong></p>
            </td>
            <td>
                <p>Disetujui Oleh,</p>
                <div class="sig-space"></div>
                <p><strong>( ______________________ )</strong></p>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dokumen SOP Digital - <?php echo htmlspecialchars($sop['judul']); ?> - Dicetak pada <?php echo date('d/m/Y H:i'); ?>
    </div>

</div> <!-- akhir .paper -->

<script>
    // Delay sedikit agar background gelap sempat render sebelum dialog print
    setTimeout(function() {
        window.print();
    }, 300);
</script>

</body>
</html>