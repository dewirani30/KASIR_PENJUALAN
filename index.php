<?php
session_start();
include "config/koneksi.php";

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

// Hitung statistik
$count_produk    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produk"))['total'];
$count_penjualan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM penjualan"))['total'];

// Query 10 Transaksi Terakhir
$query_transaksi = mysqli_query($koneksi, "
    SELECT 
        p.penjualan_id,
        p.tanggal_penjualan,
        p.total_harga,
        COALESCE(pel.nama_pelanggan, 'Umum / Non-Member') AS nama_pelanggan,
        u.nama_user
    FROM penjualan p
    LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.pelanggan_id
    LEFT JOIN user u ON p.user_id = u.user_id
    ORDER BY p.penjualan_id DESC 
    LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Elektronik</title>
    <!-- Bootstrap 5 Lokal CSS -->
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
    </style>
</head>
<body>

    <!-- NAVBAR ATAS -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <!-- Brand / Nama Toko -->
            <a class="navbar-brand fw-bold me-4" href="index.php">Toko Elektronik</a>

            <!-- Toggle Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Menu Navigasi Samping Nama Toko -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="views/produk/index.php">Data Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="views/pelanggan/index.php">Data Pelanggan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="views/user/index.php">Data User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="views/laporan/index.php">Laporan</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="views/laporan/laporan_batal.php">Laporan Batal</a>
                    </li>
                </ul>
                
                <!-- Profile & Logout -->
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">
                        Halo, <?= htmlspecialchars($_SESSION['nama_user']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTENT UTAMA -->
    <div class="container py-4">

        <!-- WELCOME CARD -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="fw-bold text-dark mb-2">Selamat Datang di Sistem Penjualan Elektronik</h2>
                <p class="text-muted mb-0">Kelola data barang, pelanggan, user, dan laporan toko melalui navigasi di atas.</p>
            </div>
        </div>

        <!-- TABEL 10 TRANSAKSI TERAKHIR -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">10 Transaksi Terakhir</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>ID Penjualan</th>
                                <th>Tanggal & Waktu</th>
                                <th>Pelanggan</th>
                                <th>Kasir / Petugas</th>
                                <th class="text-end">Total Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if (mysqli_num_rows($query_transaksi) > 0) {
                                while ($row = mysqli_fetch_assoc($query_transaksi)) { 
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $no++; ?></td>
                                    <td><span class="badge bg-secondary">#<?= $row['penjualan_id']; ?></span></td>
                                    <td><?= date('d-m-Y H:i', strtotime($row['tanggal_penjualan'])); ?></td>
                                    <td><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_user'] ?? 'Sistem'); ?></td>
                                    <td class="text-end fw-bold text-success">
                                        Rp <?= number_format($row['total_harga'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada data transaksi.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap 5 Lokal JS -->
    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>