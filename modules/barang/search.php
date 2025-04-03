<?php
session_start();
require_once __DIR__ . '/../../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Ambil query pencarian
$search = isset($_GET['q']) ? cleanInput($_GET['q']) : '';

try {
    if (!empty($search)) {
        $stmt = $pdo->prepare("SELECT id, kode, nama FROM barang WHERE kode LIKE ? OR nama LIKE ? LIMIT 10");
        $search_param = "%$search%";
        $stmt->execute([$search_param, $search_param]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $results = [];
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
} catch (PDOException $e) {
    handleError("Error pada pencarian barang: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Terjadi kesalahan sistem']);
}
?> 