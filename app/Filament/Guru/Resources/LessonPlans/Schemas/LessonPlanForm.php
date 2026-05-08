<?php

namespace App\Filament\Guru\Resources\LessonPlans\Schemas;

use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Support\PublicStorageUrl;
use Filament\Actions\Action;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;

class LessonPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulir RPP')
                    ->description('Susun RPP, simpan sebagai draft, lalu ajukan untuk approval.')
                    ->columns(2)
                    ->schema([
                        Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        Select::make('class_id')
                            ->label('Kelas')
                            ->options(fn (): array => SchoolClass::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        DatePicker::make('implementation_date')
                            ->label('Tanggal Pelaksanaan')
                            ->native(false)
                            ->required(false)
                            ->rules(['required', 'date'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        TextInput::make('topic')
                            ->label('Materi Topik')
                            ->required(false)
                            ->rules(['required', 'string', 'max:255'])
                            ->markAsRequired()
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record)),
                        FileUpload::make('file_path')
                            ->label('File RPP')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->disk('public')
                            ->directory('lesson-plans')
                            ->visibility('public')
                            ->preserveFilenames()
                            ->fetchFileInformation(fn (): bool => ! app()->runningUnitTests())
                            ->downloadable()
                            ->openable()
                            ->getUploadedFileUsing(static function (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array {
                                /** @var FilesystemAdapter $storage */
                                $storage = $component->getDisk();
                                $shouldFetchFileInformation = $component->shouldFetchFileInformation();

                                if ($shouldFetchFileInformation) {
                                    try {
                                        if (! $storage->exists($file)) {
                                            return null;
                                        }
                                    } catch (UnableToCheckFileExistence $exception) {
                                        return null;
                                    }
                                }

                                $url = PublicStorageUrl::fromPublicDiskPath($file);

                                return [
                                    'name' => ($component->isMultiple() ? ($storedFileNames[$file] ?? null) : $storedFileNames) ?? basename($file),
                                    'size' => $shouldFetchFileInformation ? $storage->size($file) : 0,
                                    'type' => $shouldFetchFileInformation ? $storage->mimeType($file) : null,
                                    'url' => $url,
                                ];
                            })
                            ->getOpenableFileUrlUsing(static fn (string $file): string => PublicStorageUrl::fromPublicDiskPath($file))
                            ->getDownloadableFileUrlUsing(static fn (string $file): string => PublicStorageUrl::fromPublicDiskPath($file))
                            ->required(fn (?LessonPlan $record): bool => ! self::isContentLocked($record))
                            ->markAsRequired(fn (?LessonPlan $record): bool => ! self::isContentLocked($record))
                            ->disabled(fn (?LessonPlan $record): bool => self::isContentLocked($record))
                            ->columnSpanFull(),
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn (?LessonPlan $record): string => $record?->status ?? 'DRAFT')
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                        Textarea::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->rows(5)
                            ->readOnly()
                            ->dehydrated(false)
                            ->placeholder('Tidak ada catatan revisi.')
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ]),
                Section::make('Materi Pembelajaran')
                    ->description('Upload file materi yang akan dibagikan ke siswa setelah RPP disetujui.')
                    ->schema([
                        Repeater::make('materials')
                            ->relationship()
                            ->label('File Materi')
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('file_path')
                                    ->label('File')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    ])
                                    ->disk('public')
                                    ->directory('lesson-plan-materials')
                                    ->visibility('public')
                                    ->preserveFilenames()
                                    ->storeFileNamesIn('original_filename')
                                    ->downloadable()
                                    ->openable()
                                    ->getDownloadableFileUrlUsing(
                                        static fn (string $file): string => PublicStorageUrl::fromPublicDiskPath($file)
                                    )
                                    ->getOpenableFileUrlUsing(
                                        static fn (string $file): string => PublicStorageUrl::fromPublicDiskPath($file)
                                    )
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Tambah Materi')
                            ->disabled(fn (?LessonPlan $record): bool => self::isMaterialLocked($record))
                            ->deleteAction(
                                fn (Action $action, ?LessonPlan $record) => $action
                                    ->hidden(fn (): bool => self::isMaterialLocked($record))
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function isContentLocked(?LessonPlan $record): bool
    {
        if (! $record) {
            return false;
        }

        return in_array($record->status, ['PENDING', 'APPROVED'], true);
    }

    private static function isMaterialLocked(?LessonPlan $record): bool
    {
        if (! $record) {
            return false;
        }

        return in_array($record->status, ['PENDING', 'APPROVED'], true);
    }
}
