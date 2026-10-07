<?php
session_start();
include "../../config/koneksi.php";
date_default_timezone_set('Asia/Jakarta');

// Cek apakah user sudah login dan pastikan dia admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../../login.php");
    exit();
}

$dari_tanggal   = $_GET['dari_tanggal'] ?? date('Y-m-d');
$sampai_tanggal = $_GET['sampai_tanggal'] ?? date('Y-m-d');

// Query khusus mengambil transaksi yang statusnya 'batal'
$query_batal = mysqli_query($koneksi, "
    SELECT 
        penjualan.*, 
        pelanggan.nama_pelanggan, 
        user.nama_user AS nama_kasir
    FROM penjualan
    LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id
    LEFT JOIN user ON penjualan.user_id = user.user_id
    WHERE penjualan.status = 'batal' 
    AND DATE(penjualan.tanggal_penjualan) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'
    ORDER BY penjualan.tanggal_penjualan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi Batal - Toko Elektronik</title>
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .card-header-red { background-color: #4f4fd9; color: white; font-weight: bold; }
        .transaksi-batal { background-color: #f8d7da !important; color: #721c24; }
    </style>
</head>
<body>

    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold me-4" href="../../index.php">Toko Elektronik</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../../index.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="../produk/index.php">Data Barang</a></li>
                    <li class="nav-item"><a class="nav-link" href="../pelanggan/index.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../user/index.php">Data User</a></li>
                     <li class="nav-item"><a class="nav-link" href="index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="laporan_batal.php">Laporan Batal</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">Halo, <?= htmlspecialchars($_SESSION['nama_user'] ?? 'Admin'); ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">
        <!-- Filter Tanggal -->
        <div class="card border-0 shadow-sm rounded-1 mb-4">
            <div class="card-header card-header-red py-2 px-3">
                ⚠️ Filter Laporan Transaksi Batal oleh Kasir
            </div>
            <div class="card-body p-3">
                <form method="GET" action="laporan_batal.php" class="row align-items-center g-3">
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold mb-0">Dari Tanggal :</label>
                        <input type="date" name="dari_tanggal" class="form-control" value="<?= $dari_tanggal; ?>" required>
                    </div>
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold mb-0">Sampai Tanggal :</label>
                        <input type="date" name="sampai_tanggal" class="form-control" value="<?= $sampai_tanggal; ?>" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-danger fw-bold">Filter Data</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Riwayat Batal -->
        <div class="card border-0 shadow-sm rounded-1">
            <div class="card-body p-3">
                <h5 class="fw-bold mb-3">Daftar Nota Transaksi yang Dibatalkan Kasir</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">No</th>
                                <th style="width: 120px;">No. Nota</th>
                                <th style="width: 140px;">Tanggal & Jam</th>
                                <th style="width: 150px;">Pelanggan</th>
                                <th style="width: 110px;">Kasir</th>
                                <th>Detail Produk yang Dibatalkan</th>
                                <th class="text-end" style="width: 140px;">Total Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($query_batal) > 0) {
                                while ($row = mysqli_fetch_array($query_batal)) {
                                    $penjualan_id = $row['penjualan_id'];
                            ?>
                                    <tr class="transaksi-batal">
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="fw-bold">NOTA-<?= sprintf("%04d", $row['penjualan_id']); ?><br><span class="badge bg-danger">DIBATALKAN</span></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])); ?></td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Pelanggan Umum'); ?></td>
                                        <td><?= htmlspecialchars($row['nama_kasir'] ?? '-'); ?></td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <?php
                                                $q_det = mysqli_query($koneksi, "SELECT detail_penjualan.*, produk.nama_produk FROM detail_penjualan LEFT JOIN produk ON detail_penjualan.produk_id = produk.produk_id WHERE penjualan_id='$penjualan_id'");
                                                while ($det = mysqli_fetch_array($q_det)) {
                                                    echo "<li>" . htmlspecialchars($det['nama_produk']) . " (" . $det['jumlah_produk'] . "x) = Rp " . number_format($det['subtotal'], 0, ',', '.') . "</li>";
                                                }
                                                ?>
                                            </ul>
                                        </td>
                                        <td class="text-end fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                    </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center text-danger py-4 fw-bold'>Tidak ada data transaksi yang dibatalkan pada periode ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>