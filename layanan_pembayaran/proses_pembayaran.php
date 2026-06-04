<?php
// API backend untuk proses pembayaran
session_start();
header('Content-Type: application/json');

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

if ($koneksi->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal']);
    exit();
}

if (!isset($_SESSION['id_pengguna'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$id_pembayaran = $_POST['id_pembayaran'] ?? 0;
$metode_bayar = $_POST['metode_bayar'] ?? '';

// Validasi input
if (!$id_pembayaran || !$metode_bayar) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

// Ambil data pembayaran
$query = "SELECT p.*, b.id_pengguna FROM pembayaran p 
          JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan 
          WHERE p.id_pembayaran = ?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $id_pembayaran);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Data pembayaran tidak ditemukan']);
    exit();
}

// Validasi pengguna yang mengakses
if ($data['id_pengguna'] != $_SESSION['id_pengguna']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak berhak mengakses pembayaran ini']);
    exit();
}

// Sudah lunas
if ($data['status_pembayaran'] === 'lunas') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Pembayaran sudah dikonfirmasi sebelumnya']);
    exit();
}

// Simulasi delay pembayaran berdasarkan metode
$delay_time = 0;
switch ($metode_bayar) {
    case 'gopay':
    case 'ovo':
    case 'dana':
        $delay_time = 3; 
        break;
    case 'kartu_kredit':
        $delay_time = 2; 
        break;
    case 'transfer_bank':
        $delay_time = 5; 
        break;
}

// Simulasi proses pembayaran dengan peluang gagal
sleep($delay_time);

$random_fail = rand(1, 100);
if ($random_fail <= 1) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Pembayaran gagal. Silakan coba lagi dengan metode pembayaran lain.',
        'code' => 'PAYMENT_FAILED'
    ]);
    exit();
}

// Transaction dimulai
$koneksi->begin_transaction();

try {
    $waktu_pembayaran = date('Y-m-d H:i:s');
    $kode_transaksi = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    // Update status pembayaran
    $stmt_update = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = 'lunas', 
                                     metode_pembayaran = ?, waktu_pembayaran = ?, kode_transaksi = ? 
                                     WHERE id_pembayaran = ?");
    $stmt_update->bind_param("sssi", $metode_bayar, $waktu_pembayaran, $kode_transaksi, $id_pembayaran);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Gagal update status pembayaran");
    }
    
    // Update status pemesanan
    $stmt_pesan = $koneksi->prepare("UPDATE pemesanan SET status_pemesanan = 'berhasil' 
                                    WHERE id_pemesanan = ?");
    $stmt_pesan->bind_param("i", $data['id_pemesanan']);
    
    if (!$stmt_pesan->execute()) {
        throw new Exception("Gagal update status pemesanan");
    }
    
    // Kurangi stok kamar
    $stmt_stok = $koneksi->prepare("UPDATE kamar SET stok_kamar = stok_kamar - 1 
                                   WHERE id_kamar = ?");
    $stmt_stok->bind_param("i", $data['id_kamar']);
    
    if (!$stmt_stok->execute()) {
        throw new Exception("Gagal mengurangi stok kamar");
    }
    
    $koneksi->commit();
    
    // Response sukses
    echo json_encode([
        'status' => 'success',
        'message' => 'Pembayaran berhasil diproses',
        'kode_transaksi' => $kode_transaksi,
        'waktu_pembayaran' => $waktu_pembayaran,
        'id_pembayaran' => $id_pembayaran,
        'redirect_url' => "bukti_pembayaran.php?id_pembayaran=$id_pembayaran"
    ]);
    
} catch (Exception $e) {
    $koneksi->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage()
    ]);
}

$koneksi->close();
?>