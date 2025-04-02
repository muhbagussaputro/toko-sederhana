<?php
// Cek apakah user sudah login
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil base URL untuk mengatasi masalah navigasi
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '';

// Deteksi level direktori dan sesuaikan base path
if (strpos($request_uri, '/barang/') !== false || 
    strpos($request_uri, '/transaksi/') !== false || 
    strpos($request_uri, '/laporan/') !== false || 
    strpos($request_uri, '/log/') !== false) {
    $base_path = '../';
} else {
    $base_path = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Toko</title>
    <!-- Tailwind CSS untuk pengembangan (ganti dengan versi production pada saat deployment) -->
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
        nav ul li a.active, nav ul li a.bg-primary {
            border-bottom: 3px solid white;
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
                <span>Selamat datang, <?php echo $_SESSION['nama']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                <a href="<?php echo $base_path; ?>logout.php" class="bg-danger hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">Logout</a>
            </div>
        </div>
    </header>
    
    <nav class="bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <ul class="flex flex-wrap">
                <?php if ($_SESSION['role'] == 'penjaga'): ?>
                    <!-- Menu prioritas untuk penjaga -->
                    <li><a href="<?php echo $base_path; ?>transaksi/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Transaksi</a></li>
                    <li><a href="<?php echo $base_path; ?>transaksi/tambah.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Transaksi Baru</a></li>
                    <li><a href="<?php echo $base_path; ?>barang/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Daftar Barang</a></li>
                    <li><a href="<?php echo $base_path; ?>index.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Dashboard</a></li>
                <?php else: ?>
                    <!-- Menu admin -->
                    <li><a href="<?php echo $base_path; ?>index.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Dashboard</a></li>
                    <li><a href="<?php echo $base_path; ?>barang/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Barang</a></li>
                    <li><a href="<?php echo $base_path; ?>transaksi/list.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Transaksi</a></li>
                    <li><a href="<?php echo $base_path; ?>laporan/laporan.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Laporan</a></li>
                    <li><a href="<?php echo $base_path; ?>log/log.php" class="block px-4 py-3 hover:bg-gray-700 transition duration-300">Log Aktivitas</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <main class="flex-grow">
        <div class="container mx-auto px-4 py-6"> 