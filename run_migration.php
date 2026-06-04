<?php
// migration menambah kolom rating
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Cek kolom rating ada
$check_column = $koneksi->query("SHOW COLUMNS FROM hotel LIKE 'rating'");

if ($check_column->num_rows === 0) {
    // Tambah kolom rating
    if ($koneksi->query("ALTER TABLE hotel ADD COLUMN rating INT DEFAULT 0")) {
        echo "✓ Kolom 'rating' berhasil ditambahkan ke tabel hotel<br>";
    } else {
        echo "✗ Error: " . $koneksi->error . "<br>";
    }
} else {
    echo "✓ Kolom 'rating' sudah ada di tabel hotel<br>";
}

$koneksi->close();
echo "<br><a href='layanan_hotel/kelola_hotel.php'>Kembali ke Dashboard Admin</a>";
?>