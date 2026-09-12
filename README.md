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
```

---

## 🚀 Instalasi Lokal

### Prasyarat
- PHP 8.0+
- MySQL / MariaDB
- Web server (Apache/Nginx) atau `php -S localhost:8000` untuk testing cepat

### Langkah

1. **Clone repository**
   ```bash
   git clone <url-repo-anda>
   cd wisma_permata
   ```

2. **Buat database**
   Buat database baru (mis. lewat phpMyAdmin), lalu import `database/schema.sql`.

3. **Konfigurasi koneksi database**
   ```bash
   cp config/db.example.php config/db.php
   ```
   Edit `config/db.php`, sesuaikan `DB_USER`, `DB_PASS`, `DB_NAME` dengan database Anda.

4. **Konfigurasi email (opsional, untuk verifikasi akun)**
   Edit `config/mail.php`, sesuaikan `MAIL_FROM_EMAIL` dengan domain Anda.
   > Catatan: memakai fungsi `mail()` bawaan PHP. Di **localhost**, fitur ini
   > biasanya tidak akan mengirim email sungguhan kecuali Anda konfigurasi
   > mail server lokal (mis. Mailtrap/Mailhog untuk testing).

5. **Buat akun admin pertama**
   Jalankan query berikut di database Anda (password default: `GantiSaya123!`):
   ```sql
   INSERT INTO users (kode_user, nama, email, password, nomor_telepon, role, status)
   VALUES ('PGH0001', 'Admin', 'admin@example.com',
           '$2y$10$XdkjucANOuiLjJmDeAzPA.DC9EDh2HCSnb7X8uCQxFpMi9qpRC88W',
           '081234567890', 'pemilik', 'aktif');
   ```
   **Ganti password ini setelah login pertama kali.**

6. **Jalankan server lokal**
   ```bash
   php -S localhost:8000
   ```
   Buka `http://localhost:8000` di browser.

---

## 🔒 Catatan Keamanan

- File `config/db.php` **tidak** disertakan di repository ini (lihat `.gitignore`) —
  gunakan `config/db.example.php` sebagai referensi.
- Folder `uploads/` sengaja dikosongkan — berisi data privat (foto KTP, bukti
  pembayaran) yang tidak boleh dipublikasikan.
- `database/schema.sql` hanya berisi **struktur tabel**, tanpa data pengguna asli.

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan skripsi/akademik. Silakan sesuaikan lisensi
sesuai kebutuhan Anda (mis. MIT License) jika ingin membuka untuk kontribusi publik.
