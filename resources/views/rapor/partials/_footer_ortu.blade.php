{{-- Footer Orang Tua: Halaman 2 --}}
@php
    $titimangsaRaw = \App\Models\Setting::where('key', 'titimangsa')->value('value');
    $titimangsaFormatted = $titimangsaRaw
        ? 'Jakarta, ' . \Carbon\Carbon::parse($titimangsaRaw)->locale('id')->isoFormat('D MMMM YYYY')
        : '—';
@endphp

<table style="border: none; margin-top: 6px;">
    <tr>
        <td style="border: none; width: 50%; text-align: center; vertical-align: top; padding-top: 0;">
            <div style="font-size: 9pt;">Mengetahui,</div>
            <div style="font-size: 9pt;">Orang Tua/Wali</div>
            <div style="margin-top: 36px; border-bottom: 1px solid #000; width: 70%; margin-left: auto; margin-right: auto;"></div>
        </td>
        <td style="border: none; width: 50%; text-align: center; vertical-align: top; padding-top: 0;">
            <div style="font-size: 9pt;">{{ $titimangsaFormatted }}</div>
            <div style="font-size: 9pt; margin-top: 2px;">Wali Kelas</div>
            <div style="margin-top: 36px; border-bottom: 1px solid #000; width: 70%; margin-left: auto; margin-right: auto;"></div>
            <div style="margin-top: 3px; font-size: 9pt;">{{ $waliKelasName ?? '' }}</div>
        </td>
    </tr>
</table>
