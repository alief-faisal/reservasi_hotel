<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

// proses hapus data
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id_hotel'])) {
    $id_delete = intval($_GET['id_hotel']);
    
    $stmt_foto = $koneksi->prepare("SELECT foto FROM hotel WHERE id_hotel = ?");
    $stmt_foto->bind_param("i", $id_delete);
    $stmt_foto->execute();
    $nama_foto = $stmt_foto->get_result()->fetch_assoc()['foto'];
    
    if ($nama_foto && $nama_foto !== 'default.jpg') {
        @unlink("../assets/" . $nama_foto); 
    }

    $stmt_del = $koneksi->prepare("DELETE FROM hotel WHERE id_hotel = ?");
    $stmt_del->bind_param("i", $id_delete);
    if ($stmt_del->execute()) {
        echo "<script>alert('Hotel berhasil dihapus.'); window.location='kelola_hotel.php';</script>";
    }
    exit();
}

// Tambah Data Baru
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_hotel'])) {
    $nama = $_POST['nama_hotel'];
    $deskripsi = $_POST['deskripsi'];
    $lokasi = $_POST['lokasi'];
    $nama_file_final = "default.jpg";

    if (isset($_FILES['foto_hotel']) && $_FILES['foto_hotel']['error'] === 0) {
        $nama_file_asal = $_FILES['foto_hotel']['name'];
        $tmp_file = $_FILES['foto_hotel']['tmp_name'];
        $ekstensi = strtolower(pathinfo($nama_file_asal, PATHINFO_EXTENSION));
        
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            $nama_file_final = time() . "_" . bin2hex(random_bytes(4)) . "." . $ekstensi;
            move_uploaded_file($tmp_file, "../assets/" . $nama_file_final);
        }
    }

    /* Simpan data hotel */
    $stmt = $koneksi->prepare("INSERT INTO hotel (nama_hotel, deskripsi, lokasi, foto) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $deskripsi, $lokasi, $nama_file_final);
    
    if($stmt->execute()) {
        $id_hotel_baru = $koneksi->insert_id;
        
        /* logic data tipe kamar ke-1*/
        $tipe_k1 = $_POST['tipe_kamar_1'];
        $nama_k1 = ($tipe_k1 === 'Standard') ? 'Room Standard' : 'Room Deluxe';
        $harga_k1 = doubleval($_POST['harga_kamar_1']);
        $stok_k1 = intval($_POST['stok_kamar_1']);
        $diskon_k1 = intval($_POST['diskon_kamar_1'] ?? 0);
        
        $stmt_kamar1 = $koneksi->prepare("INSERT INTO kamar (id_hotel, nama_kamar, tipe_kamar, harga_per_malam, stok_kamar, diskon_persen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_kamar1->bind_param("issdii", $id_hotel_baru, $nama_k1, $tipe_k1, $harga_k1, $stok_k1, $diskon_k1);
        $stmt_kamar1->execute();

        /* logic data tipe kamar ke-2 */
        $tipe_k2 = $_POST['tipe_kamar_2'];
        $nama_k2 = ($tipe_k2 === 'Deluxe') ? 'Room Deluxe' : 'Room Standard';
        $harga_k2 = doubleval($_POST['harga_kamar_2']);
        $stok_k2 = intval($_POST['stok_kamar_2']);
        $diskon_k2 = intval($_POST['diskon_kamar_2'] ?? 0);
        
        $stmt_kamar2 = $koneksi->prepare("INSERT INTO kamar (id_hotel, nama_kamar, tipe_kamar, harga_per_malam, stok_kamar, diskon_persen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_kamar2->bind_param("issdii", $id_hotel_baru, $nama_k2, $tipe_k2, $harga_k2, $stok_k2, $diskon_k2);
        $stmt_kamar2->execute();

        echo "<script>alert('Hotel dan pilihan kamar berhasil disimpan.'); window.location='kelola_hotel.php';</script>";
    }
}

// Logic Menangkap Parameter Pencarian Otomatis Admin
$pencarian_admin = isset($_GET['cari_admin']) ? trim($_GET['cari_admin']) : '';

if ($pencarian_admin !== '') {
    $query_list = "SELECT h.*, MIN(k.harga_per_malam) AS harga_terendah
                   FROM hotel h
                   LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                   WHERE h.nama_hotel LIKE ?
                   GROUP BY h.id_hotel
                   ORDER BY h.id_hotel DESC";
    $stmt_list = $koneksi->prepare($query_list);
    $keyword = "%" . $pencarian_admin . "%";
    $stmt_list->bind_param("s", $keyword);
    $stmt_list->execute();
    $list_hotel = $stmt_list->get_result();
} else {
    $query_list = "SELECT h.*, MIN(k.harga_per_malam) AS harga_terendah
                   FROM hotel h
                   LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                   GROUP BY h.id_hotel
                   ORDER BY h.id_hotel DESC";
    $list_hotel = $koneksi->query($query_list);
}

// Statistik penjualan hotel hari ini
$query_stats = "SELECT h.id_hotel, h.nama_hotel, COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan, SUM(CASE WHEN p.status_pembayaran = 'lunas' THEN 1 ELSE 0 END) as pesanan_lunas
                FROM hotel h
                LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                LEFT JOIN pemesanan b ON k.id_kamar = b.id_kamar
                LEFT JOIN pembayaran p ON b.id_pemesanan = p.id_pemesanan
                WHERE DATE(b.dibuat_pada) = CURDATE()
                GROUP BY h.id_hotel, h.nama_hotel
                ORDER BY jumlah_pesanan DESC";

$stats_result = $koneksi->query($query_stats);
$stats_data = [];
while($row = $stats_result->fetch_assoc()) {
    $stats_data[] = $row;
}

// Persiapkan data untuk Chart.js
$hotel_names = json_encode(array_map(function($item) { return $item['nama_hotel']; }, $stats_data));
$pesanan_counts = json_encode(array_map(function($item) { return $item['jumlah_pesanan']; }, $stats_data));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Control Admin</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    body {
        background-color: #f8fafc;
        color: #0f172a;
    }

    .panel-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        gap: 30px;
    }

    .panel-title {
        font-size: 1.5rem;
        font-weight: 600;
        flex: 1;
    }

    .header-search {
        width: 320px;
        position: relative;
    }

    .header-search input {
        width: 100%;
        padding: 10px 16px 10px 38px;
        font-size: 0.88rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        background-color: #ffffff;
    }

    .header-search input:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
    }

    .header-search svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 14px;
        height: 14px;
        fill: #94a3b8;
        pointer-events: none;
    }

    .grid-layout {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 30px;
        align-items: start;
    }

    .box-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 24px;
    }

    .box-title {
        font-size: 1.05rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #334155;
    }

    .sub-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        margin: 20px 0 10px 0;
        padding-top: 10px;
        border-top: 1px dashed #e2e8f0;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 5px;
        color: #475569;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 9px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.88rem;
        background: white;
        outline: none;
    }

    .flex-inputs {
        display: flex;
        gap: 10px;
    }

    .btn-submit {
        background: #dc2626;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 6px;
        width: 100%;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 15px;
    }

    .btn-submit:hover {
        background: #b91c1c;
    }

    .right-content-wrapper {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .table-responsive {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.88rem;
    }

    th {
        background: #f1f5f9;
        padding: 12px 16px;
        color: #475569;
        font-weight: 600;
    }

    td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .img-thumb {
        width: 50px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        background: #e2e8f0;
    }

    .action-links a {
        text-decoration: none;
        font-weight: 500;
        margin-right: 12px;
        font-size: 0.85rem;
    }

    .link-edit {
        color: #0284c7;
    }

    .link-del {
        color: #ef4444;
    }

    .stats-section {
        grid-column: 1 / -1;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 24px;
        margin-top: 20px;
    }

    .stats-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #0f172a;
    }

    .chart-container {
        position: relative;
        height: 350px;
        margin-bottom: 30px;
    }

    .no-data-msg {
        text-align: center;
        color: #64748b;
        padding: 40px;
        font-size: 0.95rem;
    }
    </style>
</head>

<body>

    <?php include '../komponen/navigasi.php'; ?>

    <main class="panel-container">
        <header class="panel-header">
            <h1 class="panel-title">Dashboard Manajemen Hotel</h1>
            <form id="adminSearchForm" class="header-search" action="" method="GET">
                <input type="text" name="cari_admin" id="adminSearchInput"
                    value="<?= htmlspecialchars($pencarian_admin); ?>" placeholder="Cari hotel...">
                <svg viewBox="0 0 24 24">
                    <path
                        d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                </svg>
            </form>
        </header>

        <div class="grid-layout">
            <section class="box-card">
                <h2 class="box-title">Tambah Hotel Baru</h2>
                <form action="" method="POST" enctype="multipart/form-data">

                    <div class="form-group">
                        <label>Nama Hotel</label>
                        <input type="text" name="nama_hotel" required placeholder="Aston">
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="2" required placeholder="Fasilitas singkat..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Wilayah Banten</label>
                        <select name="lokasi" required>
                            <option value="" disabled selected>-- Pilih Wilayah --</option>
                            <option value="Kota Serang">Kota Serang</option>
                            <option value="Kota Tangerang">Kota Tangerang</option>
                            <option value="Kota Tangerang Selatan">Kota Tangerang Selatan</option>
                            <option value="Kota Cilegon">Kota Cilegon</option>
                            <option value="Kabupaten Serang">Kabupaten Serang</option>
                            <option value="Kabupaten Tangerang">Kabupaten Tangerang</option>
                            <option value="Kabupaten Pandeglang">Kabupaten Pandeglang</option>
                            <option value="Kabupaten Lebak">Kabupaten Lebak</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Foto Hotel</label>
                        <input type="file" name="foto_hotel" accept="image/*" required>
                    </div>

                    <div class="sub-section-title">Tipe Kamar 1</div>
                    <div class="form-group">
                        <label>Pilih Tipe</label>
                        <select name="tipe_kamar_1" required>
                            <option value="Standard" selected>Standard Room</option>
                            <option value="Deluxe">Deluxe Room</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tarif & Stok</label>
                        <div class="flex-inputs">
                            <input type="number" name="harga_kamar_1" placeholder="Harga Rp" required>
                            <input type="number" name="stok_kamar_1" placeholder="Stok" value="10" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Diskon (%)</label>
                        <input type="number" name="diskon_kamar_1" placeholder="0" min="0" max="100" value="0">
                    </div>

                    <div class="sub-section-title">Tipe Kamar 2</div>
                    <div class="form-group">
                        <label>Pilih Tipe</label>
                        <select name="tipe_kamar_2" required>
                            <option value="Deluxe" selected>Deluxe Room</option>
                            <option value="Standard">Standard Room</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tarif & Stok</label>
                        <div class="flex-inputs">
                            <input type="number" name="harga_kamar_2" placeholder="Harga Rp" required>
                            <input type="number" name="stok_kamar_2" placeholder="Stok" value="10" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Diskon (%)</label>
                        <input type="number" name="diskon_kamar_2" placeholder="0" min="0" max="100" value="0">
                    </div>

                    <button type="submit" name="tambah_hotel" class="btn-submit">Simpan Hotel</button>
                </form>
            </section>

            <div class="right-content-wrapper">

                <section class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Hotel</th>
                                <th>Wilayah</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($list_hotel->num_rows > 0): ?>
                            <?php while($row = $list_hotel->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php 
                                        $img = $row['foto'];
                                        $path = (empty($img) || !file_exists("../assets/".$img)) ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=80&q=80" : "/reservasi_hotel/assets/".$img;
                                    ?>
                                    <img src="<?= $path; ?>" class="img-thumb" alt="">
                                </td>
                                <td style="font-weight:500; color:#0f172a;"><?= htmlspecialchars($row['nama_hotel']); ?>
                                </td>
                                <td><?= htmlspecialchars($row['lokasi']); ?></td>
                                <td>Rp
                                    <?= $row['harga_terendah'] ? number_format($row['harga_terendah'], 0, ',', '.') : '-'; ?>
                                </td>
                                <td class="action-links">
                                    <a href="edit_hotel.php?id=<?= $row['id_hotel']; ?>" class="link-edit">Edit</a>
                                    <a href="kelola_hotel.php?aksi=hapus&id_hotel=<?= $row['id_hotel']; ?>"
                                        class="link-del" onclick="return confirm('Hapus data ini?');">Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#64748b; padding: 40px 0;">Data hotel
                                    tidak ditemukan atau belum tersedia.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </section>
            </div>

            <!-- STATISTIK GRAFIK PENJUALAN HARI INI -->
            <section class="stats-section">
                <h2 class="stats-title">📊 Statistik Penjualan Hari Ini</h2>
                <?php if (!empty($stats_data)): ?>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
                <?php else: ?>
                <div class="no-data-msg">Belum ada pesanan hari ini.</div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    const adminSearchInput = document.getElementById('adminSearchInput');
    const adminSearchForm = document.getElementById('adminSearchForm');
    let searchTimer;

    adminSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);


        searchTimer = setTimeout(() => {
            adminSearchForm.submit();
        }, 300);
    });

    window.addEventListener('DOMContentLoaded', () => {
        const currentVal = adminSearchInput.value;
        if (currentVal !== '') {
            adminSearchInput.value = '';
            adminSearchInput.focus();
            adminSearchInput.value = currentVal;
        }
    });

    // Grafik Statistik Penjualan
    <?php if (!empty($stats_data)): ?>
    const chartCanvas = document.getElementById('salesChart');
    if (chartCanvas) {
        const hotelNames = <?= $hotel_names; ?>;
        const pesananCounts = <?= $pesanan_counts; ?>;

        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: hotelNames,
                datasets: [{
                    label: 'Jumlah Pesanan Hari Ini',
                    data: pesananCounts,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#30cfd0'
                    ],
                    borderColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#4facfe',
                        '#00f2fe',
                        '#43e97b',
                        '#fa709a',
                        '#fee140',
                        '#30cfd0'
                    ],
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: '#555',
                    hoverBorderColor: '#333'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            padding: 20,
                            boxWidth: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 12
                        },
                        borderColor: '#667eea',
                        borderWidth: 1,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                return 'Pesanan: ' + context.parsed.x + ' booking';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 11
                            }
                        },
                        grid: {
                            color: '#e2e8f0',
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        },
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    </script>
</body>

</html>