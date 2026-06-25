{{-- Footer Resmi: Halaman 1 & 3 --}}
<div class="footer-date">{{ $titimangsaFormatted ?? '—' }}</div>

<table style="border: none;">
    <tr>
        <td style="border: none; width: 50%; text-align: center; vertical-align: top; padding-top: 0;">
            <div style="font-size: 9pt;">Mengetahui,</div>
            <div style="font-size: 9pt;">Ketua Litbang HS-TKB</div>
            <div style="margin-top: 36px; border-bottom: 1px solid #000; width: 70%; margin-left: auto; margin-right: auto;"></div>
        </td>
        <td style="border: none; width: 50%; text-align: center; vertical-align: top; padding-top: 0;">
            <div style="font-size: 9pt;">Dibuat oleh,</div>
            <div style="font-size: 9pt;">Wali Kelas {{ $schoolClass?->level?->name ?? 'SMP' }}</div>
            <div style="margin-top: 36px; border-bottom: 1px solid #000; width: 70%; margin-left: auto; margin-right: auto;"></div>
            <div style="margin-top: 3px; font-size: 9pt;">{{ $waliKelasName ?? '' }}</div>
        </td>
    </tr>
</table>
