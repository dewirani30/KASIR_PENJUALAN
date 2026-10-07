<?php
session_start();
include "../../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

// AMBIL DATA PRODUK BERDASARKAN ID
if (!isset($_GET['id'])) {
    header("location: index.php");
    exit();
}

$produk_id = $_GET['id'];
$query_data = mysqli_query($koneksi, "SELECT * FROM produk WHERE produk_id='$produk_id'");
$data = mysqli_fetch_assoc($query_data);

if (!$data) {
    header("location: index.php");
    exit();
}

// PROSES UPDATE DATA
if (isset($_POST['update'])) {
    $produk_id   = $_POST['produk_id'];
    $nama_produk = mysqli_real_escape_string($koneksi, $_POST['nama_produk']);
    $harga       = $_POST['harga'];
    $stok        = $_POST['stok'];

    $query = "UPDATE produk SET nama_produk='$nama_produk', harga='$harga', stok='$stok' WHERE produk_id='$produk_id'";
    if (mysqli_query($koneksi, $query)) {
        header("location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk - Toko Elektronik</title>
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .btn-cyan { background-color: #5bc0de; color: white; border: none; }
        .btn-cyan:hover { background-color: #31b0d5; color: white; }
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
                        <a class="nav-link" href="../transaksi/index.php">Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../laporan/index.php">Laporan</a>
                    </li>
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

    <!-- FORM EDIT -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">Edit Data Produk</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="edit.php?id=<?= $produk_id; ?>" method="POST">
                            <input type="hidden" name="produk_id" value="<?= $data['produk_id']; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Produk</label>
                                <input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($data['nama_produk']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Harga</label>
                                <input type="number" name="harga" class="form-control" value="<?= $data['harga']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Stok</label>
                                <input type="number" name="stok" class="form-control" value="<?= $data['stok']; ?>" required>
                            </div>
                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                <button type="submit" name="update" class="btn btn-cyan px-4">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>