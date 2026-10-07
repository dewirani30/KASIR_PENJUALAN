<?php
session_start();
include "../config/koneksi.php";
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

$user_id_login = $_SESSION['user_id'];
$tanggal_hari_ini = date('Y-m-d');

// Proses Batalkan Transaksi oleh Kasir
if (isset($_GET['batal_id'])) {
    $penjualan_id = $_GET['batal_id'];

    $cek = mysqli_query($koneksi, "SELECT status FROM penjualan WHERE penjualan_id = '$penjualan_id'");
    $data_cek = mysqli_fetch_assoc($cek);

    if ($data_cek && $data_cek['status'] == 'sukses') {
        mysqli_begin_transaction($koneksi);
        $berhasil = true;

        // Kembalikan stok produk
        $q_det = mysqli_query($koneksi, "SELECT produk_id, jumlah_produk FROM detail_penjualan WHERE penjualan_id = '$penjualan_id'");
        while ($row = mysqli_fetch_assoc($q_det)) {
            $p_id = $row['produk_id'];
            $qty = $row['jumlah_produk'];
            $update_stok = mysqli_query($koneksi, "UPDATE produk SET stok = stok + $qty WHERE produk_id = '$p_id'");
            if (!$update_stok) $berhasil = false;
        }

        // Ubah status jadi batal
        $ubah_status = mysqli_query($koneksi, "UPDATE penjualan SET status = 'batal' WHERE penjualan_id = '$penjualan_id'");
        if (!$ubah_status) $berhasil = false;

        if ($berhasil) {
            mysqli_commit($koneksi);
            echo "<script>alert('Transaksi berhasil dibatalkan dan stok dikembalikan.'); window.location='riwayat.php';</script>";
        } else {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal membatalkan transaksi!'); window.location='riwayat.php';</script>";
        }
    }
    exit();
}

// Ambil riwayat transaksi hari ini untuk kasir yang sedang login
$query_riwayat = mysqli_query($koneksi, "
    SELECT penjualan.*, pelanggan.nama_pelanggan 
    FROM penjualan 
    LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id 
    WHERE penjualan.user_id = '$user_id_login' AND DATE(penjualan.tanggal_penjualan) = '$tanggal_hari_ini'
    ORDER BY penjualan.tanggal_penjualan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi Kasir - Toko Elektronik</title>
    <!-- Menggunakan CDN Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
    </style>
</head>
<body>

    <!-- Navbar Atas yang Konsisten -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold me-4" href="../../index.php">Toko Elektronik</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="pelanggan/index.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="transaksi/index.php">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="riwayat.php">Riwayat</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">Halo, <?= htmlspecialchars($_SESSION['nama_user'] ?? 'User'); ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">Riwayat Transaksi Hari Ini</h4>
        </div>
        
        <div class="card border-0 shadow-sm rounded-1">
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th style="width: 120px;">No. Nota</th>
                                <th style="width: 100px;">Jam</th>
                                <th>Pelanggan</th>
                                <th style="width: 150px;">Total</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 100px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if(mysqli_num_rows($query_riwayat) > 0) {
                                while ($row = mysqli_fetch_assoc($query_riwayat)) {
                                    $status = $row['status'] ?? 'sukses';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td class="fw-bold">NOTA-<?= sprintf("%04d", $row['penjualan_id']); ?></td>
                                <td><?= date('H:i', strtotime($row['tanggal_penjualan'])); ?></td>
                                <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Umum'); ?></td>
                                <td class="fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <?php if ($status == 'sukses') { ?>
                                        <span class="badge bg-success">Berhasil</span>
                                    <?php } else { ?>
                                        <span class="badge bg-danger">Dibatalkan</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($status == 'sukses') { ?>
                                        <a href="riwayat.php?batal_id=<?= $row['penjualan_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin membatalkan transaksi ini? Stok akan dikembalikan.')">Batalkan</a>
                                    <?php } else { ?>
                                        <span class="text-muted small fst-italic">Batal</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center text-muted py-4 fw-bold'>Belum ada transaksi yang Anda input hari ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>