# Modul Pembayaran SPP — Design

## Layer

- `App\Enums\PaymentStatus`, `PaymentMethod`
- `App\Services\InvoiceService` — nomor tagihan, nominal SPP, bulk generate
- `App\Services\PaymentService` — konfirmasi transfer, gateway, verifikasi admin
- `App\Contracts\PaymentGateway` — driver via `config/payment.php` (default: `log`)
- `App\Support\SchoolPaymentSettings` — baca rekening dari `Setting`

## Panel Filament

| Panel   | Path resource                                        |
| ------- | ---------------------------------------------------- |
| Admin   | `app/Filament/Clusters/Keuangan/` — cluster Keuangan |
| Student | `app/Filament/Student/Resources/Invoices/`           |
| Kepsek  | `app/Filament/Kepsek/Resources/Invoices/`            |

Action bayar siswa: `App\Filament\Shared\Actions\PayInvoiceAction`.

## Database tambahan

- `payment_gateway_logs` — audit request/response PG
- Index `invoices(student_id, status)`
- Kolom `billing_period` (YYYY-MM) + unique `(student_id, academic_year_id, billing_period)`

## Invariants (hardening)

- Satu tagihan lunas (`PAID`) tidak menerima pembayaran baru.
- Status tagihan diturunkan dari alur pembayaran, bukan edit form manual.
- `amount`, `student_id`, `academic_year_id`, `billing_period` terkunci setelah ada pembayaran atau status `PAID`; hanya `description` dan `due_date` dapat diedit.
- Duplikat tagihan per `(siswa, tahun akademik, billing_period)` ditolak.
- Gateway siswa: `config('payment.student_gateway_enabled')` — default `false`.

## Fase 2 (belum diimplementasi)

- Set `PAYMENT_STUDENT_GATEWAY_ENABLED=true` + driver PG nyata
- Webhook `POST /webhooks/payment/{driver}`
- Notifikasi ke siswa saat lunas
