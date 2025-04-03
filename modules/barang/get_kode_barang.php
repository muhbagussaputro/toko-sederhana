<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include koneksi database
require_once __DIR__ . '/../../db.php';

// Generate kode barang baru
$newCode = generateKodeBarang();

// Return kode barang baru sebagai plain text
header('Content-Type: text/plain');
echo $newCode; 