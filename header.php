<?php
// Cek apakah user sudah login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect ke login jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// Ambil data user dari session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Ambil nama user dari database jika tidak ada di session
if (!isset($_SESSION['nama'])) {
    try {
        $stmt = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $_SESSION['nama'] = $user['nama'];
    } catch (PDOException $e) {
        $_SESSION['nama'] = $username; // Fallback ke username jika query gagal
    }
}

// Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Tentukan base path berdasarkan direktori
$base_path = '/';

// Fungsi untuk mengecek apakah menu aktif
function isMenuActive($page, $dir = '') {
    global $current_page, $current_dir;
    
    if ($dir) {
        return ($current_page == $page && $current_dir == $dir) ? 'active bg-primary' : '';
    } else {
        return ($current_page == $page) ? 'active bg-primary' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Toko</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3B82F6',
                        secondary: '#1E40AF',
                        dark: '#1F2937',
                        light: '#F3F4F6',
                        danger: '#EF4444',
                        success: '#10B981',
                        warning: '#F59E0B',
                        info: '#3B82F6'
                    }
                }
            }
        }
    </script>
    <style>
        /* Penanda menu aktif */
        nav ul li a.active {
            background-color: #3B82F6;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-dark text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="text-xl font-bold">
                <a href="<?php echo $base_path; ?>index.php" class="hover:text-gray-300 transition duration-300">Toko Sederhana</a>
            </div>
            <div class="flex items-center space-x-4">
                <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                <a href="<?php echo $base_path; ?>logout.php" class="bg-danger hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Logout</a>
            </div>
        </div>
    </header>
    
    <nav class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <ul class="flex flex-wrap">
                <?php if ($_SESSION['role'] == 'kasir'): ?>
                    <!-- Menu untuk kasir -->
                    <li>
                        <a href="<?php echo $base_path; ?>modules/transaksi/tambah.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('tambah.php', 'transaksi'); ?>">
                            <i class="fas fa-cart-plus mr-2"></i>Transaksi Baru
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/transaksi/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('list.php', 'transaksi'); ?>">
                            <i class="fas fa-list mr-2"></i>Daftar Transaksi
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/barang/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('list.php', 'barang'); ?>">
                            <i class="fas fa-box mr-2"></i>Daftar Barang
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>index.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('index.php'); ?>">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                    </li>
                <?php else: ?>
                    <!-- Menu untuk admin -->
                    <li>
                        <a href="<?php echo $base_path; ?>index.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('index.php'); ?>">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/barang/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('list.php', 'barang'); ?>">
                            <i class="fas fa-box mr-2"></i>Barang
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/transaksi/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('list.php', 'transaksi'); ?>">
                            <i class="fas fa-shopping-cart mr-2"></i>Transaksi
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/laporan/laporan.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('laporan.php', 'laporan'); ?>">
                            <i class="fas fa-chart-bar mr-2"></i>Laporan
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo $base_path; ?>modules/log/log.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300 <?php echo isMenuActive('log.php', 'log'); ?>">
                            <i class="fas fa-history mr-2"></i>Log Aktivitas
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <main class="container mx-auto px-4 py-6 flex-grow"> 