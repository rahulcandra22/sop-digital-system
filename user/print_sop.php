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

// =========================================================================
// PROTEKSI: Cek Status SOP. Hanya boleh dicetak jika statusnya "Disetujui"
// =========================================================================
if (strtolower(trim($sop['status'])) !== 'disetujui') {
    $status_text = htmlspecialchars($sop['status']);
    
    // Tampilan UI "Dokumen Terbatas" 
    die("
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='color-scheme' content='light'>
        <title>Dokumen Terbatas - SOP Digital</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' rel='stylesheet'>
        <style>
            /* CSS Variables untuk Light Mode (Sangat Terang & Bersih) */
            :root {
                --bg-body: #f3f4f6;
                --bg-card: #ffffff;
                --text-primary: #111827;
                --text-secondary: #4b5563;
                --border-color: #e5e7eb;
                --accent-color: #2563eb;
                --icon-bg: #eff6ff;
                --icon-color: #2563eb;
                --status-box-bg: #f9fafb;
                --badge-bg: #fef3c7;
                --badge-text: #92400e;
                --btn-bg: #2563eb; 
                --btn-text: #ffffff;
                --btn-hover: #1d4ed8;
                --shadow-card: 0 10px 40px rgba(0, 0, 0, 0.06);
            }

            body {
                background-color: var(--bg-body);
                font-family: 'Inter', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                color: var(--text-primary);
                transition: background-color 0.3s ease, color 0.3s ease;
            }

            .corporate-card {
                background: var(--bg-card);
                width: 100%;
                max-width: 460px;
                padding: 48px 40px;
                border-radius: 16px;
                box-shadow: var(--shadow-card);
                text-align: center;
                border: 1px solid var(--border-color);
                box-sizing: border-box;
                position: relative;
                overflow: hidden;
                transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            }

            .corporate-card::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: var(--accent-color);
            }

            .icon-container {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 64px;
                height: 64px;
                border-radius: 50%;
                background: var(--icon-bg);
                color: var(--icon-color);
                margin-bottom: 24px;
                transition: background-color 0.3s ease, color 0.3s ease;
            }

            .icon-container svg { width: 32px; height: 32px; }

            h1 {
                font-size: 22px;
                font-weight: 700;
                margin: 0 0 12px 0;
                letter-spacing: -0.02em;
            }

            p.desc {
                font-size: 15px;
                line-height: 1.6;
                color: var(--text-secondary);
                margin: 0 0 32px 0;
                transition: color 0.3s ease;
            }

            .status-box {
                background: var(--status-box-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 16px;
                margin-bottom: 32px;
                text-align: left;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: all 0.3s ease;
            }

            .status-left { display: flex; flex-direction: column; }

            .status-title {
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: var(--text-secondary);
                font-weight: 600;
                margin-bottom: 4px;
            }

            .status-desc { font-size: 13px; font-weight: 500; }

            .status-value {
                background: var(--badge-bg);
                color: var(--badge-text);
                padding: 6px 14px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
            }

            .btn-corporate {
                background: var(--btn-bg);
                color: var(--btn-text);
                border: none;
                padding: 14px 24px;
                font-size: 14px;
                font-weight: 600;
                border-radius: 8px;
                cursor: pointer;
                width: 100%;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .btn-corporate:hover {
                background: var(--btn-hover);
                transform: translateY(-1px);
            }

            .footer-text {
                margin-top: 32px;
                font-size: 12px;
                color: var(--text-secondary);
                font-weight: 500;
            }
        </style>
    </head>
    <body>
        <div class='corporate-card'>
            <div class='icon-container'>
                <svg fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'></path>
                </svg>
            </div>
            
            <h1>Akses Cetak Terkunci</h1>
            <p class='desc'>Dokumen SOP ini belum melewati tahap otorisasi final dan tidak dapat dicetak.</p>
            
            <div class='status-box'>
                <div class='status-left'>
                    <span class='status-title'>Status Dokumen</span>
                    <span class='status-desc'>Menunggu Persetujuan</span>
                </div>
                <div class='status-value'>{$status_text}</div>
            </div>
            
            <button class='btn-corporate' onclick='goBack()'>
                <svg width='18' height='18' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 19l-7-7m0 0l7-7m-7 7h18'></path></svg>
                Tutup & Kembali
            </button>
            
            <div class='footer-text'>
                &copy; " . date('Y') . " SOP Digital System - PT. Sinergi Nusantara Integrasi
            </div>
        </div>

        <script>
            function goBack() {
                if (document.referrer && document.referrer.indexOf(window.location.hostname) !== -1) {
                    window.history.back();
                } else {
                    window.close();
                    setTimeout(function() {
                        window.location.href = '../index.php'; 
                    }, 250);
                }
            }
        </script>
    </body>
    </html>
    ");
}
// =========================================================================

$print_by = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Administrator');

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['REQUEST_URI']), '/'); 
 
$sop_online_url = $protocol . "://" . $host . $path . "/view_sop.php?id=" . $id;
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=0&data=" . urlencode($sop_online_url);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name='color-scheme' content='light'>
    <title>Cetak SOP - <?php echo htmlspecialchars($sop['judul']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 15mm 20mm;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #e2e8f0; /* Terang secara default */
            font-family: 'Inter', Arial, sans-serif;
            color: #1e293b;
            transition: background 0.3s ease;
        }

        /* Kertas Print (Harus selalu putih murni) */
        .paper-preview {
            background: #ffffff !important;
            width: 210mm;
            min-height: 297mm;
            margin: 40px auto;
            padding: 20mm 20mm 25mm 20mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
            color: #000000 !important; /* Memastikan font di atas kertas selalu hitam */
        }

        /* WATERMARK */
        .watermark-img {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
        }

        .content-wrapper { position: relative; z-index: 1; }
        h1, h2, h3, p { margin: 0; }
        .text-center { text-align: center; }
        .text-bold { font-weight: 700; }
        .text-sm { font-size: 11px; }
        
        .iso-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 13px;
        }
        
        .iso-table th, .iso-table td {
            border: 1.5px solid #1e293b;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .header-bg {
            background-color: #f1f5f9 !important;
            font-weight: 600;
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        .status-badge-doc {
            display: inline-block;
            border: 1px solid #1e293b;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
            color: #1e293b;
        }

        .logo-container img { max-width: 100px; max-height: 60px; object-fit: contain; }
        .doc-title { font-size: 19px; text-transform: uppercase; letter-spacing: 1px; color: #0f172a; font-weight: 800; }

        .content-section { margin-bottom: 25px; page-break-inside: auto; }
        
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

        tr, td, .content-section, .signature-block { page-break-inside: avoid; }

        .signature-block { width: 100%; border-collapse: collapse; margin-top: 30px; page-break-inside: avoid; }
        .signature-block td { width: 33.33%; text-align: center; padding: 10px; font-size: 13px; border: none; vertical-align: top; }
        .sign-space { height: 90px; display: flex; justify-content: center; align-items: center; margin: 5px 0; }
        .sign-name { font-weight: 700; text-decoration: underline; margin-bottom: 4px; font-size: 14px; }

        .doc-footer {
            margin-top: 40px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 10px;
            color: #64748b;
            text-align: center;
            line-height: 1.6;
        }

        .doc-footer .warning-text { font-weight: 700; color: #dc2626; text-transform: uppercase; font-size: 11px; margin-bottom: 4px; }

        @media print {
            html, body { background: #ffffff !important; }
            .paper-preview { margin: 0; padding: 0; box-shadow: none; width: 100%; min-height: auto; border: none; }
            .header-bg, .section-header { background-color: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .doc-footer .warning-text { color: #000000 !important; }
        }
    </style>
</head>
<body>

<img src="../assets/images/logo.png" class="watermark-img" alt="Watermark">

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
                    <div class="status-badge-doc">DOKUMEN TERKENDALI</div><br>
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
                    <div class="sign-space">
                        <img src="../assets/images/ttd.png" alt="Tanda Tangan" style="max-height: 80px; max-width: 150px; object-fit: contain;" onerror="this.style.display='none'">
                    </div>
                    <div class="sign-name">Nugroho Hermanto</div>
                    <div class="text-sm" style="color:#64748b;">Direktur</div>
                </td>
                <td>
                    <p>Verifikasi Dokumen</p>
                    <div class="sign-space">
                        <img src="<?php echo $qr_api_url; ?>" alt="QR Code Verifikasi" style="height: 75px; width: 75px;">
                    </div>
                    <div class="text-sm" style="color:#64748b;">Scan QR untuk cek validitas<br>secara online</div>
                </td>
            </tr>
        </table>

        <div class="doc-footer">
            <div class="warning-text">PERINGATAN: DOKUMEN FISIK ADALAH SALINAN TIDAK TERKENDALI (UNCONTROLLED COPY)</div>
            SOP Digital System &copy; <?php echo date('Y'); ?> | 
            Dicetak oleh: <strong><?php echo htmlspecialchars($print_by); ?></strong> | 
            Waktu Cetak: <?php echo date('d M Y, H:i'); ?> WIB
        </div>

    </div>
</div>

<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 1000);
    }
</script>

</body>
</html>