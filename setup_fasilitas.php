<?php
// Database Migration - Tambahkan Tabel Fasilitas
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

$migrations = [
    // Buat tabel fasilitas
    "CREATE TABLE IF NOT EXISTS fasilitas (
        id_fasilitas INT PRIMARY KEY AUTO_INCREMENT,
        nama_fasilitas VARCHAR(50) NOT NULL UNIQUE,
        icon_class VARCHAR(50) DEFAULT NULL
    )",
    
    // Buat tabel kamar_fasilitas (junction table)
    "CREATE TABLE IF NOT EXISTS kamar_fasilitas (
        id_kamar_fasilitas INT PRIMARY KEY AUTO_INCREMENT,
        id_kamar INT NOT NULL,
        id_fasilitas INT NOT NULL,
        FOREIGN KEY (id_kamar) REFERENCES kamar(id_kamar) ON DELETE CASCADE,
        FOREIGN KEY (id_fasilitas) REFERENCES fasilitas(id_fasilitas) ON DELETE CASCADE,
        UNIQUE KEY unique_kamar_fasilitas (id_kamar, id_fasilitas)
    )",
    
    // Insert fasilitas default
    "INSERT IGNORE INTO fasilitas (id_fasilitas, nama_fasilitas, icon_class) VALUES 
        (1, 'Kasur Premium', 'bed'),
        (2, 'Bathub', 'bath'),
        (3, 'Smart TV', 'tv'),
        (4, 'Minibar', 'wine'),
        (5, 'Kasur Biasa', 'bed'),
        (6, 'Kulkas', 'snowflake'),
        (7, 'Kursi', 'chair')"
];

$success_count = 0;
$error_count = 0;

echo "<h2>Database Migration - Fasilitas Kamar</h2>";

foreach ($migrations as $migration) {
    if ($koneksi->query($migration)) {
        $success_count++;
        echo "✓ Migration berhasil<br>";
    } else {
        $error_count++;
        echo "✗ Error: " . $koneksi->error . "<br>";
    }
}

$koneksi->close();

echo "<br><strong>Selesai! Success: $success_count, Error: $error_count</strong>";
echo "<br><a href='layanan_hotel/kelola_hotel.php'>Kembali ke Dashboard</a>";
?>
