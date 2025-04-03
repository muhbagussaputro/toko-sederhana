<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Inisialisasi variabel
$id = $kode = $nama = $stok = $harga = "";
$error = $success = "";

// Cek apakah ada parameter id
if (isset($_GET['id'])) {
    $id = cleanInput($_GET['id']);
    
    try {
        // Ambil data barang dari database
        $query = "SELECT id, kode, nama, stok, harga FROM barang WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Terjadi kesalahan pada database: " . mysqli_error($conn));
        }
        
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
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error edit barang: " . $e->getMessage());
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
        try {
            // Cek apakah kode sudah digunakan oleh barang lain
            $check_query = "SELECT id FROM barang WHERE kode = ? AND id != ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            
            if (!$check_stmt) {
                throw new Exception("Terjadi kesalahan pada database: " . mysqli_error($conn));
            }
            
            mysqli_stmt_bind_param($check_stmt, "si", $kode, $id);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Kode barang sudah digunakan!";
            } else {
                // Update data barang
                $update_query = "UPDATE barang SET kode = ?, nama = ?, stok = ?, harga = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if (!$update_stmt) {
                    throw new Exception("Terjadi kesalahan pada database: " . mysqli_error($conn));
                }
                
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
        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log("Error update barang: " . $e->getMessage());
        }
    }
}

// Include header
$title = "Edit Barang";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800 mb-3 md:mb-0">Edit Barang</h1>
        <a href="list.php" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
            <i class="fas fa-arrow-left mr-2"></i>Kembali
        </a>
    </div>
    
    <div class="p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($id) && empty($error)): ?>
            <form action="edit.php" method="post" class="max-w-3xl mx-auto">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="mb-4">
                        <label for="kode" class="block text-gray-700 text-sm font-medium mb-2">
                            <i class="fas fa-barcode mr-1"></i> Kode Barang<span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="kode" name="kode" value="<?php echo $kode; ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-gray-500 text-xs mt-1">Kode unik untuk barang, tidak boleh sama dengan kode barang lain</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="nama" class="block text-gray-700 text-sm font-medium mb-2">
                            <i class="fas fa-tag mr-1"></i> Nama Barang<span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nama" name="nama" value="<?php echo $nama; ?>" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="Masukkan nama barang">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="mb-4">
                        <label for="stok" class="block text-gray-700 text-sm font-medium mb-2">
                            <i class="fas fa-boxes mr-1"></i> Stok<span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="stok" name="stok" value="<?php echo $stok; ?>" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-gray-500 text-xs mt-1">Jumlah stok tersedia (minimal 0)</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="harga" class="block text-gray-700 text-sm font-medium mb-2">
                            <i class="fas fa-money-bill-wave mr-1"></i> Harga (Rp)<span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500">Rp</span>
                            </div>
                            <input type="number" id="harga" name="harga" value="<?php echo $harga; ?>" min="0" step="100" required
                                   class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <p class="text-gray-500 text-xs mt-1">Harga dalam Rupiah (tanpa titik atau koma)</p>
                    </div>
                </div>
                
                <div class="mt-8 mb-4 flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-3">
                    <button type="submit" class="bg-primary hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                    <button type="reset" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center">
                        <i class="fas fa-undo mr-2"></i> Reset Form
                    </button>
                    <a href="list.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center md:ml-auto">
                        <i class="fas fa-times mr-2"></i> Batal
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg shadow-lg flex flex-col items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div>
        <p>Menyimpan perubahan...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Auto focus ke kode barang saat halaman dimuat
    document.getElementById('kode').focus();
    
    // Format otomatis input harga
    const hargaInput = document.getElementById('harga');
    hargaInput.addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
    });
    
    // Tampilkan loading overlay saat form disubmit
    form.addEventListener('submit', function() {
        if (this.checkValidity()) {
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
        }
    });
});
</script>

<?php
// Include footer
include '../footer.php';
?> 