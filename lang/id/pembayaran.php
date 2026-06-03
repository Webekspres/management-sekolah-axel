<?php

return [
    'status' => [
        'unpaid' => 'Belum dibayar',
        'pending' => 'Menunggu verifikasi',
        'paid' => 'Lunas',
        'failed' => 'Gagal — coba lagi',
    ],
    'method' => [
        'qris' => 'QRIS',
        'va_bni' => 'Virtual Account BNI',
        'va_bca' => 'Virtual Account BCA',
        'va_mandiri' => 'Virtual Account Mandiri',
        'transfer' => 'Transfer bank',
        'cash' => 'Bayar tunai di sekolah',
    ],
    'method_group' => [
        'online' => 'Bayar sekarang (otomatis)',
        'offline' => 'Transfer atau tunai',
    ],
    'pay_modal' => [
        'title' => 'Pilih cara pembayaran',
        'subtitle' => 'Tagihan :description — :amount',
        'online_hint' => 'Pembayaran diverifikasi sistem setelah Anda menyelesaikan langkah di halaman bank atau e-wallet.',
        'transfer_hint' => 'Transfer sesuai nominal, lalu konfirmasi di halaman ini. Admin akan memverifikasi dalam 1–2 hari kerja.',
        'cash_hint' => 'Bayar tunai di sekolah sesuai jadwal yang berlaku. Setelah bayar, konfirmasi lewat WhatsApp ke pihak sekolah.',
        'submit_transfer' => 'Konfirmasi sudah bayar',
        'submit_online' => 'Lanjut bayar',
        'submit_cash' => 'Mengerti',
        'online_unavailable' => 'Pembayaran online sedang disiapkan. Silakan pilih transfer bank atau tunai terlebih dahulu.',
        'transfer_confirmed' => 'Konfirmasi pembayaran diterima. Admin akan memverifikasi tagihan Anda.',
        'cash_no_status_change' => 'Pilihan tunai tidak mengubah status tagihan. Setelah bayar di sekolah, konfirmasi lewat WhatsApp lalu tunggu admin memverifikasi.',
        'bank_section' => 'Rekening tujuan',
    ],
    'notifications' => [
        'verified' => 'Pembayaran berhasil diverifikasi.',
        'rejected' => 'Pembayaran ditolak.',
        'cannot_pay' => 'Tagihan ini tidak dapat dibayar saat ini.',
    ],
    'default_spp' => [
        'action_label' => 'Pengaturan nominal SPP',
        'heading' => 'Nominal SPP default per jenjang',
        'description' => 'Nilai ini dipakai saat membuat tagihan baru (generate per kelas atau manual), kecuali siswa punya SPP khusus.',
        'section' => 'Tarif per jenjang',
        'section_hint' => 'Perubahan tidak mengubah tagihan yang sudah dibuat.',
        'submit' => 'Simpan',
        'cancel' => 'Batal',
        'saved' => 'Nominal SPP default berhasil disimpan.',
    ],
];
