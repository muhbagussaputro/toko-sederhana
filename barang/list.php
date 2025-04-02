<?php
// Include koneksi database
require_once '../db.php';

// Proses pencarian jika ada
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$where_clause = '';

if (!empty($search)) {
    $where_clause = "WHERE kode LIKE '%$search%' OR nama LIKE '%$search%'";
}

// Query untuk mendapatkan daftar barang
$query = "SELECT id, kode, nama, stok, harga FROM barang $where_clause ORDER BY nama ASC";
$result = mysqli_query($conn, $query);

// Include header
$title = "Daftar Barang";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Daftar Barang</h1>
        <a href="tambah.php" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Tambah Barang Baru</a>
    </div>
    
    <div class="p-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Pencarian -->
        <form action="" method="get" class="mb-6">
            <div class="flex gap-2">
                <input type="text" name="search" placeholder="Cari kode atau nama barang..." value="<?php echo $search; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                <button type="submit" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Cari</button>
                <?php if (!empty($search)): ?>
                    <a href="list.php" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Reset</a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Tabel Barang -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['kode']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['nama']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="<?php echo $row['stok'] <= 5 ? 'text-red-600 font-medium' : ($row['stok'] <= 10 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo $row['stok']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="bg-warning hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">Edit</a>
                                    <a href="hapus.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data barang</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Include footer
include '../footer.php';
?> 