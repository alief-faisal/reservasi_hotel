<?php
session_start();
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");
$id_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data hotel
$stmt_hotel = $koneksi->prepare("SELECT * FROM hotel WHERE id_hotel = ?");
$stmt_hotel->bind_param("i", $id_edit);
$stmt_hotel->execute();
$data_hotel = $stmt_hotel->get_result()->fetch_assoc();

// Ambil semua kamar untuk hotel ini
$stmt_kamar = $koneksi->prepare("SELECT * FROM kamar WHERE id_hotel = ? ORDER BY tipe_kamar");
$stmt_kamar->bind_param("i", $id_edit);
$stmt_kamar->execute();
$hasil_kamar = $stmt_kamar->get_result();
$kamar_list = [];
while($k = $hasil_kamar->fetch_assoc()) {
    $kamar_list[] = $k;
}

// Ambil data fasilitas
$query_fasilitas = "SELECT * FROM fasilitas ORDER BY id_fasilitas ASC";
$hasil_fasilitas = $koneksi->query($query_fasilitas);
$fasilitas_list = [];
while ($fac = $hasil_fasilitas->fetch_assoc()) {
    $fasilitas_list[] = $fac;
}

// Ambil fasilitas untuk setiap kamar
$fasilitas_per_kamar = [];
foreach($kamar_list as $kamar) {
    $stmt_fac = $koneksi->prepare("SELECT id_fasilitas FROM kamar_fasilitas WHERE id_kamar = ?");
    $stmt_fac->bind_param("i", $kamar['id_kamar']);
    $stmt_fac->execute();
    $result_fac = $stmt_fac->get_result();
    $ids = [];
    while($row = $result_fac->fetch_assoc()) {
        $ids[] = $row['id_fasilitas'];
    }
    $fasilitas_per_kamar[$kamar['id_kamar']] = $ids;
}

if (!$data_hotel) {
    echo "<script>alert('Data tidak ditemukan.'); window.location='kelola_hotel.php';</script>";
    exit();
}

// Proses Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama      = $_POST['nama_hotel'];
    $deskripsi = $_POST['deskripsi'];
    $lokasi    = $_POST['lokasi'];
    $rating    = intval($_POST['rating']);
    $latitude  = !empty($_POST['latitude'])  ? floatval($_POST['latitude'])  : null;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $nama_file_final = $data_hotel['foto'];

    if (isset($_FILES['foto_hotel']) && $_FILES['foto_hotel']['error'] === 0) {
        $nama_file_asal = $_FILES['foto_hotel']['name'];
        $tmp_file       = $_FILES['foto_hotel']['tmp_name'];
        $ekstensi       = strtolower(pathinfo($nama_file_asal, PATHINFO_EXTENSION));
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            if ($data_hotel['foto'] && $data_hotel['foto'] !== 'default.jpg') {
                @unlink("../assets/" . $data_hotel['foto']);
            }
            $nama_file_final = time() . "_" . bin2hex(random_bytes(4)) . "." . $ekstensi;
            move_uploaded_file($tmp_file, "../assets/" . $nama_file_final);
        }
    }

    // Update data hotel termasuk koordinat
    $stmt_up = $koneksi->prepare("UPDATE hotel SET nama_hotel = ?, deskripsi = ?, lokasi = ?, foto = ?, rating = ?, latitude = ?, longitude = ? WHERE id_hotel = ?");
    $stmt_up->bind_param("ssssiidi", $nama, $deskripsi, $lokasi, $nama_file_final, $rating, $latitude, $longitude, $id_edit);

    if ($stmt_up->execute()) {
        foreach($kamar_list as $idx => $kamar) {
            $harga_baru  = floatval($_POST["harga_kamar_$idx"] ?? 0);
            $diskon_baru = intval($_POST["diskon_kamar_$idx"] ?? 0);

            $stmt_km = $koneksi->prepare("UPDATE kamar SET harga_per_malam = ?, diskon_persen = ? WHERE id_kamar = ?");
            $stmt_km->bind_param("dii", $harga_baru, $diskon_baru, $kamar['id_kamar']);
            $stmt_km->execute();

            $stmt_del_fac = $koneksi->prepare("DELETE FROM kamar_fasilitas WHERE id_kamar = ?");
            $stmt_del_fac->bind_param("i", $kamar['id_kamar']);
            $stmt_del_fac->execute();

            if (isset($_POST["fasilitas_kamar_{$kamar['id_kamar']}"])) {
                $fasilitas_baru = $_POST["fasilitas_kamar_{$kamar['id_kamar']}"];
                foreach (array_slice($fasilitas_baru, 0, 7) as $id_fasilitas) {
                    $id_fac = intval($id_fasilitas);
                    $stmt_insert_fac = $koneksi->prepare("INSERT INTO kamar_fasilitas (id_kamar, id_fasilitas) VALUES (?, ?)");
                    $stmt_insert_fac->bind_param("ii", $kamar['id_kamar'], $id_fac);
                    $stmt_insert_fac->execute();
                }
            }
        }

        echo "<script>alert('Hotel berhasil diperbarui.'); window.location='kelola_hotel.php';</script>";
    }
}

// Nilai koordinat saat ini (untuk pre-fill)
$current_lat = !empty($data_hotel['latitude'])  ? floatval($data_hotel['latitude'])  : '';
$current_lng = !empty($data_hotel['longitude']) ? floatval($data_hotel['longitude']) : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hotel - <?= htmlspecialchars($data_hotel['nama_hotel']); ?></title>

    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_edit.css">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
    /* ===== KOORDINAT SECTION ===== */
    .koordinat-group {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 20px;
    }

    .koordinat-group .koord-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 0.9rem;
        margin-bottom: 12px;
        display: block;
    }

    .koordinat-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .koordinat-row label {
        font-size: 0.78rem;
        color: #475569;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .koordinat-row input {
        width: 100%;
        padding: 9px 11px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.85rem;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.15s;
    }

    .koordinat-row input:focus {
        border-color: #dc2626;
    }

    .btn-preview-map {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #2563eb;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
        margin-bottom: 12px;
    }

    .btn-preview-map:hover {
        background: #1d4ed8;
    }

    #edit-map-container {
        display: none;
        height: 220px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
    }

    #edit-preview-map {
        height: 100%;
    }

    /* status badge koordinat */
    .koord-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 12px;
    }

    .koord-status.ada {
        background: #dcfce7;
        color: #166534;
    }

    .koord-status.belum {
        background: #fef9c3;
        color: #713f12;
    }

    .koord-hint {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-top: 8px;
        line-height: 1.5;
    }
    </style>
</head>

<body>
    <?php include '../komponen/navigasi.php'; ?>

    <main class="edit-box">
        <h2 class="title">Edit Data Hotel</h2>
        <form action="" method="POST" enctype="multipart/form-data">

            <div class="form-ctrl">
                <label>Nama Hotel</label>
                <input type="text" name="nama_hotel" value="<?= htmlspecialchars($data_hotel['nama_hotel']); ?>"
                    required>
            </div>
            <div class="form-ctrl">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3"
                    required><?= htmlspecialchars($data_hotel['deskripsi']); ?></textarea>
            </div>
            <div class="form-ctrl">
                <label>Wilayah Banten</label>
                <select name="lokasi" required>
                    <?php
                    $wilayah = ["Kota Serang","Kota Tangerang","Kota Tangerang Selatan","Kota Cilegon","Kabupaten Serang","Kabupaten Tangerang","Kabupaten Pandeglang","Kabupaten Lebak"];
                    foreach($wilayah as $w) {
                        $selected = ($data_hotel['lokasi'] === $w) ? 'selected' : '';
                        echo "<option value='$w' $selected>$w</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-ctrl">
                <label>Ganti Foto</label>
                <input type="file" name="foto_hotel" accept="image/*">
            </div>
            <div class="form-ctrl">
                <label>Rating Bintang ⭐</label>
                <select name="rating" required>
                    <?php for($b = 1; $b <= 5; $b++): ?>
                    <option value="<?= $b; ?>" <?= ($data_hotel['rating'] == $b) ? 'selected' : ''; ?>>
                        <?= $b; ?> Bintang
                    </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- ===== KOORDINAT LOKASI ===== -->
            <div class="koordinat-group">
                <span class="koord-title">📍 Koordinat Lokasi (untuk peta di halaman hotel)</span>

                <?php if ($current_lat !== '' && $current_lng !== ''): ?>
                <div class="koord-status ada">✅ Koordinat sudah tersimpan</div>
                <?php else: ?>
                <div class="koord-status belum">⚠️ Koordinat belum diisi</div>
                <?php endif; ?>

                <div class="koordinat-row">
                    <div>
                        <label>Latitude</label>
                        <input type="number" name="latitude" id="editLatitude" value="<?= $current_lat; ?>"
                            placeholder="-6.1234567" step="0.0000001" min="-90" max="90">
                    </div>
                    <div>
                        <label>Longitude</label>
                        <input type="number" name="longitude" id="editLongitude" value="<?= $current_lng; ?>"
                            placeholder="106.1234567" step="0.0000001" min="-180" max="180">
                    </div>
                </div>

                <button type="button" class="btn-preview-map" onclick="previewEditMap()">
                    🗺️ Preview / Cek Lokasi di Peta
                </button>

                <div class="koord-hint">
                    💡 Cara cari koordinat: buka <a href="https://maps.google.com" target="_blank">Google Maps</a>
                    → klik kanan lokasi hotel → klik angka koordinat untuk menyalin.<br>
                    Atau klik langsung pada peta preview di bawah untuk mengisi koordinat otomatis.
                </div>

                <div id="edit-map-container">
                    <div id="edit-preview-map"></div>
                </div>
            </div>
            <!-- ===== END KOORDINAT ===== -->

            <hr class="form-divider">
            <h3 class="sub-title">Data Kamar</h3>

            <?php foreach($kamar_list as $idx => $kamar): ?>
            <div class="kamar-card">
                <div class="kamar-title">
                    <?= htmlspecialchars($kamar['nama_kamar']); ?> (<?= htmlspecialchars($kamar['tipe_kamar']); ?>)
                </div>
                <div class="form-ctrl">
                    <label>Harga Per Malam (Rp)</label>
                    <input type="number" name="harga_kamar_<?= $idx; ?>"
                        value="<?= intval($kamar['harga_per_malam']); ?>" required>
                </div>
                <div class="form-ctrl">
                    <label>Diskon (%)</label>
                    <input type="number" name="diskon_kamar_<?= $idx; ?>" placeholder="0" min="0" max="100"
                        value="<?= intval($kamar['diskon_persen'] ?? 0); ?>">
                </div>
                <div class="form-ctrl">
                    <label>Fasilitas</label>
                    <div class="fasilitas-container">
                        <?php foreach ($fasilitas_list as $fac):
                            $checked = in_array($fac['id_fasilitas'], $fasilitas_per_kamar[$kamar['id_kamar']] ?? []) ? 'checked' : '';
                        ?>
                        <label class="fasilitas-label">
                            <input type="checkbox" name="fasilitas_kamar_<?= $kamar['id_kamar']; ?>[]"
                                value="<?= $fac['id_fasilitas']; ?>" <?= $checked; ?>>
                            <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-help">Pilih maksimal 7 fasilitas</small>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
            <a href="kelola_hotel.php" class="btn-cancel">Batal & Kembali</a>
        </form>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) logoImg.src = '/reservasi_hotel/assets/logo/logo.png';

        // Jika sudah ada koordinat, langsung tampilkan peta
        const lat = parseFloat(document.getElementById('editLatitude').value);
        const lng = parseFloat(document.getElementById('editLongitude').value);
        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
            previewEditMap();
        }
    });

    let editMap = null;
    let editMarker = null;
    let mapClickAttached = false;

    function previewEditMap() {
        const lat = parseFloat(document.getElementById('editLatitude').value);
        const lng = parseFloat(document.getElementById('editLongitude').value);

        // Jika kosong, gunakan pusat Banten sebagai default
        const centerLat = (!isNaN(lat) && lat !== 0) ? lat : -6.4058;
        const centerLng = (!isNaN(lng) && lng !== 0) ? lng : 106.0640;

        const container = document.getElementById('edit-map-container');
        container.style.display = 'block';

        if (!editMap) {
            editMap = L.map('edit-preview-map').setView([centerLat, centerLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(editMap);
        } else {
            editMap.setView([centerLat, centerLng], 15);
        }

        // Pasang / pindahkan marker
        if (!isNaN(lat) && lat !== 0 && !isNaN(lng) && lng !== 0) {
            if (editMarker) {
                editMarker.setLatLng([lat, lng]);
            } else {
                editMarker = L.marker([lat, lng]).addTo(editMap);
            }
            editMarker.bindPopup('📍 <?= addslashes(htmlspecialchars($data_hotel['nama_hotel'])); ?>').openPopup();
        }

        // Klik peta → isi input koordinat otomatis (attach sekali)
        if (!mapClickAttached) {
            editMap.on('click', function(e) {
                const {
                    lat,
                    lng
                } = e.latlng;
                document.getElementById('editLatitude').value = lat.toFixed(7);
                document.getElementById('editLongitude').value = lng.toFixed(7);

                if (editMarker) {
                    editMarker.setLatLng([lat, lng]);
                } else {
                    editMarker = L.marker([lat, lng]).addTo(editMap);
                }
                editMarker.bindPopup('📍 Lokasi dipilih').openPopup();
            });
            mapClickAttached = true;
        }

        setTimeout(() => editMap.invalidateSize(), 150);
    }
    </script>
</body>

</html>