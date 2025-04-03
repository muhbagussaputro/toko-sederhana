<?php
// Mulai sesi
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_id'])) {
    // Log aktivitas logout
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'db_toko';
    
    $conn = mysqli_connect($host, $username, $password, $database);
    
    if ($conn) {
        $user_id = $_SESSION['user_id'];
        $activity = "Logout dari sistem";
        $timestamp = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO log_aktivitas (user_id, aktivitas, timestamp) 
                  VALUES (?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $activity, $timestamp);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
    }
}

// Hapus semua data sesi
$_SESSION = array();

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Redirect ke halaman login
header("Location: login.php");
exit();
?> 