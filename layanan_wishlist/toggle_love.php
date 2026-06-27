<?php
// ============================================================
//  layanan_wishlist/toggle_love.php
//  API endpoint: toggle simpan/hapus hotel ke wishlist
//  Response: JSON
// ============================================================
session_start();
header('Content-Type: application/json');

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    echo json_encode(['success' => false, 'message' => 'login_required']);
    exit;
}

// Ambil & validasi id_hotel
$id_hotel    = intval($_POST['id_hotel'] ?? 0);
$id_pengguna = intval($_SESSION['id_pengguna']);

if ($id_hotel <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID hotel tidak valid']);
    exit;
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");
if ($koneksi->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

// Cek apakah sudah ada di wishlist
$cek = $koneksi->prepare("SELECT id_wishlist FROM wishlist WHERE id_pengguna = ? AND id_hotel = ?");
$cek->bind_param("ii", $id_pengguna, $id_hotel);
$cek->execute();
$hasil_cek = $cek->get_result();

if ($hasil_cek->num_rows > 0) {
    // Sudah ada → hapus (un-love)
    $hapus = $koneksi->prepare("DELETE FROM wishlist WHERE id_pengguna = ? AND id_hotel = ?");
    $hapus->bind_param("ii", $id_pengguna, $id_hotel);
    $hapus->execute();

    // Hitung total wishlist terbaru
    $total = $koneksi->query("SELECT COUNT(*) as total FROM wishlist WHERE id_pengguna = $id_pengguna")->fetch_assoc();

    echo json_encode([
        'success' => true,
        'loved'   => false,
        'message' => 'Dihapus dari wishlist',
        'total'   => intval($total['total'])
    ]);
} else {
    // Belum ada → tambah (love)
    $tambah = $koneksi->prepare("INSERT INTO wishlist (id_pengguna, id_hotel) VALUES (?, ?)");
    $tambah->bind_param("ii", $id_pengguna, $id_hotel);
    $tambah->execute();

    // Hitung total wishlist terbaru
    $total = $koneksi->query("SELECT COUNT(*) as total FROM wishlist WHERE id_pengguna = $id_pengguna")->fetch_assoc();

    echo json_encode([
        'success' => true,
        'loved'   => true,
        'message' => 'Ditambahkan ke wishlist',
        'total'   => intval($total['total'])
    ]);
}

$koneksi->close();