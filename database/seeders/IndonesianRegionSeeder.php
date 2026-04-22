<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class IndonesianRegionSeeder extends Seeder
{
    private const BATCH_SIZE = 1000;

    private const SQL_PATH = 'sql/tca_alamat.sql';

    private const SOURCE_TABLES = ['d_provinsi', 'd_kabkota', 'd_kecamatan', 'd_kelurahan'];

    public function run(): void
    {
        $this->importSourceSql();

        $provinceMap = $this->syncProvinces();
        $cityMap = $this->syncCities($provinceMap);
        $subDistrictMap = $this->syncSubDistricts($cityMap);
        $this->syncVillages($subDistrictMap);

        $this->command?->info('Data wilayah Indonesia berhasil disinkronkan.');
    }

    private function importSourceSql(): void
    {
        $missingTables = array_filter(
            self::SOURCE_TABLES,
            fn (string $table): bool => ! Schema::hasTable($table)
        );

        if ($missingTables === []) {
            return;
        }

        $sqlPath = database_path(self::SQL_PATH);

        if (! file_exists($sqlPath)) {
            throw new RuntimeException(
                'File `'.self::SQL_PATH.'` tidak ditemukan di `'.database_path().'`. '
                . 'Download dari alamat.thecloudalert.com lalu taruh di sana.'
            );
        }

        $this->command?->info('Mengimpor tabel sumber dari '.self::SQL_PATH.'...');

        $sql = file_get_contents($sqlPath);

        if ($sql === false) {
            throw new RuntimeException('Gagal membaca file `'.$sqlPath.'`.');
        }

        DB::unprepared($sql);

        $this->command?->info('Import selesai.');
    }

    /** @return array<int, string> */
    private function syncProvinces(): array
    {
        $now = now();

        $provinceKeyToId = DB::table('provinces')
            ->select(['id', 'name'])
            ->get()
            ->mapWithKeys(fn (object $row): array => [$this->normalizeName($row->name) => $row->id])
            ->all();

        $sourceToTarget = [];
        $pendingInsert = [];

        foreach (DB::table('d_provinsi')->select(['id', 'nama'])->orderBy('id')->get() as $row) {
            $key = $this->normalizeName($row->nama);
            $targetId = $provinceKeyToId[$key] ?? null;

            if ($targetId === null) {
                $targetId = (string) Str::ulid();
                $provinceKeyToId[$key] = $targetId;
                $pendingInsert[] = [
                    'id' => $targetId,
                    'name' => trim($row->nama),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $sourceToTarget[(int) $row->id] = $targetId;

            if (count($pendingInsert) >= self::BATCH_SIZE) {
                DB::table('provinces')->insert($pendingInsert);
                $pendingInsert = [];
            }
        }

        if ($pendingInsert !== []) {
            DB::table('provinces')->insert($pendingInsert);
        }

        return $sourceToTarget;
    }

    /**
     * @param  array<int, string>  $provinceMap
     * @return array<int, string>
     */
    private function syncCities(array $provinceMap): array
    {
        $now = now();

        $cityKeyToId = DB::table('cities')
            ->select(['id', 'province_id', 'name'])
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->province_id.'|'.$this->normalizeName($row->name) => $row->id])
            ->all();

        $sourceToTarget = [];
        $pendingInsert = [];

        foreach (DB::table('d_kabkota')->select(['id', 'd_provinsi_id', 'nama'])->orderBy('id')->get() as $row) {
            $targetProvinceId = $provinceMap[(int) $row->d_provinsi_id] ?? null;

            if ($targetProvinceId === null) {
                continue;
            }

            $name = trim($row->nama);
            $key = $targetProvinceId.'|'.$this->normalizeName($name);
            $targetId = $cityKeyToId[$key] ?? null;

            if ($targetId === null) {
                $targetId = (string) Str::ulid();
                $cityKeyToId[$key] = $targetId;
                $pendingInsert[] = [
                    'id' => $targetId,
                    'province_id' => $targetProvinceId,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $sourceToTarget[(int) $row->id] = $targetId;

            if (count($pendingInsert) >= self::BATCH_SIZE) {
                DB::table('cities')->insert($pendingInsert);
                $pendingInsert = [];
            }
        }

        if ($pendingInsert !== []) {
            DB::table('cities')->insert($pendingInsert);
        }

        return $sourceToTarget;
    }

    /**
     * @param  array<int, string>  $cityMap
     * @return array<int, string>
     */
    private function syncSubDistricts(array $cityMap): array
    {
        $now = now();

        $subDistrictKeyToId = DB::table('sub_districts')
            ->select(['id', 'city_id', 'name'])
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->city_id.'|'.$this->normalizeName($row->name) => $row->id])
            ->all();

        $sourceToTarget = [];
        $pendingInsert = [];

        foreach (DB::table('d_kecamatan')->select(['id', 'd_kabkota_id', 'nama'])->orderBy('id')->get() as $row) {
            $targetCityId = $cityMap[(int) $row->d_kabkota_id] ?? null;

            if ($targetCityId === null) {
                continue;
            }

            $name = trim($row->nama);
            $key = $targetCityId.'|'.$this->normalizeName($name);
            $targetId = $subDistrictKeyToId[$key] ?? null;

            if ($targetId === null) {
                $targetId = (string) Str::ulid();
                $subDistrictKeyToId[$key] = $targetId;
                $pendingInsert[] = [
                    'id' => $targetId,
                    'city_id' => $targetCityId,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $sourceToTarget[(int) $row->id] = $targetId;

            if (count($pendingInsert) >= self::BATCH_SIZE) {
                DB::table('sub_districts')->insert($pendingInsert);
                $pendingInsert = [];
            }
        }

        if ($pendingInsert !== []) {
            DB::table('sub_districts')->insert($pendingInsert);
        }

        return $sourceToTarget;
    }

    /** @param array<int, string> $subDistrictMap */
    private function syncVillages(array $subDistrictMap): void
    {
        $now = now();

        $villageKeyToId = DB::table('villages')
            ->select(['id', 'sub_district_id', 'name'])
            ->get()
            ->mapWithKeys(fn (object $row): array => [$row->sub_district_id.'|'.$this->normalizeName($row->name) => $row->id])
            ->all();

        DB::table('d_kelurahan')
            ->select(['id', 'd_kecamatan_id', 'nama'])
            ->orderBy('id')
            ->chunkById(self::BATCH_SIZE, function ($sourceRows) use (&$villageKeyToId, $subDistrictMap, $now): void {
                $pendingInsert = [];

                foreach ($sourceRows as $row) {
                    $targetSubDistrictId = $subDistrictMap[(int) $row->d_kecamatan_id] ?? null;

                    if ($targetSubDistrictId === null) {
                        continue;
                    }

                    $name = trim($row->nama);
                    $key = $targetSubDistrictId.'|'.$this->normalizeName($name);

                    if (isset($villageKeyToId[$key])) {
                        continue;
                    }

                    $targetId = (string) Str::ulid();
                    $villageKeyToId[$key] = $targetId;
                    $pendingInsert[] = [
                        'id' => $targetId,
                        'sub_district_id' => $targetSubDistrictId,
                        'name' => $name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($pendingInsert !== []) {
                    DB::table('villages')->insert($pendingInsert);
                }
            }, 'id');
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }
}
