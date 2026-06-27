<?php
session_start();
header('Content-Type: application/json');

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['id_user'];
    $id_hotel = intval($_POST['id_hotel']);

    // Cek apakah data sudah ada di wishlist
    $cek = $koneksi->query("SELECT * FROM wishlist WHERE id_user = '$id_user' AND id_hotel = '$id_hotel'");
    
    if ($cek && $cek->num_rows > 0) {
        // Jika sudah ada, hapus dari wishlist (Unlove)
        $delete = $koneksi->query("DELETE FROM wishlist WHERE id_user = '$id_user' AND id_hotel = '$id_hotel'");
        if ($delete) {
            echo json_encode(['status' => 'success', 'action' => 'removed']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus dari wishlist']);
        }
    } else {
        // Jika belum ada, masukkan ke database (Love)
        $insert = $koneksi->query("INSERT INTO wishlist (id_user, id_hotel) VALUES ('$id_user', '$id_hotel')");
        if ($insert) {
            echo json_encode(['status' => 'success', 'action' => 'added']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke wishlist']);
        }
    }
}
?>