-- ============================================
-- DATABASE: sop_digital_db
-- Sistem SOP Digital - PT. Sinergi Nusantara Integrasi
-- Created: January 2026

-- Create Database
CREATE DATABASE IF NOT EXISTS `sop_digital_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sop_digital_db`;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: categories
-- ============================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: sop
-- ============================================
CREATE TABLE `sop` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(200) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `deskripsi` text,
  `langkah_kerja` text NOT NULL,
  `file_attachment` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kategori_id` (`kategori_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sop_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sop_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Default Data: Users
-- ============================================
-- Password Admin: sinergi
-- Password User: rahul
INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama_lengkap`, `created_at`) VALUES
(1, 'sinergi@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', CURRENT_TIMESTAMP),
(2, 'rahulcandra@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Rahul Candra', CURRENT_TIMESTAMP);

-- ============================================
-- Sample Data: Categories
-- ============================================
INSERT INTO `categories` (`id`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'IT & Teknologi', 'Kategori untuk proses kerja terkait IT dan teknologi', CURRENT_TIMESTAMP),
(2, 'Administrasi', 'Kategori untuk proses kerja administrasi', CURRENT_TIMESTAMP),
(3, 'Keuangan', 'Kategori untuk proses kerja keuangan', CURRENT_TIMESTAMP),
(4, 'SDM', 'Kategori untuk proses kerja sumber daya manusia', CURRENT_TIMESTAMP);

-- ============================================
-- Sample Data: SOP
-- ============================================
INSERT INTO `sop` (`id`, `judul`, `kategori_id`, `deskripsi`, `langkah_kerja`, `file_attachment`, `created_by`, `created_at`) VALUES
(1, 'Prosedur Instalasi Software', 1, 'Panduan lengkap untuk instalasi software di perusahaan', 
'1. Pastikan komputer memenuhi spesifikasi minimum yang dibutuhkan
2. Download software dari sumber resmi atau repository perusahaan
3. Jalankan file installer dengan hak akses administrator
4. Ikuti wizard instalasi step by step
5. Konfigurasikan pengaturan sesuai kebutuhan
6. Restart komputer jika diperlukan
7. Verifikasi instalasi berhasil dengan menjalankan aplikasi
8. Laporkan jika ada masalah kepada IT Support', 
NULL, 1, CURRENT_TIMESTAMP),

(2, 'Prosedur Backup Data', 1, 'Panduan untuk melakukan backup data secara berkala',
'1. Siapkan media penyimpanan backup (eksternal HDD/Cloud)
2. Tentukan data yang perlu di-backup
3. Gunakan software backup yang telah disediakan
4. Pilih opsi Full Backup atau Incremental Backup
5. Tentukan jadwal backup otomatis
6. Jalankan proses backup
7. Verifikasi hasil backup
8. Simpan media backup di tempat aman
9. Update log backup di sistem', 
NULL, 1, CURRENT_TIMESTAMP),

(3, 'Prosedur Pengajuan Cuti', 4, 'Tata cara pengajuan cuti karyawan',
'1. Isi form pengajuan cuti di sistem HRIS
2. Tentukan tanggal mulai dan selesai cuti
3. Pilih jenis cuti (tahunan/sakit/penting)
4. Upload dokumen pendukung jika diperlukan
5. Submit pengajuan ke atasan langsung
6. Tunggu approval dari HRD dan atasan
7. Jika disetujui, cuti akan tercatat di sistem
8. Pastikan pekerjaan sudah didelegasikan sebelum cuti', 
NULL, 1, CURRENT_TIMESTAMP);

-- ============================================
-- Notes for Password Reset
-- ============================================
-- To reset admin password to "admin":
-- UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'sinergi@gmail.com';

-- ============================================
-- End of SQL File
-- ============================================
