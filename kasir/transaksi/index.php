<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include "../../config/koneksi.php";

// 1. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: ../../login.php");
    exit();
}

// 2. Cek apakah role user adalah kasir atau admin
if ($_SESSION['role'] !== 'kasir' && $_SESSION['role'] !== 'admin') {
    header("location: ../../login.php");
    exit();
}

function parseFloat($val) {
    return floatval(str_replace(',', '.', $val));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal_penjualan  = date('Y-m-d H:i:s'); 
    $pelanggan_input    = trim($_POST['pelanggan_input'] ?? '');
    $user_id            = (int)$_SESSION['user_id'];
    $bayar              = parseFloat($_POST['bayar'] ?? 0);
    $metode_pembayaran  = trim($_POST['metode_pembayaran'] ?? 'Tunai');

    // Parsing ID Pelanggan
    $pelanggan_id = NULL;
    if (!empty($pelanggan_input)) {
        $exp = explode('-', $pelanggan_input);
        $pelanggan_id = (int)trim($exp[0]);
        if ($pelanggan_id <= 0) {
            $pelanggan_id = NULL;
        }
    }

    $produk_ids = $_POST['produk_id'] ?? [];
    $jumlahs    = $_POST['jumlah_produk'] ?? [];

    // Hitung ulang total harga & diskon di SISI SERVER
    $subtotal_server = 0;
    $items_to_save = [];

    foreach ($produk_ids as $index => $p_id) {
        $p_id = (int)$p_id;
        $qty  = (int)($jumlahs[$index] ?? 0);

        if ($p_id > 0 && $qty > 0) {
            $stmt_prod = mysqli_prepare($koneksi, "SELECT harga, stok FROM produk WHERE produk_id = ?");
            mysqli_stmt_bind_param($stmt_prod, "i", $p_id);
            mysqli_stmt_execute($stmt_prod);
            $res_prod = mysqli_stmt_get_result($stmt_prod);
            
            if ($row_prod = mysqli_fetch_assoc($res_prod)) {
                if ($qty > $row_prod['stok']) {
                    echo "<script>alert('Transaksi gagal! Stok produk tidak mencukupi.'); window.location='index.php';</script>";
                    exit();
                }

                $harga_satuan = (float)$row_prod['harga'];
                $subtotal_item = $harga_satuan * $qty;
                $subtotal_server += $subtotal_item;

                $items_to_save[] = [
                    'produk_id' => $p_id,
                    'qty' => $qty,
                    'subtotal' => $subtotal_item
                ];
            }
            mysqli_stmt_close($stmt_prod);
        }
    }

    // Hitung diskon (10% untuk member)
    $diskon_server = ($pelanggan_id !== NULL) ? ($subtotal_server * 0.10) : 0;
    $total_harga_server = $subtotal_server - $diskon_server;

    // Validasi Pembayaran
    if ($total_harga_server > 0 && $bayar >= $total_harga_server && !empty($items_to_save)) {
        
        mysqli_begin_transaction($koneksi);

        try {
            if ($pelanggan_id !== NULL) {
                $stmt_penjualan = mysqli_prepare($koneksi, "INSERT INTO penjualan (tanggal_penjualan, total_harga, pelanggan_id, user_id, metode_pembayaran) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_penjualan, "sdiis", $tanggal_penjualan, $total_harga_server, $pelanggan_id, $user_id, $metode_pembayaran);
            } else {
                $stmt_penjualan = mysqli_prepare($koneksi, "INSERT INTO penjualan (tanggal_penjualan, total_harga, user_id, metode_pembayaran) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt_penjualan, "sdis", $tanggal_penjualan, $total_harga_server, $user_id, $metode_pembayaran);
            }

            mysqli_stmt_execute($stmt_penjualan);
            $penjualan_id = mysqli_insert_id($koneksi);
            mysqli_stmt_close($stmt_penjualan);

            // Simpan detail penjualan & update stok
            $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO detail_penjualan (penjualan_id, produk_id, jumlah_produk, subtotal) VALUES (?, ?, ?, ?)");
            $stmt_stok   = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE produk_id = ?");

            foreach ($items_to_save as $item) {
                mysqli_stmt_bind_param($stmt_detail, "iiid", $penjualan_id, $item['produk_id'], $item['qty'], $item['subtotal']);
                mysqli_stmt_execute($stmt_detail);

                mysqli_stmt_bind_param($stmt_stok, "ii", $item['qty'], $item['produk_id']);
                mysqli_stmt_execute($stmt_stok);
            }

            mysqli_stmt_close($stmt_detail);
            mysqli_stmt_close($stmt_stok);

            mysqli_commit($koneksi);

            header("location: index.php?cetak_id=" . $penjualan_id . "&bayar=" . $bayar . "&diskon=" . $diskon_server);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            echo "<script>alert('Gagal memproses transaksi!'); window.location='index.php';</script>";
            exit();
        }

    } else {
        echo "<script>alert('Transaksi gagal! Pastikan total belanja lebih dari 0 dan pembayaran mencukupi.'); window.location='index.php';</script>";
        exit();
    }
}

$pelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE status_member='Member' ORDER BY nama_pelanggan ASC");
$produk    = mysqli_query($koneksi, "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir - Toko Elektronik</title>
    <link rel="stylesheet" href="../../asset/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .struk-body { font-family: 'Courier New', Courier, monospace; color: #000; background-color: #f5f5f5; }
        .struk-container {
            width: 380px; 
            margin: 30px auto;
            padding: 15px;
            border: 1px dashed #ccc;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashed-line { border-top: 1px dashed #000; margin: 10px 0; }

        @media print { 
            body { background-color: #fff !important; }
            .no-print { display: none !important; } 
            .struk-container { 
                border: none; 
                padding: 0; 
                box-shadow: none;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="<?= isset($_GET['cetak_id']) ? 'struk-body' : ''; ?>">

<?php if (isset($_GET['cetak_id'])) { 
   
    $id     = (int)$_GET['cetak_id'];
    $bayar  = (float)$_GET['bayar'];
    $diskon = (float)$_GET['diskon'];

    $stmt_nota = mysqli_prepare($koneksi, "SELECT penjualan.*, pelanggan.nama_pelanggan, user.nama_user AS nama_kasir 
                                           FROM penjualan 
                                           LEFT JOIN pelanggan ON penjualan.pelanggan_id = pelanggan.pelanggan_id 
                                           LEFT JOIN user ON penjualan.user_id = user.user_id 
                                           WHERE penjualan.penjualan_id = ?");
    mysqli_stmt_bind_param($stmt_nota, "i", $id);
    mysqli_stmt_execute($stmt_nota);
    $query = mysqli_stmt_get_result($stmt_nota);
    $p     = mysqli_fetch_array($query);

    $nama_kasir       = !empty($p['nama_kasir']) ? $p['nama_kasir'] : ($_SESSION['nama_user'] ?? 'Kasir');
    $subtotal_belanja = $p['total_harga'] + $diskon;
    $kembalian        = $bayar - $p['total_harga'];
?>

    <div class="struk-container">
        <div class="text-center">
            <h4 class="fw-bold mb-1">TOKO ELEKTRONIK</h4>
            <p class="mb-0" style="font-size: 11px;">Jl. Raya Utama No. 123, Semarang</p>
            <p class="mb-0" style="font-size: 11px;">Telp: (024) 7654321 / 08123456789</p>
        </div>

        <div class="dashed-line"></div>

        <table width="100%" style="font-size: 12px;">
            <tr>
                <td width="35%">No. Nota</td>
                <td>: NOTA-<?= sprintf("%04d", $p['penjualan_id']); ?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: <?= date('d/m/Y', strtotime($p['tanggal_penjualan'])); ?></td>
            </tr>
            <tr>
                <td>Waktu</td>
                <td>: <?= date('H:i:s', strtotime($p['tanggal_penjualan'])); ?> WIB</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>: <b><?= htmlspecialchars($nama_kasir); ?></b></td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td>: <?= !empty($p['nama_pelanggan']) ? htmlspecialchars($p['nama_pelanggan']) : 'Pelanggan Umum'; ?></td>
            </tr>
            <tr>
                <td>Metode Bayar</td>
                <td>: <b><?= htmlspecialchars($p['metode_pembayaran'] ?? 'Tunai'); ?></b></td>
            </tr>
        </table>

        <div class="dashed-line"></div>

        <table width="100%" style="font-size: 12px;">
            <?php
                $stmt_dtl = mysqli_prepare($koneksi, "SELECT detail_penjualan.*, produk.nama_produk, produk.harga 
                                                      FROM detail_penjualan 
                                                      LEFT JOIN produk ON detail_penjualan.produk_id = produk.produk_id 
                                                      WHERE penjualan_id = ?");
                mysqli_stmt_bind_param($stmt_dtl, "i", $id);
                mysqli_stmt_execute($stmt_dtl);
                $detail = mysqli_stmt_get_result($stmt_dtl);

                while ($d = mysqli_fetch_array($detail)) {
                    $harga_satuan = ($d['jumlah_produk'] > 0) ? ($d['subtotal'] / $d['jumlah_produk']) : $d['harga'];
            ?>
                <tr>
                    <td colspan="3"><b><?= htmlspecialchars($d['nama_produk']); ?></b></td>
                </tr>
                <tr>
                    <td width="40%"><?= $d['jumlah_produk']; ?> x @<?= number_format($harga_satuan, 0, ',', '.'); ?></td>
                    <td width="10%"></td>
                    <td align="right">Rp <?= number_format($d['subtotal'], 0, ',', '.'); ?></td>
                </tr>
            <?php } ?>
        </table>

        <div class="dashed-line"></div>

        <table width="100%" style="font-size: 12px;">
            <tr>
                <td>Subtotal</td>
                <td align="right">Rp <?= number_format($subtotal_belanja, 0, ',', '.'); ?></td>
            </tr>
            <?php if ($diskon > 0) { ?>
            <tr>
                <td>Diskon Member</td>
                <td align="right" class="text-danger">- Rp <?= number_format($diskon, 0, ',', '.'); ?></td>
            </tr>
            <?php } ?>
            <tr class="fw-bold" style="font-size: 13px;">
                <td>TOTAL AKHIR</td>
                <td align="right">Rp <?= number_format($p['total_harga'], 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Uang Bayar</td>
                <td align="right">Rp <?= number_format($bayar, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Kembali</td>
                <td align="right">Rp <?= number_format($kembalian, 0, ',', '.'); ?></td>
            </tr>
        </table>

        <div class="dashed-line"></div>

        <div class="text-center" style="font-size: 11px;">
            <p class="mb-1">*** TERIMA KASIH ***</p>
            <p class="mb-0">Barang yang sudah dibeli</p>
            <p class="mb-0">tidak dapat ditukar / dikembalikan</p>
        </div>

        <br>
        <div class="text-center no-print d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary w-100 fw-bold">
                Cetak Struk
            </button>
            <a href="index.php" class="btn btn-secondary w-100 fw-bold">
                Kembali
            </a>
        </div>
    </div>

<?php } else { ?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold me-4" href="../../index.php">Toko Elektronik</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="../pelanggan/index.php">Data Pelanggan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="index.php">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="../riwayat.php">Riwayat</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white fw-semibold">Halo, <?= htmlspecialchars($_SESSION['nama_user'] ?? 'User'); ?></span>
                    <a href="../../logout.php" class="btn btn-danger btn-sm px-3" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">

        <form method="POST" action="index.php" id="formKasir" autocomplete="off">
            <input type="hidden" name="simpan_transaksi" value="1">

            <div class="row g-4">
                
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-0 mb-4">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="fw-bold mb-0">Form Transaksi Kasir</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Pilih Pelanggan (Member)</label>
                                    <input type="text" 
                                           id="pelanggan_input"
                                           name="pelanggan_input" 
                                           list="daftar_pelanggan" 
                                           class="form-control" 
                                           placeholder="-- Ketik Nama / No. HP (Kosongkan jika Non-Member) --" 
                                           onchange="hitungTotal()">

                                    <datalist id="daftar_pelanggan">
                                        <?php while ($p = mysqli_fetch_array($pelanggan)) { ?>
                                            <option value="<?= $p['pelanggan_id']." - ".$p['nama_pelanggan']." (Telp: ".$p['nomor_telepon'].")"; ?>"></option>
                                        <?php } ?>
                                    </datalist>
                                </div>
                            </div>

                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold m-0">Pilih Produk:</h6>
                                <div style="width: 280px;">
                                    <input type="text" id="cariProduk" class="form-control form-control-sm" placeholder="🔍 Cari nama produk..." onkeyup="filterProduk()">
                                </div>
                            </div>

                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-bordered table-hover align-middle mb-0" id="tabelProduk">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th style="width: 22%;">Harga Satuan</th>
                                            <th style="width: 15%;">Stok</th>
                                            <th style="width: 20%;">Jumlah (Qty)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($produk) > 0) {
                                            while ($pr = mysqli_fetch_array($produk)) {
                                        ?>
                                                <tr class="baris-produk">
                                                    <td class="nama-produk"><b><?= htmlspecialchars($pr['nama_produk']); ?></b></td>
                                                    <td>Rp <?= number_format($pr['harga'], 0, ',', '.'); ?></td>
                                                    <td><span class="badge bg-success"><?= $pr['stok']; ?> unit</span></td>
                                                    <td>
                                                        <input type="hidden" name="produk_id[]" value="<?= $pr['produk_id']; ?>">
                                                        <input type="hidden" class="harga-item" name="harga[]" value="<?= $pr['harga']; ?>">
                                                        <input type="number" 
                                                               name="jumlah_produk[]" 
                                                               class="form-control form-control-sm qty-item" 
                                                               min="0" 
                                                               max="<?= $pr['stok']; ?>" 
                                                               value="0" 
                                                               oninput="hitungTotal()">
                                                    </td>
                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center text-danger py-3'>Stok produk sedang kosong!</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-0 sticky-top" style="top: 20px;">
                        <div class="card-header bg-primary text-white py-3">
                            <h5 class="fw-bold mb-0">Pembayaran</h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span class="fw-semibold" id="subtotal_text">Rp 0</span>
                                <input type="hidden" id="subtotal_val" name="subtotal" value="0">
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Diskon (10%)</span>
                                <span class="fw-semibold text-danger" id="diskon_text">- Rp 0</span>
                                <input type="hidden" id="diskon_val" name="diskon" value="0">
                            </div>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold fs-6">Total Akhir</span>
                                <span class="fw-bold fs-4 text-primary" id="total_akhir_text">Rp 0</span>
                                <input type="hidden" id="total_akhir_val" name="total_harga" value="0">
                            </div>

                            <!-- Pilihan Metode Pembayaran -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Metode Pembayaran</label>
                                <select name="metode_pembayaran" id="metode_pembayaran" class="form-select form-select-lg" onchange="ubahMetodeBayar()">
                                    <option value="Tunai">Tunai (Cash)</option>
                                    <option value="Debit">Kartu Debit</option>
                                    <option value="Transfer">Transfer Bank</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Bayar (Rp)</label>
                                <input type="number" id="bayar_val" name="bayar" class="form-control form-control-lg" placeholder="0" oninput="hitungKembalian()" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Kembalian</label>
                                <input type="text" id="kembalian_text" class="form-control form-control-lg fw-bold text-success bg-light" value="Rp 0" readonly>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="simpan_transaksi" class="btn btn-success btn-lg fw-bold">
                                    PROSES TRANSAKSI
                                </button>
                                <button type="button" class="btn btn-outline-danger fw-bold" onclick="batalkanTransaksi()">
                                    BATALKAN TRANSAKSI
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <script src="../../asset/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            let historyTraversal = event.persisted || 
                (typeof window.performance != "undefined" && 
                 window.performance.navigation.type === 2);
            
            if (historyTraversal) {
                window.location.reload();
            } else {
                resetFormKasir();
            }
        });

        function batalkanTransaksi() {
            if (confirm("Apakah Anda yakin ingin membatalkan transaksi ini? Semua input akan dikosongkan.")) {
                resetFormKasir();
            }
        }

        function resetFormKasir() {
            let form = document.getElementById('formKasir');
            if (form) {
                form.reset();
            }
            document.getElementById('pelanggan_input').value = "";
            document.getElementById('cariProduk').value = "";
            document.getElementById('metode_pembayaran').value = "Tunai";
            ubahMetodeBayar();
            filterProduk();
            hitungTotal();
        }

        function filterProduk() {
            let input = document.getElementById('cariProduk').value.toLowerCase();
            let rows = document.querySelectorAll('.baris-produk');

            rows.forEach(function(row) {
                let nama = row.querySelector('.nama-produk').innerText.toLowerCase();
                if (nama.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function hitungTotal() {
            let hargaItems = document.querySelectorAll('.harga-item');
            let qtyItems   = document.querySelectorAll('.qty-item');
            let subtotal   = 0;

            for (let i = 0; i < qtyItems.length; i++) {
                let harga = parseFloat(hargaItems[i].value) || 0;
                let qty   = parseFloat(qtyItems[i].value) || 0;
                subtotal += (harga * qty);
            }

            let pelangganInput = document.getElementById('pelanggan_input').value;
            let diskon = 0;

            if (pelangganInput.trim() !== "") {
                diskon = subtotal * 0.10; 
            }

            let totalAkhir = subtotal - diskon;

            document.getElementById('subtotal_val').value = subtotal;
            document.getElementById('subtotal_text').innerText = "Rp " + subtotal.toLocaleString('id-ID');

            document.getElementById('diskon_val').value = diskon;
            document.getElementById('diskon_text').innerText = "- Rp " + diskon.toLocaleString('id-ID');

            document.getElementById('total_akhir_val').value = totalAkhir;
            document.getElementById('total_akhir_text').innerText = "Rp " + totalAkhir.toLocaleString('id-ID');

            ubahMetodeBayar();
        }

        function ubahMetodeBayar() {
            let metode = document.getElementById('metode_pembayaran').value;
            let totalAkhir = parseFloat(document.getElementById('total_akhir_val').value) || 0;
            let bayarInput = document.getElementById('bayar_val');

            if (metode === 'Debit' || metode === 'Transfer') {
                bayarInput.value = totalAkhir;
                bayarInput.readOnly = true;
            } else {
                bayarInput.readOnly = false;
            }
            hitungKembalian();
        }

        function hitungKembalian() {
            let totalAkhir = parseFloat(document.getElementById('total_akhir_val').value) || 0;
            let bayar      = parseFloat(document.getElementById('bayar_val').value) || 0;
            let kembalian  = bayar - totalAkhir;

            let kembalianText = document.getElementById('kembalian_text');

            if (bayar === 0 || isNaN(bayar)) {
                kembalianText.value = "Rp 0";
                kembalianText.style.color = "black";
            } else if (kembalian < 0) {
                kembalianText.value = "Uang Kurang!";
                kembalianText.style.color = "red";
            } else {
                kembalianText.value = "Rp " + kembalian.toLocaleString('id-ID');
                kembalianText.style.color = "green";
            }
        }
    </script>
<?php } ?>

</body>
</html>