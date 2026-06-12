<?php

namespace App\Services\Personalia;

use App\Models\City;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use App\Support\Import\WilayahNameNormalizer;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Illuminate\Support\Collection;

class RegionLookupService
{
    public function __construct(
        private WilayahNameNormalizer $normalizer,
    ) {}

    public function findProvince(?string $name): ?Province
    {
        if (blank($name)) {
            return null;
        }

        return Province::query()
            ->get()
            ->first(fn (Province $province): bool => $this->normalizer->matches($name, $province->name));
    }

    public function requireProvince(?string $name): Province
    {
        $province = $this->findProvince($name);

        if (! $province) {
            throw new RowImportFailedException(__('personalia.import.errors.province_not_found', [
                'name' => $name,
            ]));
        }

        return $province;
    }

    public function findCity(?string $name, ?Province $province = null): ?City
    {
        if (blank($name)) {
            return null;
        }

        $query = City::query();

        if ($province) {
            $query->where('province_id', $province->id);
        }

        $cities = $query->get();

        $exact = $cities->first(
            fn (City $city): bool => mb_strtolower(trim($name)) === mb_strtolower(trim($city->name)),
        );

        if ($exact) {
            return $exact;
        }

        $matches = $cities->filter(
            fn (City $city): bool => $this->normalizer->matches($name, $city->name),
        );

        if ($matches->count() === 1) {
            return $matches->first();
        }

        return null;
    }

    public function requireCity(?string $name, ?Province $province, string $provinceLabel): City
    {
        $city = $this->findCity($name, $province);

        if (! $city) {
            throw new RowImportFailedException(__('personalia.import.errors.city_not_found', [
                'name' => $name,
                'province' => $provinceLabel,
            ]));
        }

        return $city;
    }

    public function findSubDistrict(?string $name, ?City $city = null): ?SubDistrict
    {
        if (blank($name)) {
            return null;
        }

        $query = SubDistrict::query();

        if ($city) {
            $query->where('city_id', $city->id);
        }

        return $query->get()
            ->first(fn (SubDistrict $subDistrict): bool => $this->normalizer->matches($name, $subDistrict->name));
    }

    public function requireSubDistrict(?string $name, ?City $city, string $cityLabel): SubDistrict
    {
        $subDistrict = $this->findSubDistrict($name, $city);

        if (! $subDistrict) {
            throw new RowImportFailedException(__('personalia.import.errors.sub_district_not_found', [
                'name' => $name,
                'city' => $cityLabel,
            ]));
        }

        return $subDistrict;
    }

    public function findVillage(?string $name, ?SubDistrict $subDistrict = null): ?Village
    {
        if (blank($name)) {
            return null;
        }

        $query = Village::query();

        if ($subDistrict) {
            $query->where('sub_district_id', $subDistrict->id);
        }

        return $query->get()
            ->first(fn (Village $village): bool => $this->normalizer->matches($name, $village->name));
    }

    public function requireVillage(?string $name, ?SubDistrict $subDistrict, string $districtLabel): Village
    {
        $village = $this->findVillage($name, $subDistrict);

        if (! $village) {
            throw new RowImportFailedException(__('personalia.import.errors.village_not_found', [
                'name' => $name,
                'district' => $districtLabel,
            ]));
        }

        return $village;
    }

    /**
     * @return Collection<int, array{provinsi: string, kota_kabupaten: string, kecamatan: string, desa_kelurahan: string}>
     */
    public function regionRowsForTemplate(): Collection
    {
        $rows = collect();

        Province::query()->orderBy('name')->with([
            'cities' => fn ($query) => $query->orderBy('name')->with([
                'subDistricts' => fn ($query) => $query->orderBy('name')->with([
                    'villages' => fn ($query) => $query->orderBy('name'),
                ]),
            ]),
        ])->get()->each(function (Province $province) use ($rows): void {
            foreach ($province->cities as $city) {
                if ($city->subDistricts->isEmpty()) {
                    $rows->push([
                        'provinsi' => $province->name,
                        'kota_kabupaten' => $city->name,
                        'kecamatan' => '',
                        'desa_kelurahan' => '',
                    ]);

                    continue;
                }

                foreach ($city->subDistricts as $subDistrict) {
                    if ($subDistrict->villages->isEmpty()) {
                        $rows->push([
                            'provinsi' => $province->name,
                            'kota_kabupaten' => $city->name,
                            'kecamatan' => $subDistrict->name,
                            'desa_kelurahan' => '',
                        ]);

                        continue;
                    }

                    foreach ($subDistrict->villages as $village) {
                        $rows->push([
                            'provinsi' => $province->name,
                            'kota_kabupaten' => $city->name,
                            'kecamatan' => $subDistrict->name,
                            'desa_kelurahan' => $village->name,
                        ]);
                    }
                }
            }
        });

        return $rows;
    }
}
