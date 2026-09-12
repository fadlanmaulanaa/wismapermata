# 🏠 Wisma Permata — Sistem Manajemen Kos/Wisma

Aplikasi web untuk manajemen operasional wisma/kos, dibangun dengan **PHP native**
(tanpa framework) dan **MySQL**. Mendukung dua peran pengguna: **Admin** (pemilik/pegawai)
dan **Penghuni**.

---

## ✨ Fitur Utama

**Untuk Penghuni**
- Registrasi akun dengan **verifikasi email**
- Pemesanan kamar
- Pembayaran sewa (upload bukti transfer) — bulan otomatis berurutan, bisa bayar 1–12 bulan sekaligus
- Pengajuan aduan/komplain
- Pemesanan menu kantin
- Notifikasi real-time (jatuh tempo sewa, status pembayaran, dll)

**Untuk Admin (Pemilik/Pegawai)**
- Dashboard ringkasan operasional
- Kelola data kamar & status ketersediaan
- Kelola & verifikasi pemesanan kamar
- Verifikasi bukti pembayaran sewa
- Pantau masa sewa & jatuh tempo (dihitung otomatis dari tanggal huni)
- Kelola aduan penghuni
- Kelola menu & pesanan kantin
- Laporan operasional & laporan hunian

---

## 🛠️ Teknologi

- **Backend:** PHP native (prosedural, tanpa framework)
- **Database:** MySQL / MariaDB
- **Frontend:** Bootstrap 5, Bootstrap Icons
- **Email:** PHP `mail()` bawaan (untuk verifikasi akun)

---

## 📂 Struktur Proyek

```
├── admin/              # Halaman untuk role admin (pemilik/pegawai)
├── penghuni/            # Halaman untuk role penghuni
├── config/              # Koneksi database, helper functions, konfigurasi email
├── includes/            # Komponen bersama (header, footer, sidebar)
├── database/            # Skema database (schema.sql)
├── uploads/              # Folder penyimpanan file upload (kosong di repo ini)
├── index.php             # Landing page
├── login.php             # Halaman login
├── register.php          # Halaman registrasi
├── verify.php             # Verifikasi email
└── resend_verifikasi.php  # Kirim ulang email verifikasi

