<?php
// API endpoint untuk webhook notifikasi pembayaran (webhook dari payment gateway)
session_start();
header('Content-Type: application/json');

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

// Simulasi webhook dari payment gateway
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data || !isset($data['id_pembayaran']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$id_pembayaran = $data['id_pembayaran'];
$status = $data['status'];

// Validasi status
$valid_status = ['lunas', 'pending', 'batal'];
if (!in_array($status, $valid_status)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Status tidak valid']);
    exit();
}

try {
    $koneksi->begin_transaction();

    $waktu_update = date('Y-m-d H:i:s');

    // Update status pembayaran
    $stmt = $koneksi->prepare("UPDATE pembayaran SET status_pembayaran = ?, waktu_pembayaran = ? WHERE id_pembayaran = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $koneksi->error);
    }

    $stmt->bind_param("ssi", $status, $waktu_update, $id_pembayaran);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Jika status lunas, update juga pemesanan
    if ($status === 'lunas') {
        // Ambil id_pemesanan
        $stmt_get = $koneksi->prepare("SELECT id_pemesanan FROM pembayaran WHERE id_pembayaran = ?");
        $stmt_get->bind_param("i", $id_pembayaran);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $payment_data = $result->fetch_assoc();

        if ($payment_data) {
            $stmt_pesan = $koneksi->prepare("UPDATE pemesanan SET status_pemesanan = 'berhasil' WHERE id_pemesanan = ?");
            $stmt_pesan->bind_param("i", $payment_data['id_pemesanan']);

            if (!$stmt_pesan->execute()) {
                throw new Exception("Gagal update pemesanan");
            }
        }
    }

    $koneksi->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook diproses berhasil',
        'id_pembayaran' => $id_pembayaran,
        'status_baru' => $status
    ]);

} catch (Exception $e) {
    $koneksi->rollback();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing webhook: ' . $e->getMessage()
    ]);
}

$koneksi->close();