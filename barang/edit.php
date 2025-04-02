<?php
// Include koneksi database
require_once '../db.php';

// Inisialisasi variabel
$id = $kode = $nama = $stok = $harga = "";
$error = $success = "";

// Cek apakah ada parameter id
if (isset($_GET['id'])) {
    $id = cleanInput($_GET['id']);
    
    // Ambil data barang dari database
    $query = "SELECT id, kode, nama, stok, harga FROM barang WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 1) {
        $barang = mysqli_fetch_assoc($result);
        $kode = $barang['kode'];
        $nama = $barang['nama'];
        $stok = $barang['stok'];
        $harga = $barang['harga'];
    } else {
        $error = "Barang tidak ditemukan!";
    }
} else {
    $error = "ID barang tidak valid!";
}

// Cek jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    // Ambil data dari form
    $id = cleanInput($_POST['id']);
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
        // Cek apakah kode sudah digunakan oleh barang lain
        $check_query = "SELECT id FROM barang WHERE kode = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "si", $kode, $id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Kode barang sudah digunakan!";
        } else {
            // Update data barang
            $update_query = "UPDATE barang SET kode = ?, nama = ?, stok = ?, harga = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "ssidi", $kode, $nama, $stok, $harga, $id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Log aktivitas
                $user_id = $_SESSION['user_id'];
                $activity = "Mengedit barang: $kode - $nama";
                logActivity($user_id, $activity);
                
                $success = "Barang berhasil diperbarui!";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    }
}

// Include header
$title = "Edit Barang";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Edit Barang</h1>
        <a href="list.php" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Kembali</a>
    </div>
    
    <div class="p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($id) && empty($error)): ?>
            <form action="edit.php" method="post">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="mb-4">
                    <label for="kode" class="block text-gray-700 text-sm font-medium mb-2">Kode Barang:</label>
                    <input type="text" id="kode" name="kode" value="<?php echo $kode; ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="mb-4">
                    <label for="nama" class="block text-gray-700 text-sm font-medium mb-2">Nama Barang:</label>
                    <input type="text" id="nama" name="nama" value="<?php echo $nama; ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="mb-4">
                    <label for="stok" class="block text-gray-700 text-sm font-medium mb-2">Stok:</label>
                    <input type="number" id="stok" name="stok" value="<?php echo $stok; ?>" min="0" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="mb-6">
                    <label for="harga" class="block text-gray-700 text-sm font-medium mb-2">Harga (Rp):</label>
                    <input type="number" id="harga" name="harga" value="<?php echo $harga; ?>" min="0" step="100" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div>
                    <button type="submit" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                        Update
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include '../footer.php';
?> 