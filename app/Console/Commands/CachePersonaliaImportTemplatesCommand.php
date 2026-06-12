<?php

namespace App\Console\Commands;

use App\Models\Level;
use App\Support\Import\ImportTemplateCacheRunner;
use App\Support\Import\ImportTemplateExporter;
use Illuminate\Console\Command;

class CachePersonaliaImportTemplatesCommand extends Command
{
    protected $signature = 'personalia:cache-import-templates {--force : Jalankan meskipun proses cache lain sedang berjalan}';

    protected $description = 'Pre-cache template Excel impor siswa dan guru ke storage';

    public function handle(ImportTemplateExporter $exporter): int
    {
        if ($this->option('force')) {
            ImportTemplateCacheRunner::releaseLock();
        }

        if (! ImportTemplateCacheRunner::acquireLock()) {
            $this->components->warn('Proses cache template impor sedang berjalan. Coba lagi nanti.');

            return self::FAILURE;
        }

        try {
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
        } finally {
            ImportTemplateCacheRunner::releaseLock();
        }
    }
}
