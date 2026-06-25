<?php


$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

echo "<h1>✓ VERIFIKASI IMPLEMENTASI FITUR FASILITAS</h1>";
echo "<hr>";

// Cek Tabel fasilitas
echo "<h2>1. Tabel fasilitas</h2>";
$result = $koneksi->query("SHOW TABLES LIKE 'fasilitas'");
if ($result->num_rows > 0) {
    echo "✓ Tabel fasilitas ada<br>";
    $data = $koneksi->query("SELECT COUNT(*) as total FROM fasilitas");
    $row = $data->fetch_assoc();
    echo "✓ Total fasilitas: " . $row['total'] . " (seharusnya 7)<br>";
} else {
    echo "✗ Tabel fasilitas TIDAK ada<br>";
}

// Tabel kamar_fasilitas
echo "<h2>2. Tabel kamar_fasilitas</h2>";
$result = $koneksi->query("SHOW TABLES LIKE 'kamar_fasilitas'");
if ($result->num_rows > 0) {
    echo "✓ Tabel kamar_fasilitas ada<br>";
} else {
    echo "✗ Tabel kamar_fasilitas TIDAK ada<br>";
}

// Cek File-file baru
echo "<h2>3. File-file Baru</h2>";
$files_check = [
    '../setup_fasilitas.php' => 'Setup Migration',
    '../layanan_pemesanan/get_fasilitas.php' => 'API Get Fasilitas'
];

foreach ($files_check as $file => $desc) {
    if (file_exists(__DIR__ . $file)) {
        echo "✓ " . $desc . " ada<br>";
    } else {
        echo "✗ " . $desc . " TIDAK ada<br>";
    }
}

// Daftar Fasilitas
echo "<h2>4. Daftar Fasilitas yang Tersedia</h2>";
$result = $koneksi->query("SELECT * FROM fasilitas ORDER BY id_fasilitas");
echo "<ol>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . htmlspecialchars($row['nama_fasilitas']) . "</li>";
}
echo "</ol>";

// Sample Data - Hotel dengan Fasilitas
echo "<h2>5. Sample: Hotel dengan Fasilitas</h2>";
$result = $koneksi->query("
    SELECT h.id_hotel, h.nama_hotel, k.id_kamar, k.nama_kamar, 
           GROUP_CONCAT(f.nama_fasilitas SEPARATOR ', ') as fasilitas
    FROM hotel h
    LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
    LEFT JOIN kamar_fasilitas kf ON k.id_kamar = kf.id_kamar
    LEFT JOIN fasilitas f ON kf.id_fasilitas = f.id_fasilitas
    GROUP BY h.id_hotel, k.id_kamar
    LIMIT 5
");

if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Hotel</th><th>Kamar</th><th>Fasilitas</th>";
    echo "</tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['nama_hotel']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_kamar']) . "</td>";
        echo "<td>" . ($row['fasilitas'] ? htmlspecialchars($row['fasilitas']) : '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Belum ada data hotel dengan fasilitas. Silakan tambah hotel baru di dashboard admin.</p>";
}

// Petunjuk Testing
echo "<h2>6. Petunjuk Testing Selanjutnya</h2>";
echo "<ol>";
echo "<li><strong>Tambah Hotel Baru:</strong> Buka <a href='kelola_hotel.php'>kelola_hotel.php</a>, isi form, pilih fasilitas, dan klik 'Simpan Hotel'</li>";
echo "<li><strong>Edit Hotel:</strong> Klik 'Edit' pada salah satu hotel, ubah fasilitas, dan simpan</li>";
echo "<li><strong>Lihat Detail Hotel:</strong> Buka index.php, pilih hotel, lihat apakah fasilitas muncul</li>";
echo "<li><strong>Pesan Hotel:</strong> Saat memilih tipe kamar, fasilitas harus tampil otomatis</li>";
echo "</ol>";

$koneksi->close();
?>
<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f8f8;
}

h1 {
    color: #333;
    border-bottom: 3px solid #dc2626;
    padding-bottom: 10px;
}

h2 {
    color: #555;
    margin-top: 20px;
}

table {
    width: 100%;
}

a {
    color: #0284c7;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.success {
    color: green;
}

.error {
    color: red;
}
</style>