<?php
// Script untuk menambah kolom rating ke tabel hotel
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    die("❌ Koneksi gagal: " . $koneksi->connect_error . "\n");
}

echo "🔍 Memeriksa kolom 'rating' di tabel 'hotel'...\n";

// kolom rating
$check_column = $koneksi->query("SHOW COLUMNS FROM hotel LIKE 'rating'");

if ($check_column->num_rows === 0) {
    echo "⏳ Menambahkan kolom 'rating'...\n";
    
    // Tambah kolom rating
    if ($koneksi->query("ALTER TABLE hotel ADD COLUMN rating INT DEFAULT 0")) {
        echo "✅ Kolom 'rating' berhasil ditambahkan ke tabel hotel\n";
        echo "✅ Migration selesai!\n";
    } else {
        echo "❌ Error: " . $koneksi->error . "\n";
        exit(1);
    }
} else {
    echo "✅ Kolom 'rating' sudah ada di tabel hotel\n";
}

// Tampilkan struktur tabel
echo "\n📊 Struktur tabel hotel:\n";
echo "=====================================\n";
$result = $koneksi->query("DESCRIBE hotel");
while ($row = $result->fetch_assoc()) {
    printf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
}

$koneksi->close();
echo "\n✅ Selesai!\n";