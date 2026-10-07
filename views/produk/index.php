<?php
session_start();
include "../../config/koneksi.php";

// Cek autentikasi login
if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

// PROSES HAPUS DATA
if (isset($_GET['hapus'])) {
    $produk_id = $_GET['hapus'];
    $query     = "DELETE FROM produk WHERE produk_id='$produk_id'";
    if (mysqli_query($koneksi, $query)) {
        header("location: index.php");
        exit();
    }
}

// AMBIL SEMUA DATA PRODUK
$query_produk = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY produk_id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Produk - Toko Elektronik</title>
    <!-- Bootstrap 5 Lokal CSS -->
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { 
            background-color: #f4f6f9; 
        }
        .btn-cyan {
            background-color: #5bc0de;
            color: white;
            border: none;
        }
        .btn-cyan:hover {
            background-color: #31b0d5;
            color: white;
        }
    </style>
</head>
<body>

    <!-- NAVBAR ATAS -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold me-4" href="../../index.php">Toko Elektronik</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Data Barang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../pelanggan/index.php">Data Pelanggan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../user/index.php">Data User</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../laporan/index.php">Laporan</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="../laporan/laporan_batal.php">Laporan Batal</a></li>
                </ul>
                
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">
                        Halo, <?= htmlspecialchars($_SESSION['nama_user']); ?>
                    </span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTENT UTAMA -->
    <div class="container py-4">

        <div class="card shadow-sm border-0 rounded-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark">Data Produk</h5>
            </div>
            <div class="card-body p-4">

                <!-- TOMBOL TAMBAH PRODUK KE HALAMAN TERPISAH -->
                <div class="d-flex justify-content-end mb-3">
                    <a href="tambah.php" class="btn btn-cyan btn-sm px-3">
                        Tambah Produk
                    </a>
                </div>

                <!-- TABEL DATA -->
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th style="width: 140px;">OPSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if (mysqli_num_rows($query_produk) > 0) {
                                while ($row = mysqli_fetch_assoc($query_produk)) { 
                            ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk']); ?></td>
                                    <td>Rp. <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                    <td><?= $row['stok']; ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $row['produk_id']; ?>" class="btn btn-cyan btn-sm text-white py-1 px-2 me-1">
                                            Edit
                                        </a>
                                        <a href="index.php?hapus=<?= $row['produk_id']; ?>" class="btn btn-danger btn-sm py-1 px-2" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data produk.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <!-- Bootstrap 5 Lokal JS -->
    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>