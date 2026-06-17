<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Siswa</title>
    <style>
        @font-face {
            font-family: 'Courgette';
            src: url('{{ public_path('fonts/Courgette-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Calibri';
            src: url('{{ public_path('fonts/calibri.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Calibri';
            src: url('{{ public_path('fonts/calibrib.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @font-face {
            font-family: 'Times New Roman';
            src: url('{{ public_path('fonts/times.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Calibri', sans-serif;
            font-size: 10pt;
            color: #000;
        }

        .page {
            padding: 8mm 10mm 6mm 10mm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: auto; }

        /* ── TABEL UMUM ── */
        table {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }
        td, th {
            border: 1px solid #000;
            padding: 2px 4px;
            word-wrap: break-word;
            overflow: hidden;
            font-family: 'Calibri', sans-serif;
            font-size: 9pt;
            vertical-align: middle;
        }
        th {
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }

        /* ── HEADER ── */
        .header-judul {
            font-family: 'Courgette', cursive;
            font-size: 16pt;
            font-weight: bold;
            font-style: italic;
        }
        .header-sekolah {
            font-family: 'Calibri', sans-serif;
            font-size: 10pt;
            font-weight: bold;
        }
        .header-npsn {
            font-family: 'Calibri', sans-serif;
            font-size: 9pt;
        }

        /* ── SECTION TITLE ── */
        .section-title {
            font-family: 'Calibri', sans-serif;
            font-weight: bold;
            font-size: 10pt;
            margin: 6px 0 3px 0;
        }

        /* ── IDENTITAS ── */
        .identitas-label {
            font-family: 'Calibri', sans-serif;
            font-size: 9pt;
            width: 28%;
        }
        .identitas-sep { width: 3%; }
        .identitas-val {
            font-family: 'Calibri', sans-serif;
            font-size: 9pt;
        }
        .identitas-nowrap {
            white-space: nowrap;
        }

        /* ── SUB HEADER HAL 2 ── */
        .sub-header-center {
            text-align: center;
            font-family: 'Calibri', sans-serif;
            font-weight: bold;
            font-size: 11pt;
            border: 1px solid #000;
            padding: 3px;
            margin-bottom: 4px;
        }

        /* ── NILAI SIKAP ── */
        .sikap-no    { width: 6%; text-align: center; }
        .sikap-aspek { width: 30%; }
        .sikap-desk  { font-family: 'Times New Roman', serif; font-size: 9pt; }

        /* ── KEPRIBADIAN ── */
        .nilai-aktif { font-weight: bold; }
        .nilai-coret { text-decoration: line-through; }

        /* ── BELOW KKM ── */
        .below-kkm { color: #cc0000; font-weight: bold; }

        /* ── FOOTER ── */
        .footer-date {
            text-align: center;
            font-family: 'Calibri', sans-serif;
            font-size: 9pt;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* ── KETERANGAN PREDIKAT LABEL ── */
        .ket-label {
            font-family: 'Times New Roman', serif;
            font-style: italic;
            font-weight: bold;
            font-size: 10pt;
            margin-top: 8px;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    @include('rapor.partials.halaman1')
    @include('rapor.partials.halaman2')
    @include('rapor.partials.halaman3')
</body>
</html>
