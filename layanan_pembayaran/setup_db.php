<?php
// Database migrasi
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Daftar kolom yang harus ditambahkan
$migrations = [
    "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS metode_pembayaran VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS waktu_pembayaran DATETIME DEFAULT NULL",
    "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS kode_transaksi VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE pembayaran ADD COLUMN IF NOT EXISTS deskripsi_pembayaran TEXT DEFAULT NULL",
    "ALTER TABLE hotel ADD COLUMN IF NOT EXISTS rating INT DEFAULT 0",
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $migration) {
    if ($koneksi->query($migration)) {
        $success_count++;
        echo "✓ " . $migration . "<br>";
    } else {
        $error_count++;
        echo "✗ Error: " . $koneksi->error . "<br>";
    }
}

$koneksi->close();

echo "<br>Selesai! Success: $success_count, Error: $error_count";
?>