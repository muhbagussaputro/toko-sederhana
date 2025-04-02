<?php
// Redirect jika sudah login
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Cek apakah database sudah dibuat
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_toko';

// Coba koneksi ke MySQL tanpa database dulu
$conn_check = mysqli_connect($host, $username, $password);

// Cek koneksi ke MySQL
if (!$conn_check) {
    die("Koneksi MySQL gagal: " . mysqli_connect_error());
}

// Cek apakah database sudah ada
$db_exists = mysqli_select_db($conn_check, $database);

if (!$db_exists) {
    // Database belum ada, redirect ke setup.php
    header("Location: setup.php");
    exit();
}

mysqli_close($conn_check);

// Koneksi ke database yang sudah ada
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$error = "";

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasi
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        // Cek username
        $query = "SELECT id, username, password, nama, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Login berhasil
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                
                // Jika pilih "Ingat Saya", atur cookie selama 30 hari
                if ($remember) {
                    $token = bin2hex(random_bytes(16)); // Buat token acak
                    setcookie('remember_token', $token, time() + (86400 * 30), "/"); // Cookie berlaku 30 hari
                    
                    // Simpan token di database (buat tabel baru atau tambahkan kolom di users)
                    $token_query = "UPDATE users SET remember_token = ? WHERE id = ?";
                    $token_stmt = mysqli_prepare($conn, $token_query);
                    mysqli_stmt_bind_param($token_stmt, "si", $token, $user['id']);
                    mysqli_stmt_execute($token_stmt);
                }
                
                // Log aktivitas login
                $activity = "Login ke sistem";
                $user_id = $user['id'];
                $timestamp = date('Y-m-d H:i:s');
                
                $log_query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp) 
                              VALUES (?, ?, ?)";
                
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, "iss", $user_id, $activity, $timestamp);
                mysqli_stmt_execute($log_stmt);
                
                // Redirect berdasarkan role
                if ($user['role'] == 'penjaga') {
                    // Simpan URL transaksi baru sebagai preferensi, tapi redirect ke beranda
                    $_SESSION['last_page'] = 'transaksi/tambah.php';
                    header("Location: index.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Password salah";
            }
        } else {
            $error = "Username tidak ditemukan";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Manajemen Toko</title>
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
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-primary py-4">
            <h2 class="text-center text-white text-2xl font-bold">Toko Sederhana</h2>
        </div>
        <div class="p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">Login Sistem</h3>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="post">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="mb-6 flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="mr-2">
                    <label for="remember" class="text-gray-700 text-sm">Ingat Saya</label>
                </div>
                
                <button type="submit" class="w-full bg-primary hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md transition duration-300">
                    Login
                </button>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600 bg-gray-50 p-3 rounded-md">
                <p class="mb-1"><span class="font-semibold">Default Admin:</span> admin / admin123</p>
                <p><span class="font-semibold">Default Penjaga:</span> penjaga / penjaga123</p>
            </div>
        </div>
    </div>
</body>
</html> 