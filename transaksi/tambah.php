<?php
// Include koneksi database
require_once '../db.php';

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
        // Ambil data barang
        $query_barang = "SELECT id, kode, nama, stok, harga FROM barang WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query_barang);
        mysqli_stmt_bind_param($stmt, "i", $barang_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $barang = mysqli_fetch_assoc($result);
            
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
                        $error = "Total jumlah melebihi stok! Stok tersedia: {$barang['stok']}";
                    } else {
                        $keranjang[$index]['jumlah'] = $new_jumlah;
                        $keranjang[$index]['subtotal'] = $new_jumlah * $barang['harga'];
                    }
                } else {
                    // Tambahkan barang baru ke keranjang
                    $item = [
                        'id' => $barang['id'],
                        'kode' => $barang['kode'],
                        'nama' => $barang['nama'],
                        'harga' => $barang['harga'],
                        'jumlah' => $jumlah,
                        'subtotal' => $jumlah * $barang['harga']
                    ];
                    
                    $keranjang[] = $item;
                }
                
                // Simpan keranjang ke sesi
                $_SESSION['keranjang'] = $keranjang;
                $success = "Barang berhasil ditambahkan ke keranjang!";
            }
        } else {
            $error = "Barang tidak ditemukan!";
        }
    }
}

// Proses hapus barang dari keranjang
if (isset($_GET['hapus_item'])) {
    $index = (int)$_GET['hapus_item'];
    
    if (isset($keranjang[$index])) {
        unset($keranjang[$index]);
        $keranjang = array_values($keranjang); // Reindex array
        $_SESSION['keranjang'] = $keranjang;
        $success = "Barang berhasil dihapus dari keranjang!";
    }
}

// Proses simpan transaksi
if (isset($_POST['action']) && $_POST['action'] == 'simpan_transaksi') {
    if (count($keranjang) > 0) {
        // Mulai transaksi database
        mysqli_begin_transaction($conn);
        
        try {
            // Insert ke tabel transaksi
            $tanggal = date('Y-m-d H:i:s');
            $total = 0;
            foreach ($keranjang as $item) {
                $total += $item['subtotal'];
            }
            
            $query = "INSERT INTO transaksi (tanggal, user_id, total) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sid", $tanggal, $_SESSION['user_id'], $total);
            mysqli_stmt_execute($stmt);
            $transaksi_id = mysqli_insert_id($conn);
            
            // Insert detail transaksi dan update stok
            foreach ($keranjang as $item) {
                // Insert detail transaksi
                $query = "INSERT INTO transaksi_detail (transaksi_id, barang_id, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                $subtotal = $item['jumlah'] * $item['harga'];
                mysqli_stmt_bind_param($stmt, "iiidi", $transaksi_id, $item['id'], $item['jumlah'], $item['harga'], $subtotal);
                mysqli_stmt_execute($stmt);
                
                // Update stok barang
                $query = "UPDATE barang SET stok = stok - ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $item['jumlah'], $item['id']);
                mysqli_stmt_execute($stmt);
            }
            
            // Commit transaksi
            mysqli_commit($conn);
            
            // Log aktivitas
            $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp) VALUES (?, 'transaksi', NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            
            // Reset keranjang
            $_SESSION['keranjang'] = array();
            
            // Redirect ke halaman detail transaksi
            header("Location: detail.php?id=" . $transaksi_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback jika terjadi error
            mysqli_rollback($conn);
            
            // Log error
            $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp) VALUES (?, 'error', NOW())";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($stmt);
            
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Ambil daftar barang untuk dropdown
$query_daftar_barang = "SELECT id, kode, nama, stok, harga FROM barang WHERE stok > 0 ORDER BY nama ASC";
$result_barang = mysqli_query($conn, $query_daftar_barang);

// Redirect langsung ke halaman transaksi baru setelah refresh
if ($_SESSION['role'] == 'penjaga' && !isset($_POST['action']) && !isset($_GET['hapus_item'])) {
    // Simpan URL halaman ini untuk diingat sebagai halaman terakhir
    $_SESSION['last_page'] = 'transaksi/tambah.php';
}

// Include header
$title = "Tambah Transaksi";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Tambah Transaksi Baru</h1>
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
        
        <!-- Form Tambah Barang ke Keranjang -->
        <form action="" method="post" class="mb-8">
            <input type="hidden" name="action" value="tambah_keranjang">
            
            <div class="bg-blue-50 p-4 rounded-lg mb-6 border border-blue-100">
                <h3 class="text-lg font-semibold text-blue-800 mb-4">Tambah Barang ke Keranjang</h3>
                
                <!-- Pencarian Cepat -->
                <div class="mb-6">
                    <label for="searchBarang" class="block text-gray-700 text-sm font-medium mb-2">Cari Barang (Kode/Nama):</label>
                    <input type="text" id="searchBarang" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                           placeholder="Ketik untuk mencari barang...">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="col-span-1 md:col-span-2">
                        <label for="barang_id" class="block text-gray-700 text-sm font-medium mb-2">Pilih Barang:</label>
                        <select id="barang_id" name="barang_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">-- Pilih Barang --</option>
                            <?php mysqli_data_seek($result_barang, 0); ?>
                            <?php while($barang = mysqli_fetch_assoc($result_barang)): ?>
                                <option value="<?php echo $barang['id']; ?>" 
                                        data-stok="<?php echo $barang['stok']; ?>" 
                                        data-harga="<?php echo $barang['harga']; ?>"
                                        data-kode="<?php echo $barang['kode']; ?>"
                                        data-nama="<?php echo $barang['nama']; ?>">
                                    <?php echo $barang['kode'] . ' - ' . $barang['nama'] . ' (Stok: ' . $barang['stok'] . ', Harga: Rp ' . number_format($barang['harga'], 0, ',', '.') . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="jumlah" class="block text-gray-700 text-sm font-medium mb-2">Jumlah:</label>
                        <input type="number" id="jumlah" name="jumlah" min="1" value="1" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md transition duration-300">
                            <i class="fas fa-cart-plus mr-2"></i> Tambah ke Keranjang
                        </button>
                    </div>
                </div>
                
                <!-- Informasi Produk Terpilih -->
                <div id="selectedProductInfo" class="mt-4 p-3 bg-white rounded border border-gray-200 hidden">
                    <h4 class="font-semibold mb-2">Detail Barang Terpilih:</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <div><span class="font-medium">Kode:</span> <span id="selectedKode"></span></div>
                        <div><span class="font-medium">Nama:</span> <span id="selectedNama"></span></div>
                        <div><span class="font-medium">Stok:</span> <span id="selectedStok"></span></div>
                        <div><span class="font-medium">Harga:</span> <span id="selectedHarga"></span></div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Tabel Keranjang -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Keranjang Belanja</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($keranjang) > 0): ?>
                            <?php $total = 0; ?>
                            <?php foreach ($keranjang as $index => $item): ?>
                                <?php $total += $item['subtotal']; ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['kode']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['nama']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['jumlah']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="?hapus_item=<?php echo $index; ?>" 
                                           class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block"
                                           onclick="return confirm('Yakin ingin menghapus barang ini dari keranjang?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Total Row -->
                            <tr class="bg-gray-100">
                                <td colspan="4" class="px-6 py-4 text-right font-bold">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-lg text-primary">
                                    Rp <?php echo number_format($total, 0, ',', '.'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Keranjang belanja masih kosong</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Form Simpan Transaksi -->
        <?php if (count($keranjang) > 0): ?>
            <form id="formTransaksi" action="" method="post">
                <input type="hidden" name="action" value="simpan_transaksi">
                
                <!-- Informasi Pelanggan dihapus -->
                <button type="button" id="btnKonfirmasiTransaksi" 
                        class="w-full bg-success hover:bg-green-600 text-white py-3 px-4 rounded-md font-medium text-lg transition duration-300">
                    Simpan Transaksi
                </button>
            </form>
        <?php else: ?>
            <button type="button" class="w-full bg-gray-400 text-white py-3 px-4 rounded-md font-medium text-lg cursor-not-allowed" disabled>
                Simpan Transaksi
            </button>
            <p class="text-center text-sm text-gray-500 mt-2">Tambahkan barang ke keranjang terlebih dahulu</p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Konfirmasi Transaksi -->
<div id="modalKonfirmasi" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full mx-4 overflow-hidden">
        <div class="bg-primary text-white px-6 py-4">
            <h3 class="text-xl font-semibold">Konfirmasi Transaksi</h3>
        </div>
        
        <div class="p-6">
            <div id="detailTransaksi" class="mb-6"></div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="btnBatalKonfirmasi" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition duration-300">
                    Batal
                </button>
                <button type="button" id="btnKonfirmasi" 
                        class="bg-success hover:bg-green-600 text-white px-4 py-2 rounded-md transition duration-300">
                    Konfirmasi & Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk mengatur maksimum input jumlah berdasarkan stok
document.getElementById('barang_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const maxStok = selectedOption.getAttribute('data-stok');
    const jumlahInput = document.getElementById('jumlah');
    
    if (maxStok) {
        jumlahInput.setAttribute('max', maxStok);
        jumlahInput.setAttribute('placeholder', 'Max: ' + maxStok);
        
        // Tampilkan informasi produk terpilih
        document.getElementById('selectedProductInfo').classList.remove('hidden');
        document.getElementById('selectedKode').textContent = selectedOption.getAttribute('data-kode');
        document.getElementById('selectedNama').textContent = selectedOption.getAttribute('data-nama');
        document.getElementById('selectedStok').textContent = maxStok;
        document.getElementById('selectedHarga').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(selectedOption.getAttribute('data-harga'));
    } else {
        document.getElementById('selectedProductInfo').classList.add('hidden');
    }
});

// Pencarian barang
document.getElementById('searchBarang').addEventListener('input', function() {
    const searchText = this.value.toLowerCase();
    const select = document.getElementById('barang_id');
    const options = select.querySelectorAll('option');
    
    let firstMatchIndex = -1;
    
    options.forEach((option, index) => {
        if (index === 0) return; // Skip label "Pilih Barang"
        
        const optionText = option.textContent.toLowerCase();
        const kode = option.getAttribute('data-kode')?.toLowerCase() || '';
        const nama = option.getAttribute('data-nama')?.toLowerCase() || '';
        
        // Cek apakah teks pencarian ada di opsi
        if (optionText.includes(searchText) || kode.includes(searchText) || nama.includes(searchText)) {
            option.style.display = '';
            if (firstMatchIndex === -1) firstMatchIndex = index;
        } else {
            option.style.display = 'none';
        }
    });
    
    // Otomatis pilih opsi pertama yang cocok
    if (firstMatchIndex > 0 && searchText.length > 1) {
        select.selectedIndex = firstMatchIndex;
        select.dispatchEvent(new Event('change'));
    }
});

// Auto focus ke pencarian saat halaman dimuat
window.addEventListener('load', function() {
    document.getElementById('searchBarang').focus();
});

// Modal Konfirmasi Transaksi
const modal = document.getElementById('modalKonfirmasi');
const btnKonfirmasiTransaksi = document.getElementById('btnKonfirmasiTransaksi');
const btnBatalKonfirmasi = document.getElementById('btnBatalKonfirmasi');
const btnKonfirmasi = document.getElementById('btnKonfirmasi');
const detailTransaksi = document.getElementById('detailTransaksi');
const formTransaksi = document.getElementById('formTransaksi');

// Format angka untuk tampilan
function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID').format(angka);
}

// Event listener untuk tombol konfirmasi transaksi
btnKonfirmasiTransaksi.addEventListener('click', function() {
    // Ambil data keranjang
    const keranjangItems = <?php echo json_encode($keranjang); ?>;
    
    if (keranjangItems.length === 0) {
        alert('Keranjang belanja masih kosong!');
        return;
    }
    
    // Hitung total
    let total = 0;
    let detailHtml = '<div class="space-y-4">';
    detailHtml += '<h4 class="font-semibold text-lg">Detail Transaksi:</h4>';
    detailHtml += '<table class="min-w-full divide-y divide-gray-200">';
    detailHtml += '<thead><tr><th class="text-left">Barang</th><th class="text-right">Jumlah</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr></thead>';
    detailHtml += '<tbody>';
    
    keranjangItems.forEach(item => {
        const subtotal = item.jumlah * item.harga;
        total += subtotal;
        
        detailHtml += `<tr>
            <td class="py-2">${item.nama}</td>
            <td class="text-right">${item.jumlah}</td>
            <td class="text-right">Rp ${formatRupiah(item.harga)}</td>
            <td class="text-right">Rp ${formatRupiah(subtotal)}</td>
        </tr>`;
    });
    
    detailHtml += '</tbody>';
    detailHtml += `<tfoot>
        <tr class="font-bold">
            <td colspan="3" class="text-right">Total:</td>
            <td class="text-right">Rp ${formatRupiah(total)}</td>
        </tr>
    </tfoot>`;
    detailHtml += '</table>';
    detailHtml += '</div>';
    
    // Tampilkan detail di modal
    detailTransaksi.innerHTML = detailHtml;
    
    // Tampilkan modal
    modal.classList.remove('hidden');
});

// Event listener untuk tombol batal
btnBatalKonfirmasi.addEventListener('click', function() {
    modal.classList.add('hidden');
});

// Event listener untuk tombol konfirmasi
btnKonfirmasi.addEventListener('click', function() {
    formTransaksi.submit();
});

// Tutup modal saat mengklik di luar modal
window.addEventListener('click', function(event) {
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
});
</script>

<?php
// Include footer
include '../footer.php';
?>