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
$tanggal = date('Y-m-d H:i:s');
$error = $success = "";
$keranjang = [];

// Cek apakah ada keranjang di sesi
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Ambil data keranjang dari sesi
$keranjang = $_SESSION['keranjang'];

// Proses penambahan barang ke keranjang
if (isset($_POST['action']) && $_POST['action'] == 'tambah_keranjang') {
    $barang_id = isset($_POST['barang_id']) ? (int)$_POST['barang_id'] : 0;
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 0;
    
    if ($barang_id <= 0 || $jumlah <= 0) {
        $error = "Barang dan jumlah harus dipilih dengan benar!";
    } else {
        try {
            // Ambil data barang
            $query = "SELECT id, kode, nama, stok, harga FROM barang WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $barang_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $barang = mysqli_fetch_assoc($result);
            
            if ($barang) {
                // Cek stok
                if ($barang['stok'] < $jumlah) {
                    $error = "Stok tidak mencukupi! Stok tersedia: {$barang['stok']}";
                } else {
                    // Cek apakah barang sudah ada di keranjang
                    $index = -1;
                    foreach ($keranjang as $key => $item) {
                        if ($item['id'] == $barang_id) {
                            $index = $key;
                            break;
                        }
                    }
                    
                    if ($index >= 0) {
                        // Update jumlah jika barang sudah ada
                        $new_jumlah = $keranjang[$index]['jumlah'] + $jumlah;
                        if ($new_jumlah > $barang['stok']) {
                            $error = "Total jumlah melebihi stok tersedia! Stok tersedia: {$barang['stok']}";
                        } else {
                            $keranjang[$index]['jumlah'] = $new_jumlah;
                            $keranjang[$index]['subtotal'] = $new_jumlah * $barang['harga'];
                            $_SESSION['keranjang'] = $keranjang;
                            $success = "Jumlah barang berhasil diupdate!";
                        }
                    } else {
                        // Tambah barang baru ke keranjang
                        $keranjang[] = [
                            'id' => $barang['id'],
                            'kode' => $barang['kode'],
                            'nama' => $barang['nama'],
                            'harga' => $barang['harga'],
                            'jumlah' => $jumlah,
                            'subtotal' => $jumlah * $barang['harga']
                        ];
                        $_SESSION['keranjang'] = $keranjang;
                        $success = "Barang berhasil ditambahkan ke keranjang!";
                    }
                }
            } else {
                $error = "Barang tidak ditemukan!";
            }
        } catch (Exception $e) {
            error_log("Error pada proses tambah keranjang: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
        }
    }
}

// Proses hapus barang dari keranjang
if (isset($_POST['action']) && $_POST['action'] == 'hapus_keranjang') {
    $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
    
    if ($index >= 0 && isset($keranjang[$index])) {
        unset($keranjang[$index]);
        $keranjang = array_values($keranjang); // Reindex array
        $_SESSION['keranjang'] = $keranjang;
        $success = "Barang berhasil dihapus dari keranjang!";
    }
}

// Proses simpan transaksi
if (isset($_POST['action']) && $_POST['action'] == 'simpan_transaksi') {
    if (empty($keranjang)) {
        $error = "Keranjang masih kosong!";
    } else {
        try {
            // Mulai transaksi
            mysqli_begin_transaction($conn);
            
            // Hitung total transaksi
            $total = 0;
            foreach ($keranjang as $item) {
                $total += $item['subtotal'];
            }
            
            // Verifikasi stok realtime sebelum melanjutkan transaksi
            $stok_valid = true;
            $stok_error = "";
            
            foreach ($keranjang as $item) {
                // Periksa stok terkini
                $check_stok_query = "SELECT stok FROM barang WHERE id = ? LIMIT 1";
                $check_stmt = mysqli_prepare($conn, $check_stok_query);
                mysqli_stmt_bind_param($check_stmt, "i", $item['id']);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_bind_result($check_stmt, $stok_terkini);
                mysqli_stmt_fetch($check_stmt);
                mysqli_stmt_close($check_stmt);
                
                // Validasi stok
                if ($stok_terkini < $item['jumlah']) {
                    $stok_valid = false;
                    $stok_error = "Stok untuk barang {$item['nama']} tidak mencukupi. Stok tersedia: {$stok_terkini}, dibutuhkan: {$item['jumlah']}";
                    break;
                }
            }
            
            // Batalkan transaksi jika stok tidak valid
            if (!$stok_valid) {
                mysqli_rollback($conn);
                $error = $stok_error;
                throw new Exception($stok_error);
            }
            
            // Simpan transaksi
            $query = "INSERT INTO transaksi (tanggal, user_id, total) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sid", $tanggal, $_SESSION['user_id'], $total);
            mysqli_stmt_execute($stmt);
            $transaksi_id = mysqli_insert_id($conn);
            
            // Simpan detail transaksi dan update stok
            foreach ($keranjang as $item) {
                // Simpan detail transaksi
                $detail_query = "INSERT INTO transaksi_detail (transaksi_id, barang_id, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)";
                $detail_stmt = mysqli_prepare($conn, $detail_query);
                mysqli_stmt_bind_param($detail_stmt, "iiidd", $transaksi_id, $item['id'], $item['jumlah'], $item['harga'], $item['subtotal']);
                mysqli_stmt_execute($detail_stmt);
                
                // Update stok
                $update_query = "UPDATE barang SET stok = stok - ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "ii", $item['jumlah'], $item['id']);
                mysqli_stmt_execute($update_stmt);
            }
            
            // Commit transaksi
            mysqli_commit($conn);
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Membuat transaksi baru #$transaksi_id", 'info');
            
            // Kosongkan keranjang
            $_SESSION['keranjang'] = [];
            $keranjang = [];
            
            $success = "Transaksi berhasil disimpan!";
            
            // Redirect ke halaman detail transaksi
            header("Location: detail.php?id=" . $transaksi_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaksi jika gagal
            mysqli_rollback($conn);
            error_log("Error pada proses simpan transaksi: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
        }
    }
}

// Ambil daftar barang untuk dropdown
try {
    $query = "SELECT id, kode, nama, stok, harga FROM barang WHERE stok > 0 ORDER BY nama";
    $result = mysqli_query($conn, $query);
    $daftar_barang = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $daftar_barang[] = $row;
        }
    } else {
        throw new Exception("Error pada pengambilan daftar barang: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    error_log("Error pada pengambilan daftar barang: " . $e->getMessage());
    $error = "Terjadi kesalahan saat mengambil data barang.";
    $daftar_barang = [];
}

// Include header
include __DIR__ . '/../../header.php';
?>

<!-- Add Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Custom CSS untuk Select2 dan Modal -->
<style>
    /* Select2 Styling */
    .select2-dropdown {
        width: auto !important;
        min-width: 450px !important;
    }
    
    .select2-container--bootstrap-5 .select2-dropdown .select2-results__options .select2-results__option {
        padding: 10px 12px;
        font-size: 14px;
    }
    
    .select2-container--bootstrap-5 .select2-dropdown {
        border-radius: 6px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    /* Compact Cart Styling */
    .compact-table th {
        padding: 6px 10px;
        font-size: 13px;
    }
    
    .compact-table td {
        padding: 6px 10px;
        font-size: 13px;
    }
    
    .compact-table .name-cell {
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 0;
    }
    
    .compact-table .code-cell {
        font-size: 11px;
        color: #666;
    }
    
    /* Modal Styling */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    
    .modal-container {
        background-color: white;
        width: 90%;
        max-width: 400px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    
    .modal-header {
        background-color: #4f46e5;
        color: white;
        padding: 15px 20px;
        font-weight: bold;
        font-size: 16px;
        display: flex;
        justify-content: space-between;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-footer {
        padding: 15px 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        border-top: 1px solid #eee;
    }
    
    .btn-confirm {
        background-color: #10b981;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        border: none;
        cursor: pointer;
    }
    
    .btn-cancel {
        background-color: #e5e7eb;
        color: #374151;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        border: none;
        cursor: pointer;
    }
    
    .btn-confirm:hover {
        background-color: #059669;
    }
    
    .btn-cancel:hover {
        background-color: #d1d5db;
    }
</style>

<div class="bg-white rounded-lg shadow-md p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Transaksi Baru</h1>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <!-- Kolom Kiri: Form Tambah Barang -->
        <div class="md:col-span-5">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold mb-4">Tambah Barang</h2>
                <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="tambah_keranjang">
            
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="barang_id">
                            Pilih Barang <span class="text-red-500">*</span>
                        </label>
                        <select name="barang_id" id="barang_id" class="select2-barang shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($daftar_barang as $barang): ?>
                                <option value="<?php echo $barang['id']; ?>" 
                                    data-stok="<?php echo $barang['stok']; ?>">
                                <?php echo htmlspecialchars($barang['kode'] . ' - ' . $barang['nama'] . ' (Stok: ' . $barang['stok'] . ') - Rp ' . number_format($barang['harga'], 0, ',', '.')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                    <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="jumlah">
                                Jumlah <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="jumlah" id="jumlah" min="1" value="1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="stok_info">
                                Stok Tersedia
                            </label>
                            <input type="text" id="stok_info" class="bg-gray-100 shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight" readonly>
                        </div>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                            <i class="fas fa-cart-plus mr-2"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </form>
                    </div>
                </div>
                
        <!-- Kolom Kanan: Keranjang Belanja (Compact) -->
        <div class="md:col-span-7">
            <div class="bg-gray-50 p-3 rounded-lg h-full">
                <h2 class="text-lg font-semibold mb-3">Keranjang Belanja</h2>
                <?php if (empty($keranjang)): ?>
                    <div class="flex flex-col items-center justify-center h-48 text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                        <p class="text-center text-sm">Keranjang masih kosong</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-h-80 overflow-y-auto mb-3">
                        <table class="min-w-full bg-white compact-table">
                            <thead class="sticky top-0 bg-white">
                                <tr>
                                    <th class="border-b">Barang</th>
                                    <th class="border-b text-right">Harga</th>
                                    <th class="border-b text-right">Jml</th>
                                    <th class="border-b text-right">Subtotal</th>
                                    <th class="border-b"></th>
                        </tr>
                    </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($keranjang as $index => $item): 
                                    $total += $item['subtotal'];
                                ?>
                                    <tr>
                                        <td class="border-b">
                                            <p class="name-cell"><?php echo htmlspecialchars($item['nama']); ?></p>
                                            <span class="code-cell"><?php echo htmlspecialchars($item['kode']); ?></span>
                                        </td>
                                        <td class="border-b text-right"><?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td class="border-b text-right"><?php echo $item['jumlah']; ?></td>
                                        <td class="border-b text-right font-medium"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                        <td class="border-b text-center">
                                            <form method="post" class="inline">
                                                <input type="hidden" name="action" value="hapus_keranjang">
                                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
                    </div>
                    
                    <div class="bg-gray-100 p-3 rounded-lg mt-3">
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-sm">Total:</span>
                            <span class="text-lg font-bold text-primary">Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
            </div>
        </div>
        
                    <div class="mt-3">
                        <button id="btnSimpanTransaksi" class="bg-success hover:bg-green-600 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline w-full flex items-center justify-center">
                            <i class="fas fa-check-circle mr-2"></i> Simpan Transaksi
                        </button>
                        <form id="formSimpanTransaksi" method="post" style="display: none;">
                <input type="hidden" name="action" value="simpan_transaksi">
            </form>
                    </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Transaksi -->
<div id="modalKonfirmasi" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <span>Konfirmasi Transaksi</span>
            <span class="close-modal" style="cursor: pointer;">&times;</span>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menyimpan transaksi ini?</p>
            <p class="text-sm text-gray-600 mt-2">Total: <span class="font-bold" id="modalTotal">Rp 0</span></p>
            </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="btnBatal">Tidak</button>
            <button class="btn-confirm" id="btnKonfirmasi">Ya, Simpan</button>
        </div>
    </div>
</div>

<!-- Add jQuery and Select2 JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2-barang').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: "Ketik untuk mencari barang...",
        allowClear: true,
        language: {
            noResults: function() {
                return "Tidak ada barang yang ditemukan";
            },
            searching: function() {
                return "Mencari...";
            }
        },
        dropdownCssClass: "select2-dropdown-large"
    });
    
    // Handle Select2 change event
    $('.select2-barang').on('change', function() {
        updateStokInfo(this);
    });
    
    // Konfirmasi Modal
    const modal = document.getElementById("modalKonfirmasi");
    const btnSimpan = document.getElementById("btnSimpanTransaksi");
    const btnBatal = document.getElementById("btnBatal");
    const btnKonfirmasi = document.getElementById("btnKonfirmasi");
    const closeModal = document.querySelector(".close-modal");
    const modalTotal = document.getElementById("modalTotal");
    
    // Update total di modal
    <?php if (!empty($keranjang)): ?>
    modalTotal.textContent = "Rp <?php echo number_format($total, 0, ',', '.'); ?>";
    <?php endif; ?>
    
    // Tampilkan modal saat tombol simpan diklik
    if (btnSimpan) {
        btnSimpan.addEventListener("click", function() {
            modal.style.display = "flex";
        });
    }
    
    // Tutup modal saat tombol batal atau tanda silang diklik
    if (btnBatal) {
        btnBatal.addEventListener("click", function() {
            modal.style.display = "none";
        });
    }
    
    if (closeModal) {
        closeModal.addEventListener("click", function() {
            modal.style.display = "none";
        });
    }
    
    // Submit form saat tombol konfirmasi diklik
    if (btnKonfirmasi) {
        btnKonfirmasi.addEventListener("click", function() {
            document.getElementById("formSimpanTransaksi").submit();
        });
    }
    
    // Tutup modal saat klik di luar modal
    window.addEventListener("click", function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });
});

function updateStokInfo(selectElement) {
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    const stokInfo = document.getElementById('stok_info');
    const jumlahInput = document.getElementById('jumlah');
    
    if (selectElement.value) {
        const stok = selectedOption.dataset.stok;
        stokInfo.value = stok;
        
        // Reset jumlah input
        jumlahInput.value = 1;
        jumlahInput.max = stok;
    } else {
        stokInfo.value = '';
    }
}

// Validasi jumlah saat input berubah
document.getElementById('jumlah').addEventListener('input', function() {
    const barangSelect = document.getElementById('barang_id');
    if (barangSelect.value) {
        const selectedOption = barangSelect.options[barangSelect.selectedIndex];
        const maxStok = parseInt(selectedOption.dataset.stok);
        
        if (parseInt(this.value) > maxStok) {
            this.value = maxStok;
        }
    }
});
</script>

<?php include __DIR__ . '/../../footer.php'; ?>