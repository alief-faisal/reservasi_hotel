<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");

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
    $nama      = $_POST['nama_hotel'];
    $deskripsi = $_POST['deskripsi'];
    $lokasi    = $_POST['lokasi'];
    $rating    = intval($_POST['rating']);
    $latitude  = !empty($_POST['latitude'])  ? floatval($_POST['latitude'])  : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
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

    /* Simpan hotel — pastikan kolom latitude & longitude sudah ada di tabel hotel:
       ALTER TABLE hotel ADD COLUMN latitude DECIMAL(10,7) NULL;
       ALTER TABLE hotel ADD COLUMN longitude DECIMAL(10,7) NULL;
    */
    $stmt = $koneksi->prepare("INSERT INTO hotel (nama_hotel, deskripsi, lokasi, foto, rating, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssidd", $nama, $deskripsi, $lokasi, $nama_file_final, $rating, $latitude, $longitude);

    if ($stmt->execute()) {
        $id_hotel_baru = $koneksi->insert_id;

        /* Kamar 1 */
        $tipe_k1   = $_POST['tipe_kamar_1'];
        $nama_k1   = ($tipe_k1 === 'Standard') ? 'Room Standard' : 'Room Deluxe';
        $harga_k1  = doubleval($_POST['harga_kamar_1']);
        $stok_k1   = intval($_POST['stok_kamar_1']);
        $diskon_k1 = intval($_POST['diskon_kamar_1'] ?? 0);

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
        $tipe_k2   = $_POST['tipe_kamar_2'];
        $nama_k2   = ($tipe_k2 === 'Deluxe') ? 'Room Deluxe' : 'Room Standard';
        $harga_k2  = doubleval($_POST['harga_kamar_2']);
        $stok_k2   = intval($_POST['stok_kamar_2']);
        $diskon_k2 = intval($_POST['diskon_kamar_2'] ?? 0);

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

/* DAFTAR HOTEL (dengan pencarian) */
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hotel - Admin Panel</title>

    <?php include '../komponen/style.php'; ?>
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_kelola_hotel.css">

    <!-- Leaflet untuk preview peta saat input koordinat -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap"
        rel="stylesheet">
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

        <div class="dashboard-wrapper">

            <!-- Sidebar -->
            <aside class="admin-sidebar">
                <div class="sidebar-title">Menu Utama</div>
                <ul class="sidebar-menu">
                    <li><a href="kelola_hotel.php" class="active">🏨 Kelola Hotel</a></li>
                    <li><a href="banner.php">🖼️ Kelola Banner</a></li>
                    <li><a href="statistik.php">📊 Statistik Hari Ini</a></li>
                </ul>
            </aside>

            <!-- Konten Utama -->
            <div class="admin-main-content">
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
                                <textarea name="deskripsi" rows="2" required
                                    placeholder="Fasilitas singkat..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Wilayah Banten</label>
                                <select name="lokasi" required>
                                    <option value="" disabled selected> Pilih Wilayah </option>
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
                                    <option value="" disabled selected> Pilih Bintang Hotel </option>
                                    <option value="1">1 Bintang</option>
                                    <option value="2">2 Bintang</option>
                                    <option value="3">3 Bintang</option>
                                    <option value="4">4 Bintang</option>
                                    <option value="5">5 Bintang</option>
                                </select>
                            </div>

                            <!-- ===== KOORDINAT LOKASI ===== -->
                            <div class="koordinat-group">
                                <label class="koord-title">Koordinat Lokasi (untuk peta)</label>
                                <div class="koordinat-row">
                                    <div>
                                        <label
                                            style="font-size:0.8rem; color:#475569; margin-bottom:4px; display:block;">Latitude</label>
                                        <input type="number" name="latitude" id="inputLatitude" placeholder="-6.1234567"
                                            step="0.0000001" min="-90" max="90">
                                    </div>
                                    <div>
                                        <label
                                            style="font-size:0.8rem; color:#475569; margin-bottom:4px; display:block;">Longitude</label>
                                        <input type="number" name="longitude" id="inputLongitude"
                                            placeholder="106.1234567" step="0.0000001" min="-180" max="180">
                                    </div>
                                </div>
                                <button type="button" class="btn-preview-map" onclick="previewMap()">
                                    🗺️ Preview Peta
                                </button>
                                <div class="koord-hint">
                                    💡 Cari koordinat di <a href="https://maps.google.com" target="_blank">Google
                                        Maps</a>
                                    → klik kanan lokasi → salin koordinat. Contoh: -6.1174, 106.1526
                                </div>
                                <div id="preview-map-container">
                                    <div id="preview-map"></div>
                                </div>
                            </div>
                            <!-- ===== END KOORDINAT ===== -->

                            <div class="sub-section-title">Tipe Kamar 1</div>
                            <div class="form-group">
                                <label>Tipe Kamar</label>
                                <select name="tipe_kamar_1" required>
                                    <option value="Standard" selected>Standard Room</option>
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
                                        <input type="checkbox" name="fasilitas_kamar_1[]"
                                            value="<?= $fac['id_fasilitas']; ?>">
                                        <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="sub-section-title">Tipe Kamar 2</div>
                            <div class="form-group">
                                <label>Tipe Kamar</label>
                                <select name="tipe_kamar_2" required>
                                    <option value="Deluxe" selected>Deluxe Room</option>
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
                                        <input type="checkbox" name="fasilitas_kamar_2[]"
                                            value="<?= $fac['id_fasilitas']; ?>">
                                        <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
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
                                        <th>Koordinat</th>
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
                                        <td style="font-weight:500; color:#0f172a;">
                                            <?= htmlspecialchars($row['nama_hotel']); ?></td>
                                        <td><?= htmlspecialchars($row['lokasi']); ?></td>
                                        <td>Rp
                                            <?= $row['harga_terendah'] ? number_format($row['harga_terendah'], 0, ',', '.') : '-'; ?>
                                        </td>
                                        <td class="coord-cell">
                                            <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                                            <?= number_format($row['latitude'],6); ?>,<br>
                                            <?= number_format($row['longitude'],6); ?>
                                            <?php else: ?>
                                            <span style="color:#cbd5e1;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-links">
                                            <a href="edit_hotel.php?id=<?= $row['id_hotel']; ?>"
                                                class="link-edit">Edit</a>
                                            <a href="kelola_hotel.php?aksi=hapus&id_hotel=<?= $row['id_hotel']; ?>"
                                                class="link-del" onclick="return confirm('Hapus data ini?');">Hapus</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center; color:#64748b; padding: 40px 0;">Data
                                            hotel tidak ditemukan.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    /* Auto-search */
    const adminSearchInput = document.getElementById('adminSearchInput');
    const adminSearchForm = document.getElementById('adminSearchForm');
    let searchTimer;
    adminSearchInput.addEventListener('input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => adminSearchForm.submit(), 300);
    });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
    });
    </script>

    <!-- ===== PREVIEW PETA KOORDINAT ===== -->
    <script>
    let previewMapInstance = null;
    let previewMarker = null;

    function previewMap() {
        const lat = parseFloat(document.getElementById('inputLatitude').value);
        const lng = parseFloat(document.getElementById('inputLongitude').value);

        if (isNaN(lat) || isNaN(lng)) {
            alert('Masukkan latitude dan longitude yang valid terlebih dahulu.');
            return;
        }

        const container = document.getElementById('preview-map-container');
        container.style.display = 'block';

        if (!previewMapInstance) {
            previewMapInstance = L.map('preview-map').setView([lat, lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(previewMapInstance);
        } else {
            previewMapInstance.setView([lat, lng], 15);
        }

        if (previewMarker) {
            previewMarker.setLatLng([lat, lng]);
        } else {
            previewMarker = L.marker([lat, lng]).addTo(previewMapInstance);
        }
        previewMarker.bindPopup('📍 Lokasi Hotel').openPopup();

        /* Paksa render ulang peta (sering dibutuhkan saat container awalnya hidden) */
        setTimeout(() => previewMapInstance.invalidateSize(), 100);
    }

    /* Klik pada peta → isi input koordinat otomatis */
    document.addEventListener('DOMContentLoaded', () => {
        /* Inisialisasi setelah container pertama kali ditampilkan via previewMap() */
        /* Listener "click" diattach setelah peta dibuat, dihandle di previewMap */
    });

    /* Update koordinat lewat klik pada peta setelah dibuat */
    function attachMapClick() {
        if (previewMapInstance) {
            previewMapInstance.on('click', function(e) {
                const {
                    lat,
                    lng
                } = e.latlng;
                document.getElementById('inputLatitude').value = lat.toFixed(7);
                document.getElementById('inputLongitude').value = lng.toFixed(7);
                if (previewMarker) {
                    previewMarker.setLatLng([lat, lng]);
                } else {
                    previewMarker = L.marker([lat, lng]).addTo(previewMapInstance);
                }
                previewMarker.bindPopup('📍 Lokasi Hotel').openPopup();
            });
        }
    }

    /* Patching previewMap agar attach click setelah init */
    const _origPreviewMap = previewMap;
    window.previewMap = function() {
        _origPreviewMap();
        setTimeout(attachMapClick, 200);
    };
    </script>

</body>

</html>