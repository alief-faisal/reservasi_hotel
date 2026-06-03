<?php
$koneksi = new mysqli('localhost', 'root', '', 'reservasi_hotel');

echo "=== Tabel PEMBAYARAN ===\n";
$result = $koneksi->query('DESCRIBE pembayaran;');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Tabel PEMESANAN ===\n";
$result = $koneksi->query('DESCRIBE pemesanan;');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Tabel KAMAR ===\n";
$result = $koneksi->query('DESCRIBE kamar;');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>