# Modul Pembayaran SPP — Tasks

## Fase 1 (selesai)

- [x] Enum, services, gateway stub, migrasi `payment_gateway_logs`
- [x] Admin: InvoiceResource, PaymentResource, bulk generate, verifikasi
- [x] Student: pilih metode, transfer konfirmasi, tunai WhatsApp (informatif)
- [x] Kepsek: view-only
- [x] Tests `tests/Feature/Pembayaran/`
- [x] Dokumentasi `/docs/pembayaran-spp-*.md`

## Fase 2

- [ ] Pilih vendor PG & implement driver
- [ ] Webhook + job `ProcessPaymentWebhook`
- [ ] Notifikasi database saat PAID
