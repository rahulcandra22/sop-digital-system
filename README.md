# SOP Digital System - PT. Sinergi Nusantara Integrasi

Sistem Manajemen Dokumen Standard Operating Procedure (SOP) berbasis web yang dirancang untuk mempermudah akses, pengelolaan, dan standarisasi operasional di lingkungan perusahaan.

## 📁 Struktur Proyek
- `/admin` : Modul khusus admin untuk manajemen data.
- `/user` : Modul akses untuk karyawan atau user.
- `/config` : Konfigurasi database dan sistem.
- `/assets` : Kumpulan file HTML, CSS, JS, dan (Logo/Gambar).
- `/includes` : File logic yang digunakan berulang (seperti session).
- `/docs` : Dokumentasi database (database.sql).

## ✨ Fitur Utama
- **Dashboard Interaktif**: Ringkasan jumlah data kategori dan SOP yang tersedia.
- **Authentication & Authorization**: Sistem login aman dengan pemisahan hak akses (Admin & User).
- **Manajemen SOP (AJAX)**: Pengelolaan dokumen SOP dan kategori menggunakan AJAX untuk pengalaman pengguna yang lebih cepat.
- **Forgot & Reset Password**: Fitur pemulihan akun melalui token jika user lupa kata sandi.
- **Preview & Print**: Membaca dokumen SOP secara langsung di browser dan mencetaknya dengan format yang rapi.
- **Digital Asset**: Penggunaan tanda tangan digital (`ttd.png`) dan logo perusahaan pada dokumen.

## 🛠️ Teknologi yang Digunakan
- **Backend**: PHP 8.2 (Native)
- **Database**: MySQL
- **Frontend**: HTML, CSS & JavaScript
- **Server**: XAMPP (Apache dan MySQL)

## 🚀 Cara Instalasi
1. Pindahkan folder proyek ini ke direktori `htdocs` (XAMPP) Anda.
2. Buat database baru di phpMyAdmin dengan nama `sop_digital_db`.
3. Import file `docs/database.sql` ke dalam database tersebut.
4. Sesuaikan kredensial (admin, user, password) pada file `config/database.php`.
5. Jalankan `localhost/sop-digital-system` pada browser Anda.

## 🔑 Akun Demo
Gunakan akun berikut untuk masuk ke dalam sistem:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@sinergi.co.id` | `sinergi` |
| **User (Rahul Candra)** | `rahulcandra@sinergi.co.id` | `rahulcandra` |
| **User (Bulan Hidayatul)** | `bulanhidayatul@sinergi.co.id` | `bulanhidayatul` |
| **User (Zaenal Fanani)** | `zaenalfanani@sinergi.co.id` | `zaenalfanani` |
| **User (Latif Junia)** | `latifjunia@sinergi.co.id` | `latifjunia` |
| **User (Roykhan Pratama)** | `roykhanpratama@sinergi.co.id` | `roykhanpratama` |
| **User (Prudenta Fajar)** | `prudentafajar@sinergi.co.id` | `prudentafajar` |

## 👨‍💻 Kontributor
**Internship Project 2026** Dibuat oleh **Rahul Candra** sebagai mahasiswa **Universitas PGRI Semarang** bagian dari program magang di PT. Sinergi Nusantara Integrasi.