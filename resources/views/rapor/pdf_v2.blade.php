<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Siswa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: calibri, 'DejaVu Sans', sans-serif;
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
            font-family: calibri, 'DejaVu Sans', sans-serif;
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
            font-family: 'times new roman', serif;
            font-size: 16pt;
            font-weight: bold;
            font-style: italic;
        }
        .header-sekolah {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            font-weight: bold;
        }
        .header-npsn {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
        }

        /* ── SECTION TITLE ── */
        .section-title {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-weight: bold;
            font-size: 10pt;
            margin: 6px 0 3px 0;
        }

        /* ── IDENTITAS ── */
        .identitas-label {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            width: 22%;
            vertical-align: top;
        }
        .identitas-sep {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            width: 2%;
            vertical-align: top;
        }
        .identitas-val {
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            width: 26%;
            vertical-align: top;
        }

        /* ── SUB HEADER HAL 2 ── */
        .sub-header-center {
            text-align: center;
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-weight: bold;
            font-size: 11pt;
            border: 1px solid #000;
            padding: 3px;
            margin-bottom: 4px;
        }

        /* ── NILAI SIKAP ── */
        .sikap-no    { width: 6%; text-align: center; }
        .sikap-aspek { width: 30%; }
        .sikap-desk  { font-family: 'times new roman', 'DejaVu Serif', serif; font-size: 9pt; }

        /* ── KEPRIBADIAN ── */
        .nilai-aktif { font-weight: bold; }
        .nilai-coret { text-decoration: line-through; }

        /* ── BELOW KKM ── */
        .below-kkm { color: #cc0000; font-weight: bold; }

        /* ── FOOTER ── */
        .footer-date {
            text-align: center;
            font-family: calibri, 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        /* ── KETERANGAN PREDIKAT LABEL ── */
        .ket-label {
            font-family: 'times new roman', 'DejaVu Serif', serif;
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
