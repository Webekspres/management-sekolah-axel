<?php

namespace App\Console\Commands;

use App\Models\Level;
use App\Support\Import\ImportTemplateCacheRunner;
use App\Support\Import\ImportTemplateExporter;
use Illuminate\Console\Command;

class CachePersonaliaImportTemplatesCommand extends Command
{
    protected $signature = 'personalia:cache-import-templates
                            {--force : Jalankan meskipun proses cache lain sedang berjalan}
                            {--target= : Hanya satu template: teacher, sd, smp, atau sma}';

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
            ImportTemplateCacheRunner::appendLog('Perintah cache template impor dijalankan.');

            $this->components->info('Membuat template impor personalia...');

            $targets = $this->resolveTargets($exporter);

            if ($targets === []) {
                $this->components->error('Target template tidak dikenali. Gunakan: teacher, sd, smp, sma.');

                return self::FAILURE;
            }

            foreach ($targets as $target) {
                $type = $target['type'];
                $levelId = $target['level_id'];

                $label = $type === 'teacher'
                    ? 'Guru'
                    : 'Siswa ('.(Level::query()->find($levelId)?->name ?? 'semua').')';

                $this->components->task($label, function () use ($exporter, $type, $levelId, $label): bool {
                    ImportTemplateCacheRunner::appendLog('Membangun template: '.$label);

                    $exporter->warm($type, $levelId);

                    ImportTemplateCacheRunner::appendLog('Selesai template: '.$label);

                    return true;
                });
            }

            $this->components->success('Template impor tersimpan di storage/app/import-templates/');

            ImportTemplateCacheRunner::appendLog('Cache template impor selesai.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            ImportTemplateCacheRunner::appendLog('Cache template impor gagal: '.$exception->getMessage());

            throw $exception;
        } finally {
            ImportTemplateCacheRunner::releaseLock();
        }
    }

    /**
     * @return list<array{type: 'student'|'teacher', level_id: string|null}>
     */
    private function resolveTargets(ImportTemplateExporter $exporter): array
    {
        $targets = $exporter->cacheTargets();
        $only = $this->option('target');

        if (blank($only)) {
            return $targets;
        }

        $only = strtolower((string) $only);

        return array_values(array_filter(
            $targets,
            function (array $target) use ($only): bool {
                if ($only === 'teacher') {
                    return $target['type'] === 'teacher';
                }

                if ($target['type'] !== 'student') {
                    return false;
                }

                $levelName = Level::query()->find($target['level_id'])?->name;

                return str($levelName)->slug() === $only;
            },
        ));
    }
}
