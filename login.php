<?php
session_start();

// Pengecekan database
require_once 'db.php';

// Periksa dan buat database jika diperlukan, tetapi tanpa redirect
if (!isset($pdo) || !$pdo) {
    // Koneksi database gagal, kemungkinan database belum ada
    // Buat koneksi ke MySQL tanpa database
    try {
        $pdo_basic = new PDO("mysql:host=$host", $username, $password);
        $pdo_basic->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Cek apakah database sudah ada
        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'";
        $db_exists = $pdo_basic->query($query)->fetchColumn();
        
        if (!$db_exists) {
            // Panggil setup secara langsung, tanpa redirect
            include_once 'auto_check_db.php';
        }
    } catch (PDOException $e) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            Error koneksi database: ' . $e->getMessage() . '
        </div>';
    }
}

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

// Cek pesan timeout
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.";
}

// Cek pesan setup sukses
if (isset($_GET['setup']) && $_GET['setup'] == 'success') {
    $success = "Setup database berhasil dilakukan! Silakan login menggunakan kredensial default: <br>Admin: admin/admin123 <br>Kasir: kasir/kasir123";
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST['username'], 'string');
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validasi
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Set cookie jika remember me dicentang
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60); // 30 hari
                    
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
                    $stmt->execute([$token, date('Y-m-d H:i:s', $expires), $user['id']]);
                    
                    setcookie('remember_token', $token, $expires, '/', '', true, true);
                }
                
                // Log aktivitas login
                logActivity($user['id'], 'Login berhasil', 'info');
                
                header("Location: index.php");
                exit();
            } else {
                $error = "Username atau password salah";
                logActivity(0, "Percobaan login gagal untuk username: $username", 'warning');
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.";
        }
    }
}
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
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
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
                <p><span class="font-semibold">Default Kasir:</span> kasir / kasir123</p>
            </div>
        </div>
    </div>
</body>
</html> 