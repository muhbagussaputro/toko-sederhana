<?php
// Include koneksi database
require_once '../db.php';

// Inisialisasi variabel
$kode = $nama = $stok = $harga = "";
$error = $success = "";

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $kode = cleanInput($_POST['kode']);
    $nama = cleanInput($_POST['nama']);
    $stok = cleanInput($_POST['stok']);
    $harga = cleanInput($_POST['harga']);
    
    // Validasi input
    if (empty($kode) || empty($nama) || empty($stok) || empty($harga)) {
        $error = "Semua field harus diisi!";
    } elseif (!is_numeric($stok) || $stok < 0) {
        $error = "Stok harus berupa angka positif!";
    } elseif (!is_numeric($harga) || $harga <= 0) {
        $error = "Harga harus berupa angka positif!";
    } else {
        // Cek apakah kode sudah ada
        $check_query = "SELECT kode FROM barang WHERE kode = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $kode);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Kode barang sudah digunakan!";
        } else {
            // Insert data barang baru
            $insert_query = "INSERT INTO barang (kode, nama, stok, harga) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ssid", $kode, $nama, $stok, $harga);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                // Log aktivitas
                $user_id = $_SESSION['user_id'];
                $activity = "Menambahkan barang baru: $kode - $nama";
                logActivity($user_id, $activity);
                
                $success = "Barang berhasil ditambahkan!";
                // Reset form
                $kode = $nama = $stok = $harga = "";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Include header
$title = "Tambah Barang";
include '../header.php';
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Tambah Barang Baru</h1>
        <a href="list.php" class="btn btn-warning">Kembali</a>
    </div>
    
    <div class="card-content">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="tambah.php" method="post">
            <div class="form-group">
                <label for="kode">Kode Barang:</label>
                <input type="text" id="kode" name="kode" value="<?php echo $kode; ?>" required>
                <small>Kode unik untuk barang (mis. BRG001)</small>
            </div>
            
            <div class="form-group">
                <label for="nama">Nama Barang:</label>
                <input type="text" id="nama" name="nama" value="<?php echo $nama; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stok">Stok:</label>
                <input type="number" id="stok" name="stok" value="<?php echo $stok; ?>" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="harga">Harga (Rp):</label>
                <input type="number" id="harga" name="harga" value="<?php echo $harga; ?>" min="0" step="100" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include '../footer.php';
?> 