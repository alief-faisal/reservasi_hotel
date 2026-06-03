<?php
$koneksi = new mysqli('localhost', 'root', '', 'reservasi_hotel');
$hasil = $koneksi->query('DESCRIBE pembayaran');
echo "Kolom-kolom di tabel pembayaran:\n";
while($row = $hasil->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

// Cek data sample
echo "\n\nSample data pembayaran:\n";
$sample = $koneksi->query('SELECT * FROM pembayaran LIMIT 1');
if($sample->num_rows > 0) {
    $data = $sample->fetch_assoc();
    echo "Kolom yang ada:\n";
    foreach($data as $key => $value) {
        echo "  $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
}
?>