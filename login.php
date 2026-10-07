<?php
session_start();
include "config/koneksi.php";

if (isset($_POST['login'])) {
    // Mengamankan input dari SQL Injection
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    
    // Mengubah password inputan menjadi MD5
    $password = md5($_POST['password']);

    // Pengecekan username & password (MD5) di database
    $query = mysqli_query($koneksi, "SELECT * FROM user WHERE username='$username' AND password='$password'");
    $cek   = mysqli_num_rows($query);

    if ($cek > 0) {
        $data = mysqli_fetch_assoc($query);
        
        // Menyimpan data user & role ke dalam Session
        $_SESSION['user_id']   = $data['user_id'];
        $_SESSION['username']  = $data['username'];
        $_SESSION['nama_user'] = $data['nama_user'];
        $_SESSION['role']      = strtolower($data['role']); // Simpan role huruf kecil (admin/kasir)

        // Pengalihan berdasarkan role ke dalam folder views/
        if ($_SESSION['role'] === 'admin') {
            header("location: index.php"); // Sesuaikan folder admin Anda (misal views/dashboard/index.php)
            exit();
        } else if ($_SESSION['role'] === 'kasir') {
            header("location: kasir/transaksi/index.php"); // Sesuai dengan URL folder kasir Anda
            exit();
        } else {
            echo "<script>alert('Role tidak terdaftar!'); window.location='login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Username atau Password salah!'); window.location='login.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Kasir</title>
    <link href="asset/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow border-0">
                    <div class="card-body p-4">
                        <h4 class="card-title text-center fw-bold mb-4">LOGIN</h4>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" placeholder="Masukkan Username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Masuk</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>