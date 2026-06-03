<?php

namespace App\Console\Commands;

use App\Models\Level;
use App\Support\Import\ImportTemplateExporter;
use Illuminate\Console\Command;

class CachePersonaliaImportTemplatesCommand extends Command
{
    protected $signature = 'personalia:cache-import-templates';

    protected $description = 'Pre-cache template Excel impor siswa dan guru ke storage';

    public function handle(ImportTemplateExporter $exporter): int
    {
        $this->components->info('Membuat template impor personalia...');

        foreach ($exporter->cacheTargets() as $target) {
            $type = $target['type'];
            $levelId = $target['level_id'];

            $label = $type === 'teacher'
                ? 'Guru'
                : 'Siswa ('.(Level::query()->find($levelId)?->name ?? 'semua').')';

            $this->components->task($label, function () use ($exporter, $type, $levelId): bool {
                $exporter->warm($type, $levelId);

                return true;
            });
        }

        $this->components->success('Template impor tersimpan di storage/app/import-templates/');

        return self::SUCCESS;
    }
}
