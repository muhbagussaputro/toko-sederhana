<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user adalah admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Inisialisasi variabel filter
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d', strtotime('-7 days'));
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// Set filter waktu
$where_clause = "WHERE DATE(l.timestamp) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";

// Query untuk mendapatkan daftar log aktivitas
$query = "SELECT l.id, l.aktivitas, l.timestamp, u.nama, u.username, u.role
          FROM log_aktivitas l
          JOIN users u ON l.user_id = u.id
          $where_clause
          ORDER BY l.timestamp DESC";

$result = mysqli_query($conn, $query);

// Include header
$title = "Log Aktivitas";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800">Log Aktivitas</h1>
    </div>
    
    <div class="p-4">
        <!-- Filter Form -->
        <form action="" method="get" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label for="tgl_mulai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Mulai:</label>
                    <input type="date" id="tgl_mulai" name="tgl_mulai" value="<?php echo $tgl_mulai; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="tgl_selesai" class="block text-gray-700 text-sm font-medium mb-2">Tanggal Selesai:</label>
                    <input type="date" id="tgl_selesai" name="tgl_selesai" value="<?php echo $tgl_selesai; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <button type="submit" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto">Filter</button>
                </div>
            </div>
        </form>
        
        <!-- Tabel Log Aktivitas -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['id']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($row['timestamp'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo $row['nama'] . ' (' . $row['username'] . ')'; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                          <?php echo $row['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $row['role']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo $row['aktivitas']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data log aktivitas</td>
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