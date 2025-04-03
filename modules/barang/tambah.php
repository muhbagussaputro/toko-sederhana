<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Inisialisasi variabel
$nama = $stok = $harga = "";
$error = $success = "";

// Generate kode barang otomatis
$kode = generateKodeBarang();

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
        // Generate kode baru jika error
        $kode = generateKodeBarang();
    } elseif (!is_numeric($stok) || $stok < 0) {
        $error = "Stok harus berupa angka positif!";
        // Generate kode baru jika error
        $kode = generateKodeBarang();
    } elseif (!is_numeric($harga) || $harga <= 0) {
        $error = "Harga harus berupa angka positif!";
        // Generate kode baru jika error
        $kode = generateKodeBarang();
    } else {
        try {
            // Cek apakah kode sudah ada
            $check_query = "SELECT COUNT(*) as count FROM barang WHERE kode = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $kode);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $count = mysqli_fetch_assoc($check_result)['count'];
            
            if ($count > 0) {
                // Jika kode sudah ada, generate kode baru
                $kode = generateKodeBarang();
                // Coba lagi dengan kode baru
                $check_query = "SELECT COUNT(*) as count FROM barang WHERE kode = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "s", $kode);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                $count = mysqli_fetch_assoc($check_result)['count'];
                
                if ($count > 0) {
                    $error = "Sistem tidak dapat menghasilkan kode barang yang unik. Silakan coba lagi nanti.";
                    // Generate kode baru lagi untuk form berikutnya
                    $kode = generateKodeBarang();
                } else {
                    // Lanjut dengan kode baru
                    goto insert_data;
                }
            } else {
                // Kode belum ada, lanjut proses
                insert_data:
                // Insert data barang baru
                $insert_query = "INSERT INTO barang (kode, nama, stok, harga) VALUES (?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssid", $kode, $nama, $stok, $harga);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    // Log aktivitas
                    $user_id = $_SESSION['user_id'];
                    $activity = "Menambahkan barang baru: " . htmlspecialchars($kode) . " - " . htmlspecialchars($nama);
                    logActivity($user_id, $activity);
                    
                    $success = "Barang berhasil ditambahkan!";
                    // Reset form
                    $nama = $stok = $harga = "";
                    // Generate kode baru untuk entry berikutnya
                    $kode = generateKodeBarang();
                } else {
                    $error = "Gagal menyimpan data barang: " . mysqli_error($conn);
                    // Generate kode baru jika error
                    $kode = generateKodeBarang();
                }
            }
        } catch (Exception $e) {
            error_log("Error tambah barang: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
            // Generate kode baru jika error
            $kode = generateKodeBarang();
        }
    }
}

// Include header
$title = "Tambah Barang";
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800 mb-3 md:mb-0">Tambah Barang Baru</h1>
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
        
        <form action="tambah.php" method="post" class="max-w-3xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-4">
                    <label for="kode" class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-barcode mr-1"></i> Kode Barang<span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="kode" name="kode" value="<?php echo $kode; ?>" readonly 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary bg-gray-100">
                    <p class="text-gray-500 text-xs mt-1">Kode dibuat otomatis oleh sistem</p>
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
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="0">
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
                               class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                               placeholder="0">
                    </div>
                    <p class="text-gray-500 text-xs mt-1">Harga dalam Rupiah (tanpa titik atau koma)</p>
                </div>
            </div>
            
            <div class="mt-8 mb-4 flex flex-col md:flex-row space-y-3 md:space-y-0 md:space-x-3">
                <button type="submit" class="bg-primary hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i> Simpan Barang
                </button>
                <button type="reset" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center">
                    <i class="fas fa-undo mr-2"></i> Reset Form
                </button>
                <a href="list.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-md text-sm transition duration-300 flex items-center justify-center md:ml-auto">
                    <i class="fas fa-times mr-2"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg shadow-lg flex flex-col items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div>
        <p>Menyimpan data...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Auto focus ke nama barang saat halaman dimuat
    document.getElementById('nama').focus();
    
    // Format otomatis input harga
    const hargaInput = document.getElementById('harga');
    hargaInput.addEventListener('input', function() {
        if (this.value < 0) this.value = 0;
    });
    
    // Validasi form sebelum submit
    form.addEventListener('submit', function(event) {
        const nama = document.getElementById('nama').value.trim();
        const stok = document.getElementById('stok').value.trim();
        const harga = document.getElementById('harga').value.trim();
        
        let isValid = true;
        let errorMessage = '';
        
        if (!nama) {
            errorMessage = 'Nama barang tidak boleh kosong!';
            isValid = false;
        } else if (!stok) {
            errorMessage = 'Stok tidak boleh kosong!';
            isValid = false;
        } else if (parseInt(stok) < 0) {
            errorMessage = 'Stok tidak boleh negatif!';
            isValid = false;
        } else if (!harga) {
            errorMessage = 'Harga tidak boleh kosong!';
            isValid = false;
        } else if (parseInt(harga) <= 0) {
            errorMessage = 'Harga harus lebih dari 0!';
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
            // Tampilkan pesan error
            alert(errorMessage);
            // Meminta kode barang baru dengan AJAX jika perlu
            refreshKodeBarang();
            return false;
        }
        
        // Tampilkan loading overlay saat form valid dan disubmit
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
    });
    
    // Jika ada pesan error dari server, buat fungsi untuk refresh kode
    const errorMessage = document.querySelector('.bg-red-100');
    if (errorMessage) {
        // Tambahkan link untuk refresh kode
        const refreshLink = document.createElement('a');
        refreshLink.href = '#';
        refreshLink.className = 'ml-3 text-blue-600 hover:underline';
        refreshLink.textContent = 'Refresh kode barang';
        refreshLink.addEventListener('click', function(e) {
            e.preventDefault();
            refreshKodeBarang();
        });
        errorMessage.appendChild(refreshLink);
    }
    
    // Fungsi untuk refresh kode barang dengan AJAX
    function refreshKodeBarang() {
        // Buat request AJAX untuk meminta kode barang baru
        fetch('get_kode_barang.php')
            .then(response => response.text())
            .then(kode => {
                document.getElementById('kode').value = kode;
            })
            .catch(error => {
                console.error('Error refreshing kode barang:', error);
            });
    }
});
</script>

<?php
// Include footer
include __DIR__ . '/../../footer.php';
?> 