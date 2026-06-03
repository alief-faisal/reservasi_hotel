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

if (!$data_hotel) {
    echo "<script>alert('Data tidak ditemukan.'); window.location='kelola_hotel.php';</script>";
    exit();
}

// Proses Eksekusi Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_hotel'];
    $deskripsi = $_POST['deskripsi'];
    $lokasi = $_POST['lokasi'];
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
    $stmt_up = $koneksi->prepare("UPDATE hotel SET nama_hotel = ?, deskripsi = ?, lokasi = ?, foto = ? WHERE id_hotel = ?");
    $stmt_up->bind_param("ssssi", $nama, $deskripsi, $lokasi, $nama_file_final, $id_edit);
    
    if ($stmt_up->execute()) {
        // Update semua kamar
        foreach($kamar_list as $idx => $kamar) {
            $harga_baru = floatval($_POST["harga_kamar_$idx"] ?? 0);
            $diskon_baru = intval($_POST["diskon_kamar_$idx"] ?? 0);
            
            $stmt_km = $koneksi->prepare("UPDATE kamar SET harga_per_malam = ?, diskon_persen = ? WHERE id_kamar = ?");
            $stmt_km->bind_param("dii", $harga_baru, $diskon_baru, $kamar['id_kamar']);
            $stmt_km->execute();
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
                <label>Ganti Foto (Biarkan kosong jika tidak ingin diubah)</label>
                <input type="file" name="foto_hotel" accept="image/*">
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
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
            <a href="kelola_hotel.php" class="btn-cancel">Batal & Kembali</a>
        </form>
    </main>
</body>

</html>