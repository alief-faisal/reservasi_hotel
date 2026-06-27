<?php
// Sembunyikan tombol love jika login sebagai admin
if (!isset($_SESSION['peran']) || $_SESSION['peran'] !== 'admin'):
// Variabel $id_hotel dan $loved_ids HARUS sudah di-set sebelum include ini
$is_loved = in_array($id_hotel, $loved_ids ?? []);
?>
<button class="btn-love <?= $is_loved ? 'loved' : ''; ?>" data-hotel-id="<?= intval($id_hotel); ?>"
    data-loved="<?= $is_loved ? '1' : '0'; ?>" onclick="toggleLove(event, this)"
    title="<?= $is_loved ? 'Hapus dari wishlist' : 'Simpan ke wishlist'; ?>"
    aria-label="<?= $is_loved ? 'Hapus dari wishlist' : 'Simpan ke wishlist'; ?>">
    <!-- SVG Heart Icon -->
    <svg class="love-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
    </svg>
</button>
<?php endif; ?>