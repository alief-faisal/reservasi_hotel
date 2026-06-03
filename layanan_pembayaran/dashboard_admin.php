<?php
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

// Check admin access
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    echo "<script>alert('Akses ditolak'); window.location='../index.php';</script>";
    exit();
}

// Statistik pembayaran
$query_total = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN status_pembayaran='lunas' THEN 1 ELSE 0 END) as lunas,
                       SUM(CASE WHEN status_pembayaran='pending' THEN 1 ELSE 0 END) as pending,
                       SUM(total_bayar) as total_revenue
                FROM pembayaran p 
                JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan";

$result = $koneksi->query($query_total);
$stats = $result->fetch_assoc();

// Data pembayaran hari ini
$query_today = "SELECT p.*, u.nama, h.nama_hotel, k.nama_kamar
                FROM pembayaran p
                JOIN pemesanan b ON p.id_pemesanan = b.id_pemesanan
                JOIN pengguna u ON b.id_pengguna = u.id_pengguna
                JOIN kamar k ON b.id_kamar = k.id_kamar
                JOIN hotel h ON k.id_hotel = h.id_hotel
                WHERE DATE(p.tanggal_pemesanan) = CURDATE()
                ORDER BY p.tanggal_pemesanan DESC
                LIMIT 10";

$today_result = $koneksi->query($query_today);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembayaran Admin - GrandStay</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        margin-bottom: 30px;
    }

    .header h1 {
        color: #2d3748;
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .header p {
        color: #718096;
        font-size: 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border-left: 4px solid;
    }

    .stat-card.total {
        border-left-color: #667eea;
    }

    .stat-card.lunas {
        border-left-color: #38a169;
    }

    .stat-card.pending {
        border-left-color: #ecc94b;
    }

    .stat-card.revenue {
        border-left-color: #3182ce;
    }

    .stat-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .stat-label {
        color: #718096;
        font-size: 0.9rem;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #2d3748;
    }

    .stat-change {
        font-size: 0.85rem;
        color: #38a169;
        margin-top: 8px;
    }

    .table-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .section-title {
        padding: 20px 25px;
        border-bottom: 2px solid #f0f0f0;
        font-size: 1.2rem;
        font-weight: 700;
        color: #2d3748;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f7fafc;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #2d3748;
        font-size: 0.9rem;
        border-bottom: 2px solid #e2e8f0;
    }

    td {
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
        color: #4a5568;
    }

    tbody tr:hover {
        background: #f8fafc;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .status-lunas {
        background: #c6f6d5;
        color: #22543d;
    }

    .status-pending {
        background: #feebc8;
        color: #7c2d12;
    }

    .method-badge {
        background: #f0f4ff;
        color: #667eea;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .amount {
        font-weight: 700;
        color: #667eea;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #718096;
    }
    </style>
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <div class="container">
        <div class="header">
            <h1>📊 Dashboard Pembayaran</h1>
            <p>Pantau seluruh aktivitas pembayaran dan revenue sistem</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">📋</div>
                <div class="stat-label">Total Pembayaran</div>
                <div class="stat-value"><?= $stats['total'] ?? 0; ?></div>
            </div>

            <div class="stat-card lunas">
                <div class="stat-icon">✓</div>
                <div class="stat-label">Pembayaran Lunas</div>
                <div class="stat-value"><?= $stats['lunas'] ?? 0; ?></div>
                <div class="stat-change">
                    +<?= isset($stats['lunas']) ? round(($stats['lunas'] / max($stats['total'], 1)) * 100) : 0; ?>% dari
                    total</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Pembayaran Pending</div>
                <div class="stat-value"><?= $stats['pending'] ?? 0; ?></div>
            </div>

            <div class="stat-card revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
            </div>
        </div>

        <div class="table-section">
            <div class="section-title">🔔 Aktivitas Pembayaran Hari Ini</div>
            <?php if ($today_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Pengguna</th>
                        <th>Hotel & Kamar</th>
                        <th>Jumlah</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $today_result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>#<?= str_pad($row['id_pembayaran'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_hotel']); ?></strong><br>
                            <small><?= htmlspecialchars($row['nama_kamar']); ?></small>
                        </td>
                        <td><span class="amount">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></span></td>
                        <td><span
                                class="method-badge"><?= ucfirst(str_replace('_', ' ', $row['metode_pembayaran'] ?? '-')); ?></span>
                        </td>
                        <td><span
                                class="status-badge status-<?= $row['status_pembayaran']; ?>"><?= ucfirst($row['status_pembayaran']); ?></span>
                        </td>
                        <td><?= date('H:i', strtotime($row['tanggal_pemesanan'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>Belum ada aktivitas pembayaran hari ini</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>