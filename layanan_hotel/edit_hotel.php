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
    $nama = $_POST['nama_hotel'];
    $deskripsi = $_POST['deskripsi'];
    $lokasi = $_POST['lokasi'];
    $rating = intval($_POST['rating']);
    $nama_file_final = $data_hotel['foto'];

    if (isset($_FILES['foto_hotel']) && $_FILES['foto_hotel']['error'] === 0) {
        $nama_file_asal = $_FILES['foto_hotel']['name'];
        $tmp_file = $_FILES['foto_hotel']['tmp_name'];
        $ekstensi = strtolower(pathinfo($nama_file_asal, PATHINFO_EXTENSION));
        
        if (in_array($ekstensi, ['jpg', 'jpeg', 'png'])) {
            if ($data_hotel['foto'] && $data_hotel['foto'] !== 'default.jpg') {
                @unlink("../assets/" . $data_hotel['foto']);
            }
            $nama_file_final = time() . "_" . bin2hex(random_bytes(4)) . "." . $ekstensi;
            move_uploaded_file($tmp_file, "../assets/" . $nama_file_final);
        }
    }

    // Update data hotel
    $stmt_up = $koneksi->prepare("UPDATE hotel SET nama_hotel = ?, deskripsi = ?, lokasi = ?, foto = ?, rating = ? WHERE id_hotel = ?");
    $stmt_up->bind_param("ssssii", $nama, $deskripsi, $lokasi, $nama_file_final, $rating, $id_edit);
    
    if ($stmt_up->execute()) {
        // Update semua kamar
        foreach($kamar_list as $idx => $kamar) {
            $harga_baru = floatval($_POST["harga_kamar_$idx"] ?? 0);
            $diskon_baru = intval($_POST["diskon_kamar_$idx"] ?? 0);
            
            $stmt_km = $koneksi->prepare("UPDATE kamar SET harga_per_malam = ?, diskon_persen = ? WHERE id_kamar = ?");
            $stmt_km->bind_param("dii", $harga_baru, $diskon_baru, $kamar['id_kamar']);
            $stmt_km->execute();

            // Update fasilitas kamar dan hapus fasilitas lama
            $stmt_del_fac = $koneksi->prepare("DELETE FROM kamar_fasilitas WHERE id_kamar = ?");
            $stmt_del_fac->bind_param("i", $kamar['id_kamar']);
            $stmt_del_fac->execute();

            // Tambah fasilitas baru
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hotel</title>
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

    .edit-box {
        max-width: 460px;
        margin: 60px auto;
        background: white;
        padding: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 24px;
    }

    .form-ctrl {
        margin-bottom: 16px;
    }

    .form-ctrl label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 6px;
        color: #475569;
    }

    .form-ctrl input,
    .form-ctrl textarea,
    .form-ctrl select {
        width: 100%;
        padding: 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.9rem;
        background: white;
    }

    .btn-save {
        background: #0f172a;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        width: 100%;
        margin-top: 10px;
    }

    .btn-cancel {
        display: block;
        text-align: center;
        text-decoration: none;
        color: #475569;
        font-size: 0.88rem;
        margin-top: 15px;
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
                    $wilayah = ["Kota Serang", "Kota Tangerang", "Kota Tangerang Selatan", "Kota Cilegon", "Kabupaten Serang", "Kabupaten Tangerang", "Kabupaten Pandeglang", "Kabupaten Lebak"];
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
                    <option value="1" <?= ($data_hotel['rating'] == 1) ? 'selected' : ''; ?>>1 Bintang</option>
                    <option value="2" <?= ($data_hotel['rating'] == 2) ? 'selected' : ''; ?>>2 Bintang</option>
                    <option value="3" <?= ($data_hotel['rating'] == 3) ? 'selected' : ''; ?>>3 Bintang</option>
                    <option value="4" <?= ($data_hotel['rating'] == 4) ? 'selected' : ''; ?>>4 Bintang</option>
                    <option value="5" <?= ($data_hotel['rating'] == 5) ? 'selected' : ''; ?>>5 Bintang</option>
                </select>
            </div>

            <hr style="margin: 20px 0; border: none; border-top: 1px dashed #cbd5e1;">
            <h3 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 15px; color: #334155;">Data Kamar</h3>

            <?php foreach($kamar_list as $idx => $kamar): ?>
            <div style="background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 12px;">
                <div style="font-weight: 600; color: #0f172a; margin-bottom: 8px;">
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
                    <label>Fasilitas </label>
                    <div
                        style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <?php foreach ($fasilitas_list as $fac): 
                            $checked = in_array($fac['id_fasilitas'], $fasilitas_per_kamar[$kamar['id_kamar']] ?? []) ? 'checked' : '';
                        ?>
                        <label
                            style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0; font-weight: 400;">
                            <input type="checkbox" name="fasilitas_kamar_<?= $kamar['id_kamar']; ?>[]"
                                value="<?= $fac['id_fasilitas']; ?>" <?= $checked; ?> style="cursor: pointer;">
                            <span><?= htmlspecialchars($fac['nama_fasilitas']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small style="color: #94a3b8; font-size: 0.75rem; margin-top: 4px; display: block;">Pilih maksimal 7
                        fasilitas</small>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
            <a href="kelola_hotel.php" class="btn-cancel">Batal & Kembali</a>
        </form>
    </main>
</body>

</html>