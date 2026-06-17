<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case KepalaSekolah = 'kepala_sekolah';
    case Guru = 'guru';
    case SiswaOrtu = 'siswa_ortu';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
