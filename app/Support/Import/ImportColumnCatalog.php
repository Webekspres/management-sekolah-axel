<?php

namespace App\Support\Import;

class ImportColumnCatalog
{
    /**
     * @return array<int, ImportColumnDefinition>
     */
    public static function studentColumns(): array
    {
        return [
            new ImportColumnDefinition('nama', 'nama', 'personalia.import.hints.nama', 'nama', required: true, guesses: ['name', 'nama lengkap']),
            new ImportColumnDefinition('email', 'email', 'personalia.import.hints.email', 'email', required: true),
            new ImportColumnDefinition('password', 'password', 'personalia.import.hints.password', 'password', required: true),
            new ImportColumnDefinition('jenis_kelamin', 'jenis_kelamin', 'personalia.import.hints.jenis_kelamin', 'jenis_kelamin', required: true, guesses: ['gender', 'jk']),
            new ImportColumnDefinition('telepon', 'telepon', 'personalia.import.hints.telepon', 'telepon', guesses: ['phone', 'nomor telepon']),
            new ImportColumnDefinition('provinsi_lahir', 'provinsi_lahir', 'personalia.import.hints.wilayah', 'provinsi_lahir', guesses: ['provinsi lahir']),
            new ImportColumnDefinition('kota_kabupaten_lahir', 'kota_kabupaten_lahir', 'personalia.import.hints.wilayah', 'kota_kabupaten_lahir', guesses: ['kota lahir', 'kabupaten lahir', 'kota/kabupaten lahir']),
            new ImportColumnDefinition('tanggal_lahir', 'tanggal_lahir', 'personalia.import.hints.tanggal', 'tanggal_lahir', guesses: ['tgl lahir']),
            new ImportColumnDefinition('kelas', 'kelas', 'personalia.import.hints.kelas', 'kelas', required: true, guesses: ['class']),
            new ImportColumnDefinition('nipd', 'nipd', 'personalia.import.hints.nama', 'nipd', required: true),
            new ImportColumnDefinition('kode_sekolah', 'kode_sekolah', 'personalia.import.hints.nama', 'kode_sekolah', guesses: ['school code']),
            new ImportColumnDefinition('tanggal_masuk', 'tanggal_masuk', 'personalia.import.hints.tanggal', 'tanggal_masuk', guesses: ['tgl masuk', 'admission date']),
            new ImportColumnDefinition('asal_sekolah', 'asal_sekolah', 'personalia.import.hints.nama', 'asal_sekolah'),
            new ImportColumnDefinition('nomor_ijazah', 'nomor_ijazah', 'personalia.import.hints.nama', 'nomor_ijazah', guesses: ['no ijazah']),
            new ImportColumnDefinition('tanggal_ijazah', 'tanggal_ijazah', 'personalia.import.hints.tanggal', 'tanggal_ijazah'),
            new ImportColumnDefinition('spp_khusus', 'spp_khusus', 'personalia.import.hints.spp_khusus', 'spp_khusus', guesses: ['spp']),
            new ImportColumnDefinition('nik', 'nik', 'personalia.import.hints.nik', 'nik', required: true),
            new ImportColumnDefinition('nisn', 'nisn', 'personalia.import.hints.nisn', 'nisn', required: true),
            new ImportColumnDefinition('nomor_kk', 'nomor_kk', 'personalia.import.hints.nama', 'nomor_kk', guesses: ['no kk', 'kk']),
            new ImportColumnDefinition('nomor_akta', 'nomor_akta', 'personalia.import.hints.nama', 'nomor_akta', guesses: ['akta kelahiran']),
            new ImportColumnDefinition('agama', 'agama', 'personalia.import.hints.agama', 'agama', guesses: ['religion']),
            new ImportColumnDefinition('kebutuhan_khusus', 'kebutuhan_khusus', 'personalia.import.hints.nama', 'kebutuhan_khusus'),
            new ImportColumnDefinition('telepon_siswa', 'telepon_siswa', 'personalia.import.hints.telepon', 'telepon_siswa'),
            new ImportColumnDefinition('provinsi', 'provinsi', 'personalia.import.hints.wilayah', 'provinsi'),
            new ImportColumnDefinition('kota_kabupaten', 'kota_kabupaten', 'personalia.import.hints.wilayah', 'kota_kabupaten', guesses: ['kota', 'kabupaten']),
            new ImportColumnDefinition('kecamatan', 'kecamatan', 'personalia.import.hints.wilayah', 'kecamatan', guesses: ['district']),
            new ImportColumnDefinition('desa_kelurahan', 'desa_kelurahan', 'personalia.import.hints.wilayah', 'desa_kelurahan', guesses: ['desa', 'kelurahan', 'village']),
            new ImportColumnDefinition('nomor_rumah', 'nomor_rumah', 'personalia.import.hints.nama', 'nomor_rumah', guesses: ['no rumah']),
            new ImportColumnDefinition('rt', 'rt', 'personalia.import.hints.nama', 'rt'),
            new ImportColumnDefinition('rw', 'rw', 'personalia.import.hints.nama', 'rw'),
            new ImportColumnDefinition('detail_alamat', 'detail_alamat', 'personalia.import.hints.nama', 'detail_alamat', guesses: ['alamat detail']),
            new ImportColumnDefinition('nama_ayah', 'nama_ayah', 'personalia.import.hints.nama', 'nama_ayah'),
            new ImportColumnDefinition('telepon_ayah', 'telepon_ayah', 'personalia.import.hints.telepon', 'telepon_ayah'),
            new ImportColumnDefinition('nama_ibu', 'nama_ibu', 'personalia.import.hints.nama', 'nama_ibu'),
            new ImportColumnDefinition('telepon_ibu', 'telepon_ibu', 'personalia.import.hints.telepon', 'telepon_ibu'),
        ];
    }

    /**
     * @return array<int, ImportColumnDefinition>
     */
    public static function teacherColumns(): array
    {
        return [
            new ImportColumnDefinition('nama', 'nama', 'personalia.import.hints.nama', 'nama', required: true, guesses: ['name', 'nama lengkap']),
            new ImportColumnDefinition('email', 'email', 'personalia.import.hints.email', 'email', required: true),
            new ImportColumnDefinition('password', 'password', 'personalia.import.hints.password', 'password', required: true),
            new ImportColumnDefinition('jenis_kelamin', 'jenis_kelamin', 'personalia.import.hints.jenis_kelamin', 'jenis_kelamin', required: true, guesses: ['gender', 'jk']),
            new ImportColumnDefinition('telepon', 'telepon', 'personalia.import.hints.telepon', 'telepon', guesses: ['phone', 'nomor telepon']),
            new ImportColumnDefinition('provinsi_lahir', 'provinsi_lahir', 'personalia.import.hints.wilayah', 'provinsi_lahir', guesses: ['provinsi lahir']),
            new ImportColumnDefinition('kota_kabupaten_lahir', 'kota_kabupaten_lahir', 'personalia.import.hints.wilayah', 'kota_kabupaten_lahir', guesses: ['kota lahir', 'kabupaten lahir']),
            new ImportColumnDefinition('tanggal_lahir', 'tanggal_lahir', 'personalia.import.hints.tanggal', 'tanggal_lahir', guesses: ['tgl lahir']),
            new ImportColumnDefinition('provinsi', 'provinsi', 'personalia.import.hints.wilayah', 'provinsi'),
            new ImportColumnDefinition('kota_kabupaten', 'kota_kabupaten', 'personalia.import.hints.wilayah', 'provinsi', guesses: ['kota', 'kabupaten']),
            new ImportColumnDefinition('kecamatan', 'kecamatan', 'personalia.import.hints.wilayah', 'kecamatan'),
            new ImportColumnDefinition('desa_kelurahan', 'desa_kelurahan', 'personalia.import.hints.wilayah', 'desa_kelurahan', guesses: ['desa', 'kelurahan']),
            new ImportColumnDefinition('jalan_nomor', 'jalan_nomor', 'personalia.import.hints.nama', 'jalan_nomor', guesses: ['jalan', 'alamat jalan']),
            new ImportColumnDefinition('kode_pos', 'kode_pos', 'personalia.import.hints.nama', 'kode_pos', guesses: ['kodepos', 'postal code']),
            new ImportColumnDefinition('detail_alamat', 'detail_alamat', 'personalia.import.hints.nama', 'detail_alamat'),
            new ImportColumnDefinition('nip', 'nip', 'personalia.import.hints.nama', 'nip'),
            new ImportColumnDefinition('status_kepegawaian', 'status_kepegawaian', 'personalia.import.hints.status_kepegawaian', 'status_kepegawaian', guesses: ['status', 'jabatan']),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function studentHeaderRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): string => $column->label(),
            self::studentColumns(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function teacherHeaderRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): string => $column->label(),
            self::teacherColumns(),
        );
    }

    /**
     * @return array<int, string|null>
     */
    public static function studentHintRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): string => $column->formatHint(),
            self::studentColumns(),
        );
    }

    /**
     * @return array<int, string|null>
     */
    public static function studentExampleRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): ?string => $column->example(),
            self::studentColumns(),
        );
    }

    /**
     * @return array<int, string|null>
     */
    public static function teacherHintRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): string => $column->formatHint(),
            self::teacherColumns(),
        );
    }

    /**
     * @return array<int, string|null>
     */
    public static function teacherExampleRow(): array
    {
        return array_map(
            fn (ImportColumnDefinition $column): ?string => $column->example(),
            self::teacherColumns(),
        );
    }
}
