<?php
// Include koneksi database
require_once '../db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Proses pencarian jika ada
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search)) {
    $where_clause = "WHERE kode LIKE ? OR nama LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
}

// Query untuk mendapatkan daftar barang dengan prepared statement
$query = "SELECT id, kode, nama, stok, harga FROM barang $where_clause ORDER BY nama ASC";

// Gunakan prepared statement
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Cek error query
if (!$result) {
    $_SESSION['error'] = "Terjadi kesalahan saat mengambil data barang: " . mysqli_error($conn);
}

// Include header
$title = "Daftar Barang";
include '../header.php';
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="flex flex-col md:flex-row justify-between items-center p-4 border-b border-gray-200">
        <h1 class="text-xl font-semibold text-gray-800 mb-3 md:mb-0">Daftar Barang</h1>
        <a href="tambah.php" class="bg-primary hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300 w-full md:w-auto text-center">
            <i class="fas fa-plus mr-2"></i>Tambah Barang Baru
        </a>
    </div>
    
    <div class="p-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Pencarian -->
        <div class="mb-6 relative">
            <div class="flex gap-2 relative">
                <input type="text" id="searchBarang" placeholder="Cari kode atau nama barang..." value="<?php echo $search; ?>" 
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary">
                <div id="searchLoader" class="absolute right-28 top-2 hidden">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                </div>
                <button id="searchButton" type="button" class="bg-info hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                    <i class="fas fa-search mr-1"></i>Cari
                </button>
                <button id="resetButton" type="button" class="bg-warning hover:bg-yellow-600 text-white px-4 py-2 rounded-md text-sm transition duration-300">
                    <i class="fas fa-undo mr-1"></i>Reset
                </button>
            </div>
            <div id="searchResults" class="absolute z-10 bg-white shadow-md rounded-md mt-1 w-full max-h-60 overflow-y-auto border border-gray-300 hidden">
                <!-- Hasil pencarian akan diisi di sini oleh JavaScript -->
            </div>
        </div>
        
        <?php if (!empty($search)): ?>
            <div class="mb-4 text-sm text-gray-600">
                <p>Menampilkan hasil pencarian untuk "<?php echo htmlspecialchars($search); ?>" 
                (<?php echo mysqli_num_rows($result); ?> barang ditemukan)</p>
            </div>
        <?php endif; ?>
        
        <!-- Tabel Barang -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody id="barangTableBody" class="bg-white divide-y divide-gray-200">
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['kode']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap"><?php echo $row['nama']; ?></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="<?php echo $row['stok'] <= 5 ? 'text-red-600 font-medium' : ($row['stok'] <= 10 ? 'text-yellow-600' : 'text-green-600'); ?>">
                                        <?php echo $row['stok']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td class="px-4 py-3 whitespace-nowrap space-x-2">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="bg-warning hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <a href="hapus.php?id=<?php echo $row['id']; ?>" 
                                       class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                       <i class="fas fa-trash mr-1"></i>Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">Tidak ada data barang</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Loading Overlay untuk indikator loading -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg shadow-lg flex flex-col items-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-3"></div>
        <p>Memuat data...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchBarang');
    const searchButton = document.getElementById('searchButton');
    const resetButton = document.getElementById('resetButton');
    const searchResults = document.getElementById('searchResults');
    const searchLoader = document.getElementById('searchLoader');
    const barangTableBody = document.getElementById('barangTableBody');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    let searchTimeout = null;
    
    // Auto focus ke pencarian saat halaman dimuat
    searchInput.focus();
    
    // Fungsi pencarian real-time
    function searchBarang(keyword) {
        if (keyword.length === 0) {
            searchResults.classList.add('hidden');
            return;
        }
        
        // Tampilkan loader
        searchLoader.classList.remove('hidden');
        
        // Batal pencarian sebelumnya jika ada
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Tunda pencarian untuk mengurangi request ke server
        searchTimeout = setTimeout(() => {
            fetch(`search_api.php?search=${encodeURIComponent(keyword)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Sembunyikan loader
                searchLoader.classList.add('hidden');
                
                // Kosongkan daftar hasil
                searchResults.innerHTML = '';
                
                if (data.status === 'success' && data.data.length > 0) {
                    // Tampilkan hasil pencarian
                    data.data.forEach(item => {
                        const resultItem = document.createElement('div');
                        resultItem.className = 'p-2 hover:bg-gray-100 cursor-pointer border-b border-gray-200';
                        resultItem.innerHTML = `
                            <div class="flex justify-between">
                                <div><b>${item.kode}</b> - ${item.nama}</div>
                                <div class="text-gray-600">${item.harga_formatted}</div>
                            </div>
                            <div class="text-sm">
                                <span class="${item.stok_status === 'rendah' ? 'text-red-600 font-medium' : (item.stok_status === 'sedang' ? 'text-yellow-600' : 'text-green-600')}">
                                    Stok: ${item.stok}
                                </span>
                            </div>
                        `;
                        
                        // Event saat item diklik
                        resultItem.addEventListener('click', function() {
                            updateTable(data.data);
                            searchResults.classList.add('hidden');
                            
                            // Update URL dengan parameter pencarian
                            const url = new URL(window.location.href);
                            url.searchParams.set('search', keyword);
                            window.history.pushState({}, '', url);
                        });
                        
                        searchResults.appendChild(resultItem);
                    });
                    
                    searchResults.classList.remove('hidden');
                } else {
                    // Tidak ada hasil, tampilkan pesan
                    const noResult = document.createElement('div');
                    noResult.className = 'p-2 text-center text-gray-500';
                    noResult.textContent = 'Tidak ada hasil ditemukan';
                    searchResults.appendChild(noResult);
                    searchResults.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                searchLoader.classList.add('hidden');
                
                // Tampilkan pesan error
                searchResults.innerHTML = `
                    <div class="p-2 text-center text-red-500">
                        Terjadi kesalahan. Silakan coba lagi.
                    </div>
                `;
                searchResults.classList.remove('hidden');
            });
        }, 300); // Tunda 300ms
    }
    
    // Fungsi untuk memperbarui tabel
    function updateTable(data) {
        // Kosongkan isi tabel
        barangTableBody.innerHTML = '';
        
        if (data.length > 0) {
            // Isi dengan data baru
            data.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                
                const stokClass = item.stok <= 5 ? 'text-red-600 font-medium' : (item.stok <= 10 ? 'text-yellow-600' : 'text-green-600');
                
                row.innerHTML = `
                    <td class="px-4 py-3 whitespace-nowrap">${item.kode}</td>
                    <td class="px-4 py-3 whitespace-nowrap">${item.nama}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="${stokClass}">${item.stok}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">${item.harga_formatted}</td>
                    <td class="px-4 py-3 whitespace-nowrap space-x-2">
                        <a href="edit.php?id=${item.id}" class="bg-warning hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </a>
                        <a href="hapus.php?id=${item.id}" 
                           class="bg-danger hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-300 inline-block"
                           onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                           <i class="fas fa-trash mr-1"></i>Hapus
                        </a>
                    </td>
                `;
                
                barangTableBody.appendChild(row);
            });
        } else {
            // Tampilkan pesan jika tidak ada data
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="5" class="px-4 py-4 text-center text-gray-500">Tidak ada data barang</td>
            `;
            barangTableBody.appendChild(emptyRow);
        }
    }
    
    // Input event untuk pencarian real-time
    searchInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        if (keyword.length >= 1) {
            searchBarang(keyword);
        } else {
            searchResults.classList.add('hidden');
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
        }
    });
    
    // Klik di luar hasil pencarian untuk menutupnya
    document.addEventListener('click', function(e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.classList.add('hidden');
        }
    });
    
    // Tombol search
    searchButton.addEventListener('click', function() {
        const keyword = searchInput.value.trim();
        if (keyword.length >= 1) {
            // Tampilkan loading overlay
            loadingOverlay.classList.remove('hidden');
            loadingOverlay.classList.add('flex');
            
            // Redirect ke halaman dengan parameter pencarian
            window.location.href = `list.php?search=${encodeURIComponent(keyword)}`;
        }
    });
    
    // Tombol reset
    resetButton.addEventListener('click', function() {
        // Tampilkan loading overlay
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
        
        window.location.href = 'list.php';
    });
    
    // Tangani tombol Enter pada input pencarian
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchButton.click();
        }
    });
});
</script>

<?php
// Include footer
include '../footer.php';
?> 