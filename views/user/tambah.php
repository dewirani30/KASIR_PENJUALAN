<?php
session_start();
include "../../config/koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

if (isset($_POST['simpan'])) {
    $nama_user = mysqli_real_escape_string($koneksi, $_POST['nama_user']);
    $username  = mysqli_real_escape_string($koneksi, $_POST['username']);
    // Mengubah password menjadi MD5 sebelum disimpan
    $password  = md5($_POST['password']);
    $role      = mysqli_real_escape_string($koneksi, $_POST['role']);

    $query = "INSERT INTO user (nama_user, username, password, role) VALUES ('$nama_user', '$username', '$password', '$role')";
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
    <title>Tambah User - Toko Elektronik</title>
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
                    <li class="nav-item"><a class="nav-link" href="../pelanggan/index.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Data User</a></li>
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
                        <h5 class="fw-bold mb-0 text-dark">Tambah Data User</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="tambah.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" name="nama_user" class="form-control" placeholder="Masukkan nama lengkap" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="Admin">Admin</option>
                                    <option value="Kasir">Kasir</option>
                                </select>
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
</body>
</html>