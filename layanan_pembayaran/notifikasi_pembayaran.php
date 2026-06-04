<?php
// Fungsi untuk mengirim notifikasi email pembayaran
class NotifikasiPembayaran {
    
    public static function kirimNotifikasiSukses($data_pembayaran) {
        $subject = "Pembayaran Berhasil - Invoice #" . str_pad($data_pembayaran['id_pembayaran'], 6, '0', STR_PAD_LEFT);
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 4px; }
                .success-icon { font-size: 3rem; margin-bottom: 10px; }
                .content { margin: 20px 0; }
                .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .label { font-weight: bold; color: #333; }
                .value { color: #666; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
                .total { font-size: 1.5rem; color: #667eea; font-weight: bold; text-align: center; padding: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='success-icon'>✓</div>
                    <h1>Pembayaran Berhasil!</h1>
                </div>
                
                <div class='content'>
                    <p>Terima kasih telah melakukan pembayaran untuk pemesanan di " . htmlspecialchars($data_pembayaran['nama_hotel']) . "</p>
                    
                    <div class='info-row'>
                        <span class='label'>Nomor Invoice:</span>
                        <span class='value'>#" . str_pad($data_pembayaran['id_pembayaran'], 6, '0', STR_PAD_LEFT) . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Hotel:</span>
                        <span class='value'>" . htmlspecialchars($data_pembayaran['nama_hotel']) . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Kamar:</span>
                        <span class='value'>" . htmlspecialchars($data_pembayaran['nama_kamar']) . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Check-in:</span>
                        <span class='value'>" . date('d M Y', strtotime($data_pembayaran['tanggal_checkin'])) . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Check-out:</span>
                        <span class='value'>" . date('d M Y', strtotime($data_pembayaran['tanggal_checkout'])) . "</span>
                    </div>
                    
                    <div class='info-row'>
                        <span class='label'>Metode Pembayaran:</span>
                        <span class='value'>" . ucfirst(str_replace('_', ' ', $data_pembayaran['metode_pembayaran'])) . "</span>
                    </div>
                    
                    <div class='total'>Rp " . number_format($data_pembayaran['total_bayar'], 0, ',', '.') . "</div>
                    
                    <p style='color: #666; margin-top: 20px;'>Silakan lihat bukti pembayaran Anda di portal kami untuk informasi lebih detail.</p>
                </div>
                
                <div class='footer'>
                    <p>Ini adalah email otomatis, mohon tidak membalas email ini.</p>
                    <p>&copy; 2026 GrandStay Hotel Booking System. Semua hak dilindungi.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        
        // notifikasi dikirim
        self::catatNotifikasi('pembayaran_sukses', $data_pembayaran['id_pembayaran'], $data_pembayaran['email']);
        
        return true;
    }
    
    public static function kirimNotifikasiPending($data_pembayaran) {
        $subject = "Notifikasi Pembayaran Menunggu - Invoice #" . str_pad($data_pembayaran['id_pembayaran'], 6, '0', STR_PAD_LEFT);
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; text-align: center; border-radius: 4px; }
                .warning-icon { font-size: 3rem; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='warning-icon'>⏳</div>
                    <h1>Pembayaran Menunggu Konfirmasi</h1>
                </div>
                
                <div class='content'>
                    <p>Pemesanan Anda untuk " . htmlspecialchars($data_pembayaran['nama_hotel']) . " masih menunggu pembayaran.</p>
                    <p><strong>Silakan selesaikan pembayaran dalam 24 jam untuk mengkonfirmasi pemesanan Anda.</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        self::catatNotifikasi('pembayaran_pending', $data_pembayaran['id_pembayaran'], $data_pembayaran['email']);
        return true;
    }
    
    private static function catatNotifikasi($tipe, $id_pembayaran, $email) {
        return true;
    }
}