<?php
session_start();

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

try {
    // Query untuk mendapatkan semua data barang
    $query = "SELECT id, kode, nama, stok, harga FROM barang ORDER BY nama ASC";
    $result = mysqli_query($conn, $query);
    
    $barang = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $barang[] = $row;
        }
    } else {
        throw new Exception("Error pada pengambilan data barang: " . mysqli_error($conn));
    }
} catch (Exception $e) {
    error_log("Error pada pengambilan data barang: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
}

// Include header
include __DIR__ . '/../../header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-3 md:mb-0">Daftar Barang</h1>
        <a href="tambah.php" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Tambah Barang Baru
        </a>
    </div>
    
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
        
    <!-- Tabel Barang dengan DataTables -->
        <div class="overflow-x-auto">
        <table id="tabelBarang" class="min-w-full bg-white stripe hover">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b">Kode</th>
                    <th class="py-2 px-4 border-b">Nama Barang</th>
                    <th class="py-2 px-4 border-b">Stok</th>
                    <th class="py-2 px-4 border-b text-right">Harga</th>
                    <th class="py-2 px-4 border-b">Aksi</th>
                    </tr>
                </thead>
            <tbody>
                <?php if (!empty($barang)): ?>
                    <?php foreach ($barang as $row): ?>
                        <tr>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['kode']); ?></td>
                            <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td class="py-2 px-4 border-b">
                                    <span class="<?php echo $row['stok'] <= 5 ? 'text-red-600 font-medium' : ($row['stok'] <= 10 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo $row['stok']; ?>
                                    </span>
                                </td>
                            <td class="py-2 px-4 border-b text-right">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                            <td class="py-2 px-4 border-b space-x-2">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="bg-warning hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <a href="hapus.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                       <i class="fas fa-trash mr-1"></i>Hapus
                                    </a>
                                </td>
                            </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>

<!-- Load DataTables CSS & JS dari CDN -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    $('#tabelBarang').DataTable({
        responsive: true,
        language: {
            search: "Pencarian:",
            lengthMenu: "Tampilkan _MENU_ data per halaman",
            zeroRecords: "Tidak ada data barang yang ditemukan",
            info: "Menampilkan halaman _PAGE_ dari _PAGES_",
            infoEmpty: "Tidak ada data barang",
            infoFiltered: "(difilter dari _MAX_ total data)",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        },
        columnDefs: [
            { orderable: false, targets: 4 } // Kolom aksi tidak bisa diurutkan
        ],
        order: [[1, 'asc']] // Urutkan berdasarkan nama barang (kolom 1)
    });
});
</script>

<style>
/* Styling untuk DataTables */
.dataTables_wrapper {
    margin-top: 1rem;
    font-size: 0.875rem;
}
.dataTables_filter input {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    margin-left: 0.5rem;
}
.dataTables_length select {
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    margin: 0 0.5rem;
}
.paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.25rem;
    border-radius: 0.375rem;
    cursor: pointer;
}
.paginate_button.current {
    background-color: #3B82F6;
    color: white;
}
.dataTables_info {
    margin-top: 1rem;
    margin-bottom: 1rem;
}
</style>

<?php include __DIR__ . '/../../footer.php'; ?> 