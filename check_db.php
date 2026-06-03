<?php
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");
$result = $koneksi->query("DESCRIBE pengguna;");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";
?>