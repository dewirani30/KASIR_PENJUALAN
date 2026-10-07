<?php
session_start();
include "../../config/koneksi.php";

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

// Default otomatis hari ini, atau gunakan inputan jika sudah di-filter
$dari_tanggal   = $_GET['dari_tanggal'] ?? date('Y-m-d');
$sampai_tanggal = $_GET['sampai_tanggal'] ?? date('Y-m-d');

// Inisialisasi variabel total
$total_pendapatan = 0;
$total_transaksi  = 0;

// Query Laporan SEKARANG LANGSUNG DIJALANKAN (Tanpa menunggu tombol filter diklik)
$query_laporan = mysqli_query($koneksi, "
    SELECT 
        penjualan.*, 
        pelanggan.nama_pelanggan, 
        user.nama_user AS nama_kasir
    FROM penjualan
    LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id
    LEFT JOIN user ON penjualan.user_id = user.user_id
    WHERE DATE(penjualan.tanggal_penjualan) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'
    ORDER BY penjualan.tanggal_penjualan DESC
");

$query_laporan = mysqli_query($koneksi, "
    SELECT 
        penjualan.*, 
        pelanggan.nama_pelanggan, 
        user.nama_user AS nama_kasir
    FROM penjualan
    LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id
    LEFT JOIN user ON penjualan.user_id = user.user_id
    WHERE penjualan.status = 'sukses'
    AND DATE(penjualan.tanggal_penjualan) BETWEEN '$dari_tanggal' AND '$sampai_tanggal'
    ORDER BY penjualan.tanggal_penjualan DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Toko Elektronik</title>
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .card-header-blue {
            background-color: #337ab7;
            color: white;
            font-weight: bold;
        }
        .badge-periode {
            background-color: #5bc0de;
            color: white;
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 4px;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { background-color: #fff !important; }
            .card { border: none !important; box-shadow: none !important; }
            .container-fluid { padding: 0 !important; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR (Disembunyikan saat Cetak) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm no-print">
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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Laporan</a></li>
                    <li class="nav-item"><a class="nav-link" href="laporan_batal.php">Laporan Batal</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">Halo, <?= htmlspecialchars($_SESSION['nama_user'] ?? 'User'); ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">

        <!-- CARD FILTER LAPORAN -->
        <div class="card border-0 shadow-sm rounded-1 mb-4 no-print">
            <div class="card-header card-header-blue py-2 px-3">
                <i class="me-1">📊</i> Filter Laporan Penjualan
            </div>
            <div class="card-body p-3">
                <form method="GET" action="index.php" class="row align-items-center g-3">
                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold mb-0">Dari Tanggal :</label>
                        <input type="date" name="dari_tanggal" class="form-control" value="<?= $dari_tanggal; ?>" required>
                    </div>

                    <div class="col-auto d-flex align-items-center gap-2">
                        <label class="fw-bold mb-0">Sampai Tanggal :</label>
                        <input type="date" name="sampai_tanggal" class="form-control" value="<?= $sampai_tanggal; ?>" required>
                    </div>

                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary fw-bold">
                            Filter Laporan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- HASIL LAPORAN (SEKARANG LANGSUNG MUNCUL) -->
        <div class="card border-0 shadow-sm rounded-1">
            <div class="card-body p-3">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0">Laporan Penjualan Periode:</h5>
                        <span class="badge-periode fw-bold">
                            <?= date('d/m/Y', strtotime($dari_tanggal)); ?> s/d <?= date('d/m/Y', strtotime($sampai_tanggal)); ?>
                        </span>
                    </div>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-success fw-bold">
                            Cetak Laporan
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" style="font-size: 14px;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">No</th>
                                <th style="width: 110px;">No. Nota</th>
                                <th style="width: 140px;">Tanggal & Jam</th>
                                <th style="width: 150px;">Pelanggan</th>
                                <th style="width: 100px;">Kasir</th>
                                <th>Detail Produk Yang Dibeli</th>
                                <th class="text-end" style="width: 150px;">Total Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if (mysqli_num_rows($query_laporan) > 0) {
                                while ($row = mysqli_fetch_array($query_laporan)) {
                                    $penjualan_id = $row['penjualan_id'];
                                    $total_pendapatan += $row['total_harga'];
                                    $total_transaksi++;
                            ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="fw-bold">NOTA-<?= sprintf("%04d", $row['penjualan_id']); ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($row['tanggal_penjualan'])); ?></td>
                                        <td class="fst-italic text-muted">
                                            <?= !empty($row['nama_pelanggan']) ? htmlspecialchars($row['nama_pelanggan']) : 'Pelanggan Umum'; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_kasir'] ?? 'kasir'); ?></td>
                                        <td>
                                            <ul class="mb-0 ps-3">
                                                <?php
                                                $query_detail = mysqli_query($koneksi, "
                                                    SELECT detail_penjualan.*, produk.nama_produk 
                                                    FROM detail_penjualan 
                                                    LEFT JOIN produk ON detail_penjualan.produk_id = produk.produk_id 
                                                    WHERE penjualan_id='$penjualan_id'
                                                ");
                                                while ($det = mysqli_fetch_array($query_detail)) {
                                                    $harga_satuan = ($det['jumlah_produk'] > 0) ? ($det['subtotal'] / $det['jumlah_produk']) : 0;
                                                    echo "<li class='mb-1'>" . htmlspecialchars($det['nama_produk']) . " <i>(" . $det['jumlah_produk'] . " x @Rp " . number_format($harga_satuan, 0, ',', '.') . ")</i> = <b>Rp " . number_format($det['subtotal'], 0, ',', '.') . "</b></li>";
                                                }
                                                ?>
                                            </ul>
                                        </td>
                                        <td class="text-end fw-bold">Rp. <?= number_format($row['total_harga'], 0, ',', '.'); ?></td>
                                    </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center text-danger py-4 fw-bold'>Tidak ada data transaksi pada periode tanggal ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                        <?php if (mysqli_num_rows($query_laporan) > 0) { ?>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end text-uppercase">Total Transaksi:</td>
                                <td class="text-end"><?= $total_transaksi; ?> Transaksi</td>
                            </tr>
                            <tr class="fs-6">
                                <td colspan="6" class="text-end text-uppercase">Total Pendapatan:</td>
                                <td class="text-end text-success">Rp. <?= number_format($total_pendapatan, 0, ',', '.'); ?></td>
                            </tr>
                        </tfoot>
                        <?php } ?>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>