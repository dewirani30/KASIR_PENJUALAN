<?php
session_start();
include "../../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

if (isset($_POST['simpan'])) {
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $nomor_telepon  = mysqli_real_escape_string($koneksi, trim($_POST['nomor_telepon']));

    // Validasi PHP (Sisi Server)
    if (!is_numeric($nomor_telepon)) {
        echo "<script>alert('Nomor telepon harus berupa angka!'); window.history.back();</script>";
        exit();
    } elseif (strlen($nomor_telepon) < 9 || strlen($nomor_telepon) > 13) {
        echo "<script>alert('Nomor telepon harus terdiri dari 9 hingga 13 digit!'); window.history.back();</script>";
        exit();
    }

    $query = "INSERT INTO pelanggan (nama_pelanggan, alamat, nomor_telepon) 
              VALUES ('$nama_pelanggan', '$alamat', '$nomor_telepon')";
              
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
    <title>Tambah Pelanggan - Toko Elektronik</title>
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .btn-cyan { background-color: #5bc0de; color: white; border: none; }
        .btn-cyan:hover { background-color: #31b0d5; color: white; }
    </style>
</head>
<body>

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
                    <li class="nav-item"><a class="nav-link active" href="index.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../user/index.php">Data User</a></li>
                    <li class="nav-item"><a class="nav-link" href="../transaksi/index.php">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="../laporan/index.php">Laporan</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">Halo, <?= htmlspecialchars($_SESSION['nama_user']); ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-0">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">Tambah Data Pelanggan</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" class="form-control" placeholder="Masukkan nama pelanggan" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3" placeholder="Masukkan alamat" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">No. Telepon</label>
                                <input type="text" 
                                       id="no_tlp"
                                       name="nomor_telepon" 
                                       class="form-control" 
                                       placeholder="Masukkan nomor telepon" 
                                       minlength="9" 
                                       maxlength="13" 
                                       autocomplete="off"
                                       required>
                            </div>
                            <div class="d-flex gap-2 justify-content-end mt-4">
                                <a href="index.php" class="btn btn-secondary px-4">Batal</a>
                                <button type="submit" name="simpan" class="btn btn-cyan px-4">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
    <script>
        const inputTlp = document.getElementById('no_tlp');

        inputTlp.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length > 13) {
                this.value = this.value.slice(0, 13);
            }
        });
    </script>
</body>
</html>