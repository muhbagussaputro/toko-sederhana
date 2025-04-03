<?php
// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo "Akses ditolak. Anda tidak memiliki hak akses.";
    exit();
}

// Inisialisasi variabel filter
$tgl_mulai = isset($_GET['tgl_mulai']) ? cleanInput($_GET['tgl_mulai']) : date('Y-m-d', strtotime('-7 days'));
$tgl_selesai = isset($_GET['tgl_selesai']) ? cleanInput($_GET['tgl_selesai']) : date('Y-m-d');
$user_filter = isset($_GET['user_id']) ? cleanInput($_GET['user_id'], 'int') : '';
$level_filter = isset($_GET['level']) ? cleanInput($_GET['level']) : '';
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';

// Set filter waktu dan kondisi lainnya
$where_conditions = ["DATE(l.timestamp) BETWEEN ? AND ?"];
$params = [$tgl_mulai, $tgl_selesai];

// Tambahkan filter user jika dipilih
if (!empty($user_filter)) {
    $where_conditions[] = "l.user_id = ?";
    $params[] = $user_filter;
}

try {
    // Periksa apakah kolom level ada
    $stmt = $pdo->prepare("SHOW COLUMNS FROM log_aktivitas LIKE 'level'");
    $stmt->execute();
    $has_level_column = $stmt->rowCount() > 0;
    
    if ($has_level_column && !empty($level_filter)) {
        $where_conditions[] = "l.level = ?";
        $params[] = $level_filter;
    }
    
    // Tambahkan filter pencarian jika ada
    if (!empty($search)) {
        $where_conditions[] = "l.aktivitas LIKE ?";
        $params[] = "%$search%";
    }
    
    // Buat where clause
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Query untuk mendapatkan daftar log aktivitas
    $query = "SELECT l.id, l.aktivitas, l.timestamp, u.nama, u.username, u.role";
    
    // Tambahkan kolom level jika ada
    if ($has_level_column) {
        $query .= ", l.level";
    }
    
    $query .= " FROM log_aktivitas l
               JOIN users u ON l.user_id = u.id
               $where_clause
               ORDER BY l.timestamp DESC
               LIMIT 500"; // Batasi jumlah data yang diambil untuk performa yang lebih baik
    
    // Eksekusi query
    $stmt = $pdo->prepare($query);
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i+1, $params[$i]);
    }
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    // Output tabel HTML
    ob_start();
?>
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
            <?php if ($has_level_column): ?>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
            <?php endif; ?>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktivitas</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <?php if (count($result) > 0): ?>
            <?php foreach ($result as $row): ?>
                <tr class="hover:bg-gray-50 <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'bg-yellow-50' : ''; ?>">
                    <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['id']; ?></td>
                    <td class="px-4 py-3 whitespace-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($row['timestamp'])); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['nama'] . ' (' . $row['username'] . ')'; ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                              <?php echo $row['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo $row['role']; ?>
                        </span>
                    </td>
                    <?php if ($has_level_column): ?>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                              <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo isset($row['level']) ? $row['level'] : 'normal'; ?>
                        </span>
                    </td>
                    <?php endif; ?>
                    <td class="px-4 py-3 <?php echo isset($row['level']) && $row['level'] == 'penting' ? 'font-semibold' : ''; ?>">
                        <?php 
                        // Cek pola transaksi dan ubah menjadi link
                        $aktivitas = $row['aktivitas'];
                        
                        // Pola 1: "Membuat transaksi baru #123"
                        if (preg_match('/Membuat transaksi baru #(\d+)/', $aktivitas, $matches)) {
                            $transaksi_id = $matches[1];
                            $aktivitas = preg_replace(
                                '/(Membuat transaksi baru #)(\d+)/', 
                                '$1<a href="../transaksi/detail.php?id=$2" class="text-blue-600 hover:text-blue-800 hover:underline">$2</a>',
                                $aktivitas
                            );
                        } 
                        // Pola 2: "transaksi #123" (tanpa kata "baru")
                        elseif (preg_match('/transaksi #(\d+)/i', $aktivitas, $matches)) {
                            $transaksi_id = $matches[1];
                            $aktivitas = preg_replace(
                                '/(transaksi #)(\d+)/i', 
                                '$1<a href="../transaksi/detail.php?id=$2" class="text-blue-600 hover:text-blue-800 hover:underline">$2</a>',
                                $aktivitas
                            );
                        }
                        
                        echo $aktivitas;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo $has_level_column ? '6' : '5'; ?>" class="px-4 py-4 text-center text-gray-500">Tidak ada data log aktivitas</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
<div class="mt-4 text-sm text-gray-600">
    <p>Menampilkan <?php echo count($result); ?> log aktivitas <?php echo $level_filter == 'penting' ? 'penting' : ($level_filter == 'normal' ? 'normal' : ''); ?> 
    <?php echo !empty($search) ? "dengan kata kunci \"" . htmlspecialchars($search) . "\"" : ""; ?></p>
</div>
<?php
    $html = ob_get_clean();
    echo $html;
    
} catch (PDOException $e) {
    error_log("Error pada log aktivitas AJAX: " . $e->getMessage());
    echo '<div class="p-4 bg-red-50 text-red-500 rounded-md">
            <p>Terjadi kesalahan saat memuat data log. Silakan coba lagi atau hubungi administrator.</p>
            <p>Error code: ' . time() . '</p>
          </div>';
}
?> 