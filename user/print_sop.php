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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Pengaturan Kertas & Layar */
        @page {
            size: A4;
            margin: 15mm 20mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #0f172a;
            font-family: 'Inter', Arial, sans-serif;
            color: #1e293b;
        }

        /* Preview di Layar Monitor */
        .paper-preview {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 40px auto;
            padding: 20mm 20mm 25mm 20mm; /* Padding bawah lebih besar untuk footer */
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            box-sizing: border-box;
            position: relative;
            overflow: hidden; /* Untuk menjaga watermark tetap di dalam */
        }

        /* WATERMARK LOGO DI TENGAH KERTAS */
        .paper-preview::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background-image: url('../assets/images/logo.png'); /* Pastikan path logo benar */
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0.04; /* Sangat transparan agar teks tetap terbaca */
            z-index: 0;
            pointer-events: none;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        /* Elemen z-index agar di atas watermark */
        .content-wrapper {
            position: relative;
            z-index: 1;
        }

        /* Tipografi Dasar */
        h1, h2, h3, p { margin: 0; }
        .text-center { text-align: center; }
        .text-bold { font-weight: 700; }
        .text-sm { font-size: 11px; }
        
        /* Tabel Utama ISO Style */
        .iso-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
        }
        
        .iso-table th, .iso-table td {
            border: 1.5px solid #1e293b; /* Garis lebih tegas */
            padding: 8px 12px;
            vertical-align: middle;
        }

        .header-bg {
            background-color: #f1f5f9 !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        /* Cap Dokumen Terkendali */
        .status-badge {
            display: inline-block;
            border: 1px solid #1e293b;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
            color: #1e293b;
        }

        /* Logo Area */
        .logo-container img {
            max-width: 100px;
            max-height: 60px;
            object-fit: contain;
        }

        /* Judul SOP Besar */
        .doc-title {
            font-size: 19px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
            font-weight: 800;
        }

        /* Konten Isi SOP */
        .content-section {
            margin-bottom: 25px;
            page-break-inside: auto;
        }
        
        .section-header {
            background-color: #f1f5f9 !important;
            border: 1.5px solid #1e293b;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        .section-body {
            border: 1.5px solid #1e293b;
            border-top: none;
            padding: 15px 15px;
            font-size: 13px;
            line-height: 1.7;
            white-space: pre-wrap;
            min-height: 60px;
            text-align: justify;
        }

        /* Blok Tanda Tangan */
        .signature-block {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            page-break-inside: avoid; /* Anti terpotong halaman */
        }

        .signature-block td {
            width: 50%;
            text-align: center;
            padding: 10px;
            font-size: 13px;
            border: none;
        }

        .sign-space {
            height: 90px; /* Ruang lebih lega untuk TTD */
        }

        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 4px;
            font-size: 14px;
        }

        /* Footer Dokumen (Hanya muncul di paling bawah kertas) */
        .doc-footer {
            position: absolute;
            bottom: 10mm;
            left: 20mm;
            right: 20mm;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            font-size: 10px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }

        /* Pengaturan Cetak Asli (Print Mode) */
        @media print {
            body { background: none; }
            .paper-preview {
                margin: 0;
                padding: 0;
                box-shadow: none;
                width: 100%;
                min-height: auto;
                border: none;
            }
            
            .header-bg, .section-header {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .paper-preview::before {
                opacity: 0.05 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .doc-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
            }
        }
    </style>
</head>
<body>

<div class="paper-preview">
    <div class="content-wrapper">
        
        <table class="iso-table text-center">
            <tr>
                <td rowspan="3" width="22%" class="logo-container">
                    <img src="../assets/images/logo.png" alt="Logo Perusahaan" onerror="this.src='https://cdn-icons-png.flaticon.com/512/2991/2991148.png'">
                </td>
                <td rowspan="3" width="48%">
                    <h2 class="doc-title">STANDARD OPERATING PROCEDURE</h2>
                    <div style="margin-top: 5px; font-weight: 500; font-size: 14px;">
                        Kategori: <?php echo htmlspecialchars($sop['nama_kategori']); ?>
                    </div>
                </td>
                <td width="30%" class="header-bg text-sm">
                    <div class="status-badge">DOKUMEN TERKENDALI</div><br>
                    Nomor Dokumen
                </td>
            </tr>
            <tr>
                <td class="text-bold" style="font-size: 14px;">SOP-<?php echo str_pad($sop['id'], 4, '0', STR_PAD_LEFT); ?></td>
            </tr>
            <tr>
                <td class="text-sm">Tgl. Berlaku: <strong><?php echo date('d/m/Y', strtotime($sop['created_at'])); ?></strong></td>
            </tr>
        </table>

        <table class="iso-table">
            <tr>
                <td width="22%" class="header-bg">Judul Prosedur</td>
                <td colspan="3" class="text-bold" style="font-size: 15px;"><?php echo htmlspecialchars($sop['judul']); ?></td>
            </tr>
            <tr>
                <td width="22%" class="header-bg">Dibuat Oleh</td>
                <td width="30%"><?php echo htmlspecialchars($sop['creator']); ?></td>
                <td width="20%" class="header-bg">Revisi Terakhir</td>
                <td width="28%"><?php echo date('d F Y', strtotime($sop['updated_at'])); ?></td>
            </tr>
        </table>

        <div class="content-section">
            <div class="section-header">1. Tujuan & Ruang Lingkup</div>
            <div class="section-body"><?php echo nl2br(htmlspecialchars($sop['deskripsi'] ? $sop['deskripsi'] : 'Tidak ada deskripsi tambahan.')); ?></div>
        </div>

        <div class="content-section">
            <div class="section-header">2. Prosedur / Langkah Kerja</div>
            <div class="section-body" style="font-family: inherit;"><?php echo nl2br(htmlspecialchars($sop['langkah_kerja'])); ?></div>
        </div>

        <table class="signature-block">
            <tr>
                <td>
                    <p>Disusun Oleh,</p>
                    <div class="sign-space"></div>
                    <div class="sign-name"><?php echo htmlspecialchars($sop['creator']); ?></div>
                    <div class="text-sm" style="color:#64748b;">Staff / Pembuat SOP</div>
                </td>
                <td>
                    <p>Mengetahui & Disetujui Oleh,</p>
                    <div class="sign-space"></div>
                    <div class="sign-name">Nugroho Hermanto</div>
                    <div class="text-sm" style="color:#64748b;">Direktur</div>
                </td>
            </tr>
        </table>

    </div>
    
    <div class="doc-footer">
        <div>SOP Digital System &copy; <?php echo date('Y'); ?></div>
        <div>Dicetak pada: <?php echo date('d M Y, H:i'); ?> WIB</div>
    </div>
</div>

<script>
    window.onload = function() {
        // Beri jeda sedikit agar watermark dan CSS ter-load sempurna sebelum dialog print muncul
        setTimeout(function() {
            window.print();
        }, 800);
    }
</script>

</body>
</html>