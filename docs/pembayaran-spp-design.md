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

## Fase 2 (belum diimplementasi)

- Driver PG nyata + webhook `POST /webhooks/payment/{driver}`
- Notifikasi ke siswa saat lunas
