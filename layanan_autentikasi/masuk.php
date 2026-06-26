<?php
// logic proses masuk ke dalam sistem
session_start();
$koneksi = new mysqli("localhost", "root", "", "reservasi_hotel");
$pesan_error = "";
$pesan_sukses = "";
$is_ajax = isset($_POST['mode']); // Deteksi jika request dari AJAX
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login'; // Default mode adalah login

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil mode dari POST atau GET, default adalah login
    $mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($_GET['mode']) ? $_GET['mode'] : 'login');
    
    if ($mode === 'daftar') {
        // LOGIC PENDAFTARAN AKUN BARU
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $konfirmasi_password = trim($_POST['konfirmasi_password']);
        $peran = 'user'; // Default peran adalah user

        // Validasi input
        if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi_password)) {
            $pesan_error = "Semua field harus diisi.";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $pesan_error]);
                exit();
            }
        } elseif ($password !== $konfirmasi_password) {
            $pesan_error = "Password tidak cocok.";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $pesan_error]);
                exit();
            }
        } elseif (strlen($password) < 6) {
            $pesan_error = "Password minimal 6 karakter.";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $pesan_error]);
                exit();
            }
        } else {
            // Cek apakah email sudah terdaftar
            $stmt_cek = $koneksi->prepare("SELECT * FROM pengguna WHERE email = ?");
            $stmt_cek->bind_param("s", $email);
            $stmt_cek->execute();
            $hasil_cek = $stmt_cek->get_result();

            if ($hasil_cek->num_rows > 0) {
                $pesan_error = "Email sudah terdaftar. Gunakan email lain.";
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $pesan_error]);
                    exit();
                }
            } else {
                // Insert akun baru ke database
                $stmt_insert = $koneksi->prepare("INSERT INTO pengguna (nama, email, password, peran) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $nama, $email, $password, $peran);

                if ($stmt_insert->execute()) {
                    $pesan_sukses = "Akun berhasil dibuat! Silakan login dengan email dan password Anda.";
                    
                    // Jika AJAX request, return JSON
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $pesan_sukses]);
                        exit();
                    }
                    
                    // Reset form
                    $_POST = [];
                    // Ubah mode ke login setelah sukses daftar
                    $mode = 'login';
                } else {
                    $pesan_error = "Terjadi kesalahan saat membuat akun. Coba lagi.";
                    
                    // Jika AJAX request, return JSON
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $pesan_error]);
                        exit();
                    }
                }
            }
        }
    } else {
        // LOGIC MASUK KE SISTEM
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // untuk query mencari pengguna berdasarkan email
        $stmt = $koneksi->prepare("SELECT * FROM pengguna WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $hasil = $stmt->get_result();

        if ($hasil->num_rows > 0) {
            $user = $hasil->fetch_assoc();
            
            // untuk mencocokkan password yang dimasukkan dengan yang ada di database
            if ($password === $user['password']) {
                $_SESSION['id_pengguna'] = $user['id_pengguna'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['peran'] = $user['peran'];
                
                // Jika AJAX request, return JSON
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Login berhasil']);
                    exit();
                }
                
                header("Location: /reservasi_hotel/index.php");
                exit();
            } else {
                $pesan_error = "Kredensial tidak cocok.";
                
                // Jika AJAX request, return JSON
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $pesan_error]);
                    exit();
                }
            }
        } else {
            $pesan_error = "Kredensial tidak cocok.";
            
            // Jika AJAX request, return JSON
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $pesan_error]);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Akun</title>

    <!-- Panggilan CSS Utama Navigasi & Halaman Masuk Eksternal -->
    <link rel="stylesheet" href="/reservasi_hotel/css/style_navigasi.css">
    <link rel="stylesheet" href="/reservasi_hotel/css/style_masuk.css">
</head>

<body>
    <?php include_once '../komponen/navigasi.php'; ?>

    <main>
        <section class="wrapper">
            <h2 class="form-title" id="form-main-title"><?php echo $mode === 'daftar' ? 'Daftar Akun' : 'Login'; ?></h2>

            <?php if($pesan_sukses): ?>
            <div class="alert-success"><?= $pesan_sukses; ?></div>
            <?php endif; ?>

            <?php if($pesan_error): ?>
            <div class="alert"><?= $pesan_error; ?></div>
            <?php endif; ?>

            <!-- TAB LOGIN -->
            <div class="tab-content <?php echo $mode === 'login' ? 'active' : ''; ?>" id="tab-login">
                <form action="?mode=login" method="POST">
                    <div class="form-group">
                        <label for="email-login">Email</label>
                        <input type="email" id="email-login" name="email" required placeholder="Masukkan email">
                    </div>
                    <div class="form-group">
                        <label for="password-login">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password-login" name="password" required
                                placeholder="Masukkan password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password-login')">
                                <svg id="eye-icon-login" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Masuk</button>
                </form>
                <div class="toggle-text">
                    Belum punya akun? <a onclick="switchTab('daftar')">Daftar di sini</a>
                </div>
            </div>

            <!-- TAB DAFTAR -->
            <div class="tab-content <?php echo $mode === 'daftar' ? 'active' : ''; ?>" id="tab-daftar">
                <form action="?mode=daftar" method="POST">
                    <div class="form-group">
                        <label for="nama">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required placeholder="Masukkan nama lengkap"
                            value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email-daftar">Email</label>
                        <input type="email" id="email-daftar" name="email" required placeholder="Masukkan email"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password-daftar">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password-daftar" name="password" required
                                placeholder="Minimal 6 karakter">
                            <button type="button" class="toggle-password" onclick="togglePassword('password-daftar')">
                                <svg id="eye-icon-daftar" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi-password">Konfirmasi Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="konfirmasi-password" name="konfirmasi_password" required
                                placeholder="Ulangi password">
                            <button type="button" class="toggle-password"
                                onclick="togglePassword('konfirmasi-password')">
                                <svg id="eye-icon-konfirmasi" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Daftar</button>
                </form>
                <div class="toggle-text">
                    Sudah punya akun? <a onclick="switchTab('login')">Login di sini</a>
                </div>
            </div>
        </section>
    </main>

    <script>
    // Perbaikan Otomatis Struktur Navigasi saat Halaman Dimuat
    document.addEventListener('DOMContentLoaded', () => {
        // 1. Memperbaiki link gambar logo yang pecah
        const logoImg = document.querySelector('.brand-logo img');
        if (logoImg) {
            logoImg.src = '/reservasi_hotel/assets/logo/logo.png';
        }

        // 2. Set tampilan awal tombol navigasi berdasarkan mode PHP saat ini
        const initialMode = "<?= $mode ?>";
        updateNavButtons(initialMode);
    });

    // Fungsi untuk menyembunyikan/menampilkan tombol di navbar secara dinamis
    function updateNavButtons(mode) {
        const btnLoginNav = document.querySelector('button[onclick*="login"]') || document.querySelector('.btn-login');
        const btnDaftarNav = document.querySelector('button[onclick*="daftar"]') || document.querySelector(
            '.btn-register');

        if (mode === 'daftar') {
            if (btnDaftarNav) btnDaftarNav.style.display = 'none';
            if (btnLoginNav) btnLoginNav.style.display = 'inline-block';
        } else {
            if (btnLoginNav) btnLoginNav.style.display = 'none';
            if (btnDaftarNav) btnDaftarNav.style.display = 'inline-block';
        }
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    }

    function switchTab(tab) {
        document.getElementById('tab-login').classList.remove('active');
        document.getElementById('tab-daftar').classList.remove('active');

        if (tab === 'login') {
            document.getElementById('tab-login').classList.add('active');
            document.getElementById('form-main-title').innerText = 'Login';
            window.history.replaceState({}, document.title, '?mode=login');
            updateNavButtons('login');
        } else if (tab === 'daftar') {
            document.getElementById('tab-daftar').classList.add('active');
            document.getElementById('form-main-title').innerText = 'Daftar Akun';
            window.history.replaceState({}, document.title, '?mode=daftar');
            updateNavButtons('daftar');
        }
    }

    function openLoginModal(type) {
        switchTab(type);
    }
    </script>
</body>

</html>