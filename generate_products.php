<?php
// Set informasi koneksi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'db_toko';

// Buat koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Set execution time to unlimited (for large data)
set_time_limit(0);

echo "<h2>Menambahkan Banyak Barang ke Database</h2>";

// Kategori produk
$categories = [
    'Laptop', 'Smartphone', 'Tablet', 'Monitor', 'Printer', 'Scanner', 
    'Keyboard', 'Mouse', 'Headset', 'Speaker', 'Flashdisk', 'Harddisk',
    'SSD', 'RAM', 'Processor', 'Motherboard', 'VGA Card', 'Power Supply',
    'Casing', 'Cooling Fan', 'Webcam', 'Microphone', 'Router', 'Switch',
    'Access Point', 'Network Card', 'Kabel LAN', 'Kabel HDMI', 'Kabel VGA',
    'Converter', 'Hub USB', 'Card Reader', 'UPS', 'Stabilizer'
];

// Merek produk
$brands = [
    'Asus', 'Acer', 'Lenovo', 'HP', 'Dell', 'Samsung', 'Apple', 'Xiaomi',
    'Logitech', 'SteelSeries', 'Razer', 'Corsair', 'HyperX', 'MSI', 'Gigabyte',
    'NZXT', 'Cooler Master', 'Thermaltake', 'WD', 'Seagate', 'Kingston', 'Sandisk',
    'Transcend', 'Vgen', 'Team', 'Toshiba', 'LG', 'BenQ', 'AOC', 'ViewSonic',
    'Epson', 'Canon', 'Brother', 'TP-Link', 'D-Link', 'Cisco', 'Netgear',
    'Huawei', 'Intel', 'AMD', 'NVIDIA', 'Fantech', 'Rexus', 'Digital Alliance'
];

// Tipe/model produk
$models = [
    'Pro', 'Lite', 'Plus', 'Ultra', 'Max', 'Mini', 'Nano', 'Slim', 'Gaming',
    'Business', 'Creator', 'Student', 'Basic', 'Advanced', 'Premium', 'Value',
    'Extreme', 'Elite', 'Prime', 'Essential', 'Performance', 'Wireless', 'Bluetooth',
    'Standard', 'Compact', 'Ergonomic', 'Touch', 'RGB', 'LED', 'Classic',
    'X1', 'X2', 'X3', 'X5', 'X7', 'X9', 'A1', 'A3', 'A5', 'A7', 'V1', 'V3', 'V5',
    'T1', 'T3', 'T5', 'M1', 'M3', 'M5', 'S1', 'S3', 'S5', 'G1', 'G3', 'G5', 'G7'
];

// Warna produk
$colors = [
    'Hitam', 'Putih', 'Abu-abu', 'Silver', 'Merah', 'Biru', 'Hijau', 'Kuning',
    'Oranye', 'Ungu', 'Pink', 'Coklat', 'Gold', 'Rose Gold', 'Midnight Blue',
    'Space Gray', 'Arctic White', 'Carbon Black'
];

// Parameter untuk pembangkitan barang
$total_items = 100;  // Jumlah total barang yang akan dibuat
$batch_size = 50;     // Jumlah barang per batch insert
$start_code = 1001;    // Kode barang awal

// Mulai waktu eksekusi
$start_time = microtime(true);

// Dapatkan kode terakhir dari database
$query_last_code = "SELECT MAX(SUBSTRING(kode, 3)) as last_code FROM barang WHERE kode LIKE 'KD%'";
$result_last_code = mysqli_query($conn, $query_last_code);
if ($row = mysqli_fetch_assoc($result_last_code)) {
    if ($row['last_code'] !== null) {
        $start_code = (int)$row['last_code'] + 1;
    }
}

// Variabel untuk tracking
$total_added = 0;
$total_batches = 0;

// Loop untuk membuat barang dalam batch
for ($i = 0; $i < $total_items; $i += $batch_size) {
    $current_batch_size = min($batch_size, $total_items - $i);
    $values = [];
    
    for ($j = 0; $j < $current_batch_size; $j++) {
        // Generate data barang
        $code = 'KD' . str_pad($start_code + $i + $j, 5, '0', STR_PAD_LEFT);
        
        // Buat nama produk
        $category = $categories[array_rand($categories)];
        $brand = $brands[array_rand($brands)];
        $model = $models[array_rand($models)];
        $color = $colors[array_rand($colors)];
        
        $name_type = rand(1, 10);
        if ($name_type <= 4) {
            $name = $brand . ' ' . $category . ' ' . $model;
        } elseif ($name_type <= 7) {
            $name = $category . ' ' . $brand . ' ' . $model . ' ' . $color;
        } else {
            $name = $brand . ' ' . $model . ' ' . $category . ' ' . rand(10, 50) . '"';
        }
        
        // Generate stok (1-100)
        $stock = rand(1, 100);
        
        // Generate harga berdasarkan kategori
        $price_map = [
            'Laptop' => [4000000, 25000000],
            'Smartphone' => [1000000, 20000000],
            'Tablet' => [2000000, 15000000],
            'Monitor' => [1000000, 10000000],
            'Printer' => [700000, 5000000],
            'Scanner' => [500000, 3000000],
            'Keyboard' => [100000, 2000000],
            'Mouse' => [50000, 1500000],
            'Headset' => [100000, 3000000],
            'Speaker' => [200000, 5000000],
            'Flashdisk' => [50000, 500000],
            'Harddisk' => [500000, 3000000],
            'SSD' => [400000, 5000000],
            'RAM' => [300000, 3000000],
            'Processor' => [1000000, 15000000],
            'Motherboard' => [700000, 10000000],
            'VGA Card' => [1500000, 30000000],
            'Power Supply' => [400000, 3000000],
            'Casing' => [300000, 3000000],
            'Cooling Fan' => [100000, 1000000],
            'Webcam' => [100000, 2000000],
            'Microphone' => [200000, 5000000],
            'Router' => [200000, 5000000],
            'Switch' => [100000, 10000000],
            'Access Point' => [300000, 3000000],
            'Network Card' => [100000, 1000000],
            'Kabel LAN' => [50000, 500000],
            'Kabel HDMI' => [50000, 500000],
            'Kabel VGA' => [50000, 300000],
            'Converter' => [50000, 500000],
            'Hub USB' => [100000, 700000],
            'Card Reader' => [50000, 300000],
            'UPS' => [500000, 5000000],
            'Stabilizer' => [200000, 3000000]
        ];
        
        $price_range = isset($price_map[$category]) ? $price_map[$category] : [50000, 1000000];
        $price = rand($price_range[0], $price_range[1]);
        
        // Tambahkan ke array values untuk batch insert
        $code_esc = mysqli_real_escape_string($conn, $code);
        $name_esc = mysqli_real_escape_string($conn, $name);
        $values[] = "('$code_esc', '$name_esc', $stock, $price)";
    }
    
    // Lakukan batch insert
    if (!empty($values)) {
        $sql = "INSERT INTO barang (kode, nama, stok, harga) VALUES " . implode(", ", $values);
        
        if (mysqli_query($conn, $sql)) {
            $total_added += mysqli_affected_rows($conn);
            $total_batches++;
            
            // Tampilkan progres
            echo "Batch #$total_batches: Berhasil menambahkan " . count($values) . " barang<br>";
            ob_flush();
            flush();
        } else {
            echo "Error pada batch #$total_batches: " . mysqli_error($conn) . "<br>";
        }
    }
}

// Hitung waktu eksekusi
$execution_time = microtime(true) - $start_time;

echo "<br>Proses selesai!<br>";
echo "Total $total_added barang berhasil ditambahkan ke database.<br>";
echo "Waktu eksekusi: " . round($execution_time, 2) . " detik.<br>";
echo "<a href='index.php'>Kembali ke halaman utama</a>";

// Tutup koneksi
mysqli_close($conn);
?> 