<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

/* hapus banner */
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_banner' && isset($_GET['id_banner'])) {
    $id_b = intval($_GET['id_banner']);
    $stmt_b = $koneksi->prepare("SELECT nama_file FROM banner WHERE id_banner = ?");
    $stmt_b->bind_param("i", $id_b);
    $stmt_b->execute();
    $row_b = $stmt_b->get_result()->fetch_assoc();
    if ($row_b) {
        @unlink("../assets/banner/" . $row_b['nama_file']);
    }
    
    // bind_param untuk menghindari SQL Injection
    $del_b = $koneksi->prepare("DELETE FROM banner WHERE id_banner = ?");
    $del_b->bind_param("i", $id_b);
    $del_b->execute();
    echo "<script>alert('Banner berhasil dihapus.'); window.location='kelola_hotel.php';</script>";
    exit();
}

/* logika upload banner */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_banner'])) {
    $judul_b = trim($_POST['judul_banner'] ?? '');
    $urutan_b = intval($_POST['urutan_banner'] ?? 0);

    if (isset($_FILES['foto_banner']) && $_FILES['foto_banner']['error'] === 0) {
        $ext_b = strtolower(pathinfo($_FILES['foto_banner']['name'], PATHINFO_EXTENSION));
        if (in_array($ext_b, ['jpg', 'jpeg', 'png', 'webp'])) {
            $dir_banner = "../assets/banner/";
            if (!is_dir($dir_banner)) mkdir($dir_banner, 0775, true);
            $nama_b = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext_b;
            move_uploaded_file($_FILES['foto_banner']['tmp_name'], $dir_banner . $nama_b);

            $stmt_ins = $koneksi->prepare("INSERT INTO banner (judul, nama_file, urutan) VALUES (?, ?, ?)");
            $stmt_ins->bind_param("ssi", $judul_b, $nama_b, $urutan_b);
            $stmt_ins->execute();
            echo "<script>alert('Banner berhasil ditambahkan.'); window.location='kelola_hotel.php';</script>";
            exit();
        } else {
            echo "<script>alert('Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.');</script>";
        }
    } else {
        echo "<script>alert('Gagal upload foto banner.');</script>";
    }
}

/* logika hapus hotel */
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

/* logika ambil data fasilitas */
$query_fasilitas = "SELECT * FROM fasilitas ORDER BY id_fasilitas ASC";
$hasil_fasilitas = $koneksi->query($query_fasilitas);
$fasilitas_list = [];
while ($fac = $hasil_fasilitas->fetch_assoc()) {
    $fasilitas_list[] = $fac;
}

 /* logika tambah hotel baru */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_hotel'])) {
    $nama       = $_POST['nama_hotel'];
    $deskripsi  = $_POST['deskripsi'];
    $lokasi     = $_POST['lokasi'];
    $rating     = intval($_POST['rating']);
    $nama_file_final = "default.jpg";

    if (isset($_FILES['foto_hotel']) && $_FILES['foto_hotel']['error'] === 0) {
        $nama_file_asal = $_FILES['foto_hotel']['name'];
        $tmp_file       = $_FILES['foto_hotel']['tmp_name'];
        $ekstensi       = strtolower(pathinfo($nama_file_asal, PATHINFO_EXTENSION));
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            $nama_file_final = time() . "_" . bin2hex(random_bytes(4)) . "." . $ekstensi;
            move_uploaded_file($tmp_file, "../assets/" . $nama_file_final);
        }
    }

    $stmt = $koneksi->prepare("INSERT INTO hotel (nama_hotel, deskripsi, lokasi, foto, rating) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $nama, $deskripsi, $lokasi, $nama_file_final, $rating);

    if ($stmt->execute()) {
        $id_hotel_baru = $koneksi->insert_id;

        /* Kamar 1 */
        $tipe_k1  = $_POST['tipe_kamar_1'];
        $nama_k1  = ($tipe_k1 === 'Standard') ? 'Room Standard' : 'Room Deluxe';
        $harga_k1 = doubleval($_POST['harga_kamar_1']);
        $stok_k1  = intval($_POST['stok_kamar_1']);
        $diskon_k1= intval($_POST['diskon_kamar_1'] ?? 0);

        $stmt_k1 = $koneksi->prepare("INSERT INTO kamar (id_hotel, nama_kamar, tipe_kamar, harga_per_malam, stok_kamar, diskon_persen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_k1->bind_param("issdii", $id_hotel_baru, $nama_k1, $tipe_k1, $harga_k1, $stok_k1, $diskon_k1);
        $stmt_k1->execute();
        $id_kamar1 = $stmt_k1->insert_id;

        if (isset($_POST['fasilitas_kamar_1']) && is_array($_POST['fasilitas_kamar_1'])) {
            foreach (array_slice($_POST['fasilitas_kamar_1'], 0, 7) as $id_fasilitas) {
                $id_fac = intval($id_fasilitas);
                $sf = $koneksi->prepare("INSERT INTO kamar_fasilitas (id_kamar, id_fasilitas) VALUES (?, ?)");
                $sf->bind_param("ii", $id_kamar1, $id_fac);
                $sf->execute();
            }
        }

        /* Kamar 2 */
        $tipe_k2  = $_POST['tipe_kamar_2'];
        $nama_k2  = ($tipe_k2 === 'Deluxe') ? 'Room Deluxe' : 'Room Standard';
        $harga_k2 = doubleval($_POST['harga_kamar_2']);
        $stok_k2  = intval($_POST['stok_kamar_2']);
        $diskon_k2= intval($_POST['diskon_kamar_2'] ?? 0);

        $stmt_k2 = $koneksi->prepare("INSERT INTO kamar (id_hotel, nama_kamar, tipe_kamar, harga_per_malam, stok_kamar, diskon_persen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_k2->bind_param("issdii", $id_hotel_baru, $nama_k2, $tipe_k2, $harga_k2, $stok_k2, $diskon_k2);
        $stmt_k2->execute();
        $id_kamar2 = $stmt_k2->insert_id;

        if (isset($_POST['fasilitas_kamar_2']) && is_array($_POST['fasilitas_kamar_2'])) {
            foreach (array_slice($_POST['fasilitas_kamar_2'], 0, 7) as $id_fasilitas) {
                $id_fac = intval($id_fasilitas);
                $sf = $koneksi->prepare("INSERT INTO kamar_fasilitas (id_kamar, id_fasilitas) VALUES (?, ?)");
                $sf->bind_param("ii", $id_kamar2, $id_fac);
                $sf->execute();
            }
        }

        echo "<script>alert('Hotel dan pilihan kamar berhasil disimpan.'); window.location='kelola_hotel.php';</script>";
    }
}

/* ============================================================
   DAFTAR HOTEL (dengan pencarian)
   ============================================================ */
$pencarian_admin = isset($_GET['cari_admin']) ? trim($_GET['cari_admin']) : '';

if ($pencarian_admin !== '') {
    $query_list = "SELECT h.*, MIN(k.harga_per_malam) AS harga_terendah
                   FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                   WHERE h.nama_hotel LIKE ?
                   GROUP BY h.id_hotel ORDER BY h.id_hotel DESC";
    $stmt_list = $koneksi->prepare($query_list);
    $kw = "%" . $pencarian_admin . "%";
    $stmt_list->bind_param("s", $kw);
    $stmt_list->execute();
    $list_hotel = $stmt_list->get_result();
} else {
    $query_list = "SELECT h.*, MIN(k.harga_per_malam) AS harga_terendah
                   FROM hotel h LEFT JOIN kamar k ON h.id_hotel = k.id_hotel
                   GROUP BY h.id_hotel ORDER BY h.id_hotel DESC";
    $list_hotel = $koneksi->query($query_list);
}

/* ============================================================
   STATISTIK PENJUALAN HARI INI
   ============================================================ */
$query_stats = "SELECT h.id_hotel, h.nama_hotel,
                    COUNT(DISTINCT b.id_pemesanan) as jumlah_pesanan,
                    SUM(CASE WHEN p.status_pembayaran = 'lunas' THEN 1 ELSE 0 END) as pesanan_lunas
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
$hotel_names    = json_encode(array_map(fn($i) => $i['nama_hotel'], $stats_data));
$pesanan_counts = json_encode(array_map(fn($i) => $i['jumlah_pesanan'], $stats_data));

/* ============================================================
   AMBIL DAFTAR BANNER
   ============================================================ */
$query_banner_list = "SELECT * FROM banner ORDER BY urutan ASC, id_banner ASC";
$hasil_banner_list = $koneksi->query($query_banner_list);
$banner_admin_list = [];
if ($hasil_banner_list) {
    while ($b = $hasil_banner_list->fetch_assoc()) {
        $banner_admin_list[] = $b;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Control Admin</title>
    <?php include '../komponen/style.php'; ?>
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
                    <div class="form-group">
                        <label>Hotel Bintang</label>
                        <select name="rating" required>
                            <option value="" disabled selected>-- Pilih Bintang Hotel --</option>
                            <option value="1">1 Bintang</option>
                            <option value="2">2 Bintang</option>
                            <option value="3">3 Bintang</option>
                            <option value="4">4 Bintang</option>
                            <option value="5">5 Bintang</option>
                        </select>
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
                    <div class="form-group">
                        <label>Fasilitas</label>
                        <div
                            style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px;">
                            <?php foreach ($fasilitas_list as $fac): ?>
                            <label
                                style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-weight: 400;">
                                <input type="checkbox" name="fasilitas_kamar_1[]" value="<?= $fac['id_fasilitas']; ?>"
                                    style="cursor: pointer;">
                                <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small style="color: #94a3b8; font-size: 0.75rem; margin-top: 4px; display: block;">Pilih
                            maksimal 7 fasilitas</small>
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
                    <div class="form-group">
                        <label>Fasilitas</label>
                        <div
                            style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px;">
                            <?php foreach ($fasilitas_list as $fac): ?>
                            <label
                                style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-weight: 400;">
                                <input type="checkbox" name="fasilitas_kamar_2[]" value="<?= $fac['id_fasilitas']; ?>"
                                    style="cursor: pointer;">
                                <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small style="color: #94a3b8; font-size: 0.75rem; margin-top: 4px; display: block;">Pilih
                            maksimal 7 fasilitas</small>
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
                                        $img  = $row['foto'];
                                        $path = (empty($img) || !file_exists("../assets/".$img))
                                            ? "https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=80&q=80"
                                            : "/reservasi_hotel/assets/".$img;
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

            <section class="banner-admin-section">
                <h2 class="banner-admin-title">🖼️ Kelola Banner Halaman Utama</h2>

                <?php if (!empty($banner_admin_list)): ?>
                <div class="banner-admin-grid">
                    <?php foreach ($banner_admin_list as $b): ?>
                    <div class="banner-admin-card">
                        <img src="/reservasi_hotel/assets/banner/<?= htmlspecialchars($b['nama_file']); ?>" alt="">
                        <div class="banner-admin-card-body">
                            <span title="<?= htmlspecialchars($b['judul'] ?? '-'); ?>">
                                <?= htmlspecialchars($b['judul'] ?: '-'); ?>
                            </span>
                            <a href="kelola_hotel.php?aksi=hapus_banner&id_banner=<?= $b['id_banner']; ?>"
                                onclick="return confirm('Hapus banner ini?');" class="banner-del-btn">Hapus</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="banner-empty-admin">Belum ada banner. Tambahkan banner di bawah.</p>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="banner-upload-box">
                    <strong style="display:block; margin-bottom:6px; color:#334155;">Upload Banner Baru</strong>
                    <span style="font-size:0.82rem; color:#94a3b8;">Format: JPG, PNG, WEBP · Disarankan rasio 16:5 atau
                        3:1</span>
                    <div class="form-group" style="margin-top:14px; margin-bottom:8px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569;">Judul Banner <span
                                style="font-weight:400; color:#94a3b8;">(opsional)</span></label>
                        <input type="text" name="judul_banner" placeholder="Promo Akhir Tahun" style="margin-top:4px;">
                    </div>
                    <div class="form-group" style="margin-bottom:8px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569;">Urutan Tampil</label>
                        <input type="number" name="urutan_banner" value="<?= count($banner_admin_list) + 1; ?>" min="1"
                            style="margin-top:4px; width:80px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569;">Pilih Foto</label>
                        <input type="file" name="foto_banner" accept="image/*" required style="margin-top:4px;">
                    </div>
                    <button type="submit" name="tambah_banner" class="btn-upload-banner">Upload Banner</button>
                </form>
            </section>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    /* Auto-search admin */
    const adminSearchInput = document.getElementById('adminSearchInput');
    const adminSearchForm = document.getElementById('adminSearchForm');
    let searchTimer;
    adminSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => adminSearchForm.submit(), 300);
    });
    window.addEventListener('DOMContentLoaded', () => {
        const v = adminSearchInput.value;
        if (v !== '') {
            adminSearchInput.value = '';
            adminSearchInput.focus();
            adminSearchInput.value = v;
        }
    });

    /* Chart statistik */
    <?php if (!empty($stats_data)): ?>
    const chartCanvas = document.getElementById('salesChart');
    if (chartCanvas) {
        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: <?= $hotel_names; ?>,
                datasets: [{
                    label: 'Jumlah Pesanan Hari Ini',
                    data: <?= $pesanan_counts; ?>,
                    backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe', '#43e97b',
                        '#fa709a', '#fee140', '#30cfd0'
                    ],
                    borderColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#00f2fe', '#43e97b',
                        '#fa709a', '#fee140', '#30cfd0'
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
                            }
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