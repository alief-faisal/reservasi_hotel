<?php
//untuk menghapus sesi otentikasi user dan logout dari server
session_start();
session_unset();
session_destroy();
header("Location: /reservasi_hotel/index.php");
exit();