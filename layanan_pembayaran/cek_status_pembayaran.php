<?php
// API endpoint untuk polling status pembayaran (untuk real-time update)
session_start();
header('Content-Type: application/json');

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if (!isset($_SESSION['id_pengguna'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$id_pembayaran = $_GET['id_pembayaran'] ?? 0;

if (!$id_pembayaran) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID pembayaran tidak valid']);
    exit();
}

$query = "SELECT p.status_pembayaran, p.waktu_pembayaran, p.kode_transaksi, p.metode_pembayaran,
                 b.id_pengguna
          FROM pembayaran p
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan
          WHERE p.id_pembayaran = ?";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data pembayaran tidak ditemukan']);
    exit();
}

// Validasi kepemilikan
if ($data['id_pengguna'] != $_SESSION['id_pengguna']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak berhak mengakses data ini']);
    exit();
}

echo json_encode([
    'status_pembayaran' => $data['status_pembayaran'],
    'waktu_pembayaran' => $data['waktu_pembayaran'],
    'kode_transaksi' => $data['kode_transaksi'],
    'metode_pembayaran' => $data['metode_pembayaran']
]);

$koneksi->close();