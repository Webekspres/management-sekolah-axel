<?php

namespace App\Services\Personalia;

use App\Models\City;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\MoneyFormat;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentImportService
{
    public function __construct(
        private RegionLookupService $regions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromFormData(array $data): Model
    {
        $userData = $data['user'] ?? [];

        if (User::query()->where('email', $userData['email'] ?? null)->exists()) {
            throw ValidationException::withMessages([
                'user.email' => 'Email sudah digunakan.',
            ]);
        }

        unset(
            $data['user'],
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
        );

        return DB::transaction(function () use ($data, $userData): Model {
            $birthCity = filled($userData['place_of_birth'] ?? null)
                ? City::query()->where('name', $userData['place_of_birth'])->first()
                : null;

            $user = User::query()->create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'gender' => $userData['gender'] ?? 'L',
                'phone_number' => $userData['phone_number'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
                'place_of_birth' => $userData['place_of_birth'] ?? null,
                'address_detail' => $userData['address_detail'] ?? null,
                'city_id' => $birthCity?->id,
                'role' => 'siswa_ortu',
            ]);

            return $user->student()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $options
     */
    public function createFromRow(array $row, array $options = []): Student
    {
        $this->assertNoDuplicates($row);

        $levelId = $options['academic_level_id'] ?? null;
        $class = $this->resolveSchoolClass($row['kelas'] ?? null, $levelId);

        $birthProvince = filled($row['provinsi_lahir'] ?? null)
            ? $this->regions->requireProvince($row['provinsi_lahir'])
            : null;

        $birthCityName = null;
        if (filled($row['kota_kabupaten_lahir'] ?? null)) {
            $birthCity = $this->regions->requireCity(
                $row['kota_kabupaten_lahir'],
                $birthProvince,
                (string) ($row['provinsi_lahir'] ?? ''),
            );
            $birthCityName = $birthCity->name;
        }

        $domicile = $this->resolveDomicileNames($row);

        $formData = [
            'user' => [
                'name' => $row['nama'],
                'email' => $row['email'],
                'password' => $row['password'],
                'gender' => strtoupper((string) ($row['jenis_kelamin'] ?? 'L')),
                'phone_number' => $row['telepon'] ?? null,
                'date_of_birth' => $this->parseDate($row['tanggal_lahir'] ?? null),
                'place_of_birth' => $birthCityName,
                'address_detail' => $row['detail_alamat'] ?? null,
            ],
            'class_id' => $class->id,
            'nipd' => $row['nipd'],
            'nisn' => $row['nisn'],
            'nik' => $row['nik'],
            'kk_number' => $row['nomor_kk'] ?? null,
            'birth_cert_number' => $row['nomor_akta'] ?? null,
            'religion' => $row['agama'] ?? null,
            'school_code' => $row['kode_sekolah'] ?? null,
            'student_phone' => $row['telepon_siswa'] ?? null,
            'special_needs' => $row['kebutuhan_khusus'] ?? null,
            'house_number' => $row['nomor_rumah'] ?? null,
            'rt' => $row['rt'] ?? null,
            'rw' => $row['rw'] ?? null,
            'village' => $domicile['village'],
            'district' => $domicile['district'],
            'city' => $domicile['city'],
            'father_name' => $row['nama_ayah'] ?? null,
            'father_phone' => $row['telepon_ayah'] ?? null,
            'mother_name' => $row['nama_ibu'] ?? null,
            'mother_phone' => $row['telepon_ibu'] ?? null,
            'admission_date' => $this->parseDate($row['tanggal_masuk'] ?? null),
            'origin_school' => $row['asal_sekolah'] ?? null,
            'diploma_date' => $this->parseDate($row['tanggal_ijazah'] ?? null),
            'diploma_number' => $row['nomor_ijazah'] ?? null,
            'custom_spp' => MoneyFormat::parse($row['spp_khusus'] ?? null),
        ];

        /** @var Student $student */
        $student = $this->createFromFormData($formData);

        return $student;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function assertNoDuplicates(array $row): void
    {
        if (User::query()->where('email', $row['email'] ?? null)->exists()) {
            throw new RowImportFailedException(__('personalia.import.errors.email_exists', [
                'email' => $row['email'],
            ]));
        }

        if (Student::query()->where('nipd', $row['nipd'] ?? null)->exists()) {
            throw new RowImportFailedException(__('personalia.import.errors.nipd_exists', [
                'nipd' => $row['nipd'],
            ]));
        }

        if (Student::query()->where('nisn', $row['nisn'] ?? null)->exists()) {
            throw new RowImportFailedException(__('personalia.import.errors.nisn_exists', [
                'nisn' => $row['nisn'],
            ]));
        }
    }

    private function resolveSchoolClass(?string $name, ?string $levelId): SchoolClass
    {
        if (blank($name)) {
            throw new RowImportFailedException(__('personalia.import.errors.class_not_found', [
                'name' => '',
            ]));
        }

        $query = SchoolClass::query()->withoutGlobalScopes()->where('name', $name);

        if (filled($levelId)) {
            $query->where('level_id', $levelId);
        }

        $classes = $query->get();

        if ($classes->isEmpty()) {
            throw new RowImportFailedException(__('personalia.import.errors.class_not_found', [
                'name' => $name,
            ]));
        }

        if ($classes->count() > 1) {
            throw new RowImportFailedException(__('personalia.import.errors.class_ambiguous', [
                'name' => $name,
            ]));
        }

        return $classes->first();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{city: ?string, district: ?string, village: ?string}
     */
    private function resolveDomicileNames(array $row): array
    {
        if (blank($row['provinsi'] ?? null)) {
            return [
                'city' => $row['kota_kabupaten'] ?? null,
                'district' => $row['kecamatan'] ?? null,
                'village' => $row['desa_kelurahan'] ?? null,
            ];
        }

        $province = $this->regions->requireProvince($row['provinsi']);

        $city = filled($row['kota_kabupaten'] ?? null)
            ? $this->regions->requireCity($row['kota_kabupaten'], $province, $province->name)
            : null;

        $subDistrict = ($city && filled($row['kecamatan'] ?? null))
            ? $this->regions->requireSubDistrict($row['kecamatan'], $city, $city->name)
            : null;

        $village = ($subDistrict && filled($row['desa_kelurahan'] ?? null))
            ? $this->regions->requireVillage($row['desa_kelurahan'], $subDistrict, $subDistrict->name)
            : null;

        return [
            'city' => $city?->name ?? $row['kota_kabupaten'] ?? null,
            'district' => $subDistrict?->name ?? $row['kecamatan'] ?? null,
            'village' => $village?->name ?? $row['desa_kelurahan'] ?? null,
        ];
    }

    private function parseDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return (string) $value;
    }
}
