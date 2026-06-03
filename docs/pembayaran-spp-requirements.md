# Modul Pembayaran SPP — Requirements

## Ringkasan

Tagihan SPP (`invoices`) dan pembayaran (`payments`) untuk portal admin, kepala sekolah (lihat), dan siswa/orang tua (bayar & riwayat).

## Peran

| Peran            | Kemampuan                                                                                                          |
| ---------------- | ------------------------------------------------------------------------------------------------------------------ |
| `super_admin`    | CRUD tagihan, bulk generate per kelas, verifikasi/tolak pembayaran, catat pembayaran manual (tunai pasca-WhatsApp) |
| `kepala_sekolah` | Lihat tagihan & filter status                                                                                      |
| `siswa_ortu`     | Lihat tagihan sendiri, pilih metode bayar, konfirmasi transfer di portal                                           |

## Metode pembayaran (siswa)

- **Online** (QRIS, VA): gateway abstraksi (`LogPaymentGateway` stub di v1).
- **Transfer**: instruksi rekening dari `Setting`, tombol konfirmasi → status menunggu verifikasi.
- **Tunai**: instruksi + himbau konfirmasi lewat WhatsApp (**tanpa** nomor WA di UI); tidak membuat record pembayaran dari portal.

## Copywriting

String UI di `lang/id/pembayaran.php` dan label enum `PaymentMethod` / `PaymentStatus`.

## Setting wajib (key)

- `bank_name`, `account_number`, `account_holder`
- `school_whatsapp` (hanya referensi admin, tidak ditampilkan ke siswa)

## Activity log

Model `Invoice` dan `Payment` memakai `log_name = spp`.
