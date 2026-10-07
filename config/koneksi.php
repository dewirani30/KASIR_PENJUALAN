<?php
$koneksi = mysqli_connect("localhost", "root", "", "sistem_kasir");

if (mysqli_connect_errno()){
    echo "Koneksi gagal tersambung: " . mysqli_connect_error();
}
?>