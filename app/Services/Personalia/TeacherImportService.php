<?php

namespace App\Services\Personalia;

use App\Models\Address;
use App\Models\City;
use App\Models\Teacher;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherImportService
{
    /**
     * @var array<string, string>
     */
    private const EMPLOYMENT_STATUS_MAP = [
        'staff tu' => 'staff_tu',
        'staff_tu' => 'staff_tu',
        'guru kelas' => 'guru_kelas',
        'guru_kelas' => 'guru_kelas',
        'lainnya' => 'other',
        'other' => 'other',
    ];

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

        $birthCity = filled($userData['place_of_birth'] ?? null)
            ? City::query()->where('name', $userData['place_of_birth'])->first()
            : null;

        $this->validateAddressData($data);

        $addressData = array_filter([
            'province_id' => $data['address_province_id'] ?? null,
            'city_id' => $data['address_city_id'] ?? null,
            'sub_district_id' => $data['address_sub_district_id'] ?? null,
            'village_id' => $data['address_village_id'] ?? null,
            'street' => $data['address_street'] ?? null,
            'postal_code' => $data['address_postal_code'] ?? null,
        ], fn ($value): bool => $value !== null);

        unset(
            $data['user'],
            $data['address_province_id'],
            $data['address_city_id'],
            $data['address_sub_district_id'],
            $data['address_village_id'],
            $data['address_street'],
            $data['address_postal_code'],
        );

        return DB::transaction(function () use ($addressData, $birthCity, $data, $userData): Model {
            $userPayload = [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'gender' => $userData['gender'] ?? 'L',
                'phone_number' => $userData['phone_number'] ?? null,
                'date_of_birth' => $userData['date_of_birth'] ?? null,
                'place_of_birth' => $userData['place_of_birth'] ?? null,
                'address_detail' => $userData['address_detail'] ?? null,
                'city_id' => $birthCity?->id,
                'role' => 'guru',
            ];

            if ($addressData !== []) {
                $address = Address::query()->create($addressData);
                $userPayload['address_id'] = $address->id;
                $userPayload['city_id'] = $addressData['city_id'] ?? $userPayload['city_id'];
            }

            $user = User::query()->create($userPayload);

            return $user->teacher()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function createFromRow(array $row, array $options = []): Teacher
    {
        unset($options);

        $this->assertNoDuplicates($row);

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

        $addressIds = $this->resolveAddressIds($row);

        $formData = [
            'user' => [
                'name' => $row['nama'],
                'email' => $row['email'],
                'password' => $row['password'],
                'gender' => strtoupper((string) ($row['jenis_kelamin'] ?? 'L')),
                'phone_number' => $this->blankAsNull($row['telepon'] ?? null),
                'date_of_birth' => filled($row['tanggal_lahir'] ?? null) ? (string) $row['tanggal_lahir'] : null,
                'place_of_birth' => $birthCityName,
                'address_detail' => $this->blankAsNull($row['detail_alamat'] ?? null),
            ],
            'address_province_id' => $addressIds['province_id'],
            'address_city_id' => $addressIds['city_id'],
            'address_sub_district_id' => $addressIds['sub_district_id'],
            'address_village_id' => $addressIds['village_id'],
            'address_street' => $this->blankAsNull($row['jalan_nomor'] ?? null),
            'address_postal_code' => $this->blankAsNull($row['kode_pos'] ?? null),
            'nip' => $this->blankAsNull($row['nip'] ?? null),
            'employment_status' => $this->mapEmploymentStatus($this->blankAsNull($row['status_kepegawaian'] ?? null)),
        ];

        /** @var Teacher $teacher */
        $teacher = $this->createFromFormData($formData);

        return $teacher;
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

        if (filled($row['nip'] ?? null) && Teacher::query()->where('nip', $row['nip'])->exists()) {
            throw new RowImportFailedException(__('personalia.import.errors.nip_exists', [
                'nip' => $row['nip'],
            ]));
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{province_id: ?string, city_id: ?string, sub_district_id: ?string, village_id: ?string}
     */
    private function resolveAddressIds(array $row): array
    {
        if (blank($row['provinsi'] ?? null)
            || blank($row['kota_kabupaten'] ?? null)
            || blank($row['kecamatan'] ?? null)
            || blank($row['desa_kelurahan'] ?? null)
        ) {
            return [
                'province_id' => null,
                'city_id' => null,
                'sub_district_id' => null,
                'village_id' => null,
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
            'province_id' => $province->id,
            'city_id' => $city?->id,
            'sub_district_id' => $subDistrict?->id,
            'village_id' => $village?->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateAddressData(array $data): void
    {
        $fields = [
            'address_province_id' => 'Provinsi wajib diisi jika alamat domisili diisi.',
            'address_city_id' => 'Kota/Kabupaten wajib diisi jika provinsi dipilih.',
            'address_sub_district_id' => 'Kecamatan wajib diisi jika kota/kabupaten dipilih.',
            'address_village_id' => 'Desa/Kelurahan wajib diisi jika kecamatan dipilih.',
            'address_street' => 'Jalan/Gang/Nomor wajib diisi jika wilayah alamat sudah dipilih.',
            'address_postal_code' => 'Kode pos wajib diisi jika wilayah alamat sudah dipilih.',
        ];

        $filledCount = collect($fields)
            ->keys()
            ->filter(fn (string $key): bool => filled($data[$key] ?? null))
            ->count();

        if ($filledCount === 0) {
            return;
        }

        if ($filledCount === count($fields)) {
            return;
        }

        $errors = [];

        foreach ($fields as $key => $message) {
            if (blank($data[$key] ?? null)) {
                $errors[$key] = $message;
            }
        }

        throw ValidationException::withMessages($errors);
    }

    private function mapEmploymentStatus(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $key = mb_strtolower(trim($value));
        $mapped = self::EMPLOYMENT_STATUS_MAP[$key] ?? null;

        if ($mapped === null) {
            throw new RowImportFailedException(__('personalia.import.errors.invalid_employment_status', [
                'value' => $value,
            ]));
        }

        return $mapped;
    }

    private function blankAsNull(mixed $value): mixed
    {
        return blank($value) ? null : $value;
    }
}
