<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/../login.php");
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
            $stmt = $pdo->prepare("SELECT id, kode, nama, stok, harga FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch();
            
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
        } catch (PDOException $e) {
            handleError("Error pada proses tambah keranjang: " . $e->getMessage());
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
            $pdo->beginTransaction();
            
            // Hitung total transaksi
            $total = 0;
            foreach ($keranjang as $item) {
                $total += $item['subtotal'];
            }
            
            // Simpan transaksi
            $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, user_id, total) VALUES (?, ?, ?)");
            $stmt->execute([$tanggal, $_SESSION['user_id'], $total]);
            $transaksi_id = $pdo->lastInsertId();
            
            // Simpan detail transaksi dan update stok
            foreach ($keranjang as $item) {
                // Simpan detail transaksi
                $stmt = $pdo->prepare("INSERT INTO transaksi_detail (transaksi_id, barang_id, jumlah, harga, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$transaksi_id, $item['id'], $item['jumlah'], $item['harga'], $item['subtotal']]);
                
                // Update stok
                $stmt = $pdo->prepare("UPDATE barang SET stok = stok - ? WHERE id = ?");
                $stmt->execute([$item['jumlah'], $item['id']]);
            }
            
            $pdo->commit();
            
            // Log aktivitas
            logActivity($_SESSION['user_id'], "Membuat transaksi baru #$transaksi_id", 'info');
            
            // Kosongkan keranjang
            $_SESSION['keranjang'] = [];
            $keranjang = [];
            
            $success = "Transaksi berhasil disimpan!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            handleError("Error pada proses simpan transaksi: " . $e->getMessage());
            $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
        }
    }
}

// Ambil daftar barang untuk dropdown
try {
    $stmt = $pdo->query("SELECT id, kode, nama, stok, harga FROM barang WHERE stok > 0 ORDER BY nama");
    $daftar_barang = $stmt->fetchAll();
} catch (PDOException $e) {
    handleError("Error pada pengambilan daftar barang: " . $e->getMessage());
    $error = "Terjadi kesalahan saat mengambil data barang.";
    $daftar_barang = [];
}

// Include header
include __DIR__ . '/../header.php';
?>

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
    
    <!-- Form Tambah Barang -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-4">Tambah Barang</h2>
        <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="action" value="tambah_keranjang">
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="barang_id">
                    Barang
                </label>
                <select name="barang_id" id="barang_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Pilih Barang</option>
                    <?php foreach ($daftar_barang as $barang): ?>
                        <option value="<?php echo $barang['id']; ?>">
                            <?php echo htmlspecialchars($barang['kode'] . ' - ' . $barang['nama'] . ' (Stok: ' . $barang['stok'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="jumlah">
                    Jumlah
                </label>
                <input type="number" name="jumlah" id="jumlah" min="1" value="1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-primary hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Tambah ke Keranjang
                </button>
            </div>
        </form>
    </div>
    
    <!-- Keranjang -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold mb-4">Keranjang</h2>
        <?php if (empty($keranjang)): ?>
            <p class="text-gray-500">Keranjang masih kosong</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-left">Nama Barang</th>
                            <th class="px-4 py-2 text-right">Harga</th>
                            <th class="px-4 py-2 text-right">Jumlah</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                            <th class="px-4 py-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total = 0;
                        foreach ($keranjang as $index => $item): 
                            $total += $item['subtotal'];
                        ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($item['kode']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($item['nama']); ?></td>
                                <td class="px-4 py-2 text-right">Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                <td class="px-4 py-2 text-right"><?php echo number_format($item['jumlah']); ?></td>
                                <td class="px-4 py-2 text-right">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                                <td class="px-4 py-2 text-center">
                                    <form method="post" class="inline">
                                        <input type="hidden" name="action" value="hapus_keranjang">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <button type="submit" class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="font-bold">
                            <td colspan="4" class="px-4 py-2 text-right">Total:</td>
                            <td class="px-4 py-2 text-right">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 text-right">
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="simpan_transaksi">
                    <button type="submit" class="bg-success hover:bg-green-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Simpan Transaksi
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>