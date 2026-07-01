<?php
// API untuk mengambil fasilitas berdasarkan id_kamar
header('Content-Type: application/json');

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (isset($_GET['id_kamar'])) {
    $id_kamar = intval($_GET['id_kamar']);

    $query = "SELECT f.id_fasilitas, f.nama_fasilitas 
              FROM fasilitas f
              INNER JOIN kamar_fasilitas kf ON f.id_fasilitas = kf.id_fasilitas
              WHERE kf.id_kamar = ?";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $id_kamar);
    $stmt->execute();
    $result = $stmt->get_result();

    $fasilitas = [];
    while ($row = $result->fetch_assoc()) {
        $fasilitas[] = $row;
    }

    echo json_encode($fasilitas);
} else {
    echo json_encode([]);
}

$koneksi->close();