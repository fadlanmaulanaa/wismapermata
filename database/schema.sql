-- =====================================================
-- Wisma Permata — Database Schema (STRUKTUR SAJA)
-- =====================================================
-- File ini HANYA berisi struktur tabel (CREATE TABLE),
-- TANPA data pengguna asli — aman untuk repository publik.
--
-- Cara pakai:
-- 1. Buat database baru di MySQL/MariaDB (mis. lewat phpMyAdmin)
-- 2. Import file ini ke database tersebut
-- 3. (Opsional) Buat akun admin pertama secara manual, contoh:
--
--    INSERT INTO users (kode_user, nama, email, password, nomor_telepon, role, status)
--    VALUES ('PGH0001', 'Admin', 'admin@example.com',
--            '$2y$10$XdkjucANOuiLjJmDeAzPA.DC9EDh2HCSnb7X8uCQxFpMi9qpRC88W',
--            '081234567890', 'pemilik', 'aktif');
--    -- Password hash di atas = "GantiSaya123!". GANTI setelah login pertama kali!
-- =====================================================

-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 27, 2026 at 02:57 PM
-- Server version: 11.4.13-MariaDB
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wisma_permata`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pemesanan_kantin`
--

CREATE TABLE `detail_pemesanan_kantin` (
  `id` int(11) NOT NULL,
  `pemesanan_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kamar`
--

CREATE TABLE `kamar` (
  `id` int(11) NOT NULL,
  `kode_kamar` varchar(10) DEFAULT NULL,
  `nomor_kamar` varchar(10) NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `ukuran` varchar(30) DEFAULT NULL,
  `harga_per_bulan` decimal(10,2) NOT NULL,
  `status` enum('tersedia','dipesan','terisi') DEFAULT 'tersedia',
  `lantai` int(11) DEFAULT 1,
  `deskripsi` text DEFAULT NULL,
  `fasilitas` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `kamar_foto`
--

CREATE TABLE `kamar_foto` (
  `id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `urutan` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `konfirmasi_huni`
--

CREATE TABLE `konfirmasi_huni` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `periode_bulan` varchar(20) NOT NULL,
  `status` enum('menunggu','lanjut','keluar') DEFAULT 'menunggu',
  `tanggal_kirim` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_respon` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `laporan_aduan`
--

CREATE TABLE `laporan_aduan` (
  `id` int(11) NOT NULL,
  `kode_aduan` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `kategori` enum('fasilitas','keamanan','kebersihan','lainnya') DEFAULT 'fasilitas',
  `judul` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('pending','ditangani','selesai','ditolak') DEFAULT 'pending',
  `catatan_pengelola` text DEFAULT NULL,
  `teknisi` varchar(100) DEFAULT NULL,
  `ditangani_oleh` int(11) DEFAULT NULL,
  `tanggal_aduan` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_selesai` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `menu_kantin`
--

CREATE TABLE `menu_kantin` (
  `id` int(11) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` enum('makanan','minuman','snack') DEFAULT 'makanan',
  `harga` decimal(10,2) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tersedia` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('info','success','warning','danger') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran_sewa`
--

CREATE TABLE `pembayaran_sewa` (
  `id` int(11) NOT NULL,
  `kode_pembayaran` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `bulan_bayar` varchar(20) DEFAULT NULL,
  `tanggal_bayar` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode` enum('transfer') DEFAULT 'transfer',
  `periode` enum('bulanan','tahunan') NOT NULL DEFAULT 'bulanan',
  `jumlah_bulan` int(11) NOT NULL DEFAULT 1,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','ditolak') DEFAULT 'pending',
  `keterangan` text DEFAULT NULL,
  `diverifikasi_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan_kamar`
--

CREATE TABLE `pemesanan_kamar` (
  `id` int(11) NOT NULL,
  `kode_pemesanan` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `tanggal_pesan` timestamp NOT NULL DEFAULT current_timestamp(),
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` enum('pending','disetujui','ditolak','selesai') DEFAULT 'pending',
  `batas_bayar` datetime DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `diproses_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan_kantin`
--

CREATE TABLE `pemesanan_kantin` (
  `id` int(11) NOT NULL,
  `kode_pesanan` varchar(10) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `metode_bayar` enum('cash','qris') DEFAULT 'cash',
  `status` enum('pending','diproses','diantar','selesai','ditolak') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `tanggal_pesan` timestamp NOT NULL DEFAULT current_timestamp(),
  `diproses_oleh` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `kode_user` varchar(10) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `nomor_ktp` varchar(20) DEFAULT NULL,
  `nomor_kamar` varchar(10) DEFAULT NULL,
  `role` enum('pemilik','pegawai','penghuni','calon_penghuni') DEFAULT 'calon_penghuni',
  `status` enum('aktif','nonaktif','pending') DEFAULT 'pending',
  `foto_ktp` varchar(255) DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `token_expired_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
--

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pemesanan_kantin`
--
ALTER TABLE `detail_pemesanan_kantin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pemesanan_id` (`pemesanan_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kamar` (`nomor_kamar`),
  ADD UNIQUE KEY `kode_kamar` (`kode_kamar`);

--
-- Indexes for table `kamar_foto`
--
ALTER TABLE `kamar_foto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `konfirmasi_huni`
--
ALTER TABLE `konfirmasi_huni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `laporan_aduan`
--
ALTER TABLE `laporan_aduan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_aduan` (`kode_aduan`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `menu_kantin`
--
ALTER TABLE `menu_kantin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pembayaran_sewa`
--
ALTER TABLE `pembayaran_sewa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pembayaran` (`kode_pembayaran`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `pemesanan_kamar`
--
ALTER TABLE `pemesanan_kamar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pemesanan` (`kode_pemesanan`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indexes for table `pemesanan_kantin`
--
ALTER TABLE `pemesanan_kantin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `kode_user` (`kode_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pemesanan_kantin`
--
ALTER TABLE `detail_pemesanan_kantin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `kamar_foto`
--
ALTER TABLE `kamar_foto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `konfirmasi_huni`
--
ALTER TABLE `konfirmasi_huni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `laporan_aduan`
--
ALTER TABLE `laporan_aduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `menu_kantin`
--
ALTER TABLE `menu_kantin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `pembayaran_sewa`
--
ALTER TABLE `pembayaran_sewa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `pemesanan_kamar`
--
ALTER TABLE `pemesanan_kamar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `pemesanan_kantin`
--
ALTER TABLE `pemesanan_kantin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pemesanan_kantin`
--
ALTER TABLE `detail_pemesanan_kantin`
  ADD CONSTRAINT `detail_pemesanan_kantin_ibfk_1` FOREIGN KEY (`pemesanan_id`) REFERENCES `pemesanan_kantin` (`id`),
  ADD CONSTRAINT `detail_pemesanan_kantin_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menu_kantin` (`id`);

--
-- Constraints for table `kamar_foto`
--
ALTER TABLE `kamar_foto`
  ADD CONSTRAINT `kamar_foto_ibfk_1` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konfirmasi_huni`
--
ALTER TABLE `konfirmasi_huni`
  ADD CONSTRAINT `konfirmasi_huni_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `konfirmasi_huni_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`);

--
-- Constraints for table `laporan_aduan`
--
ALTER TABLE `laporan_aduan`
  ADD CONSTRAINT `laporan_aduan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `pembayaran_sewa`
--
ALTER TABLE `pembayaran_sewa`
  ADD CONSTRAINT `pembayaran_sewa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pembayaran_sewa_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`);

--
-- Constraints for table `pemesanan_kamar`
--
ALTER TABLE `pemesanan_kamar`
  ADD CONSTRAINT `pemesanan_kamar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `pemesanan_kamar_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`);

--
-- Constraints for table `pemesanan_kantin`
--
ALTER TABLE `pemesanan_kantin`
  ADD CONSTRAINT `pemesanan_kantin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
