<?php

namespace App\Filament\Guru\Resources\LessonPlans\Schemas;

use App\Models\LessonPlan;
use App\Models\SchoolClass;
use App\Support\PublicStorageUrl;
use App\Support\RichText;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\HtmlString;
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
                        Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?string $state): string => $state ?? 'DRAFT')
                            ->hiddenOn('create'),
                        Placeholder::make('revision_note')
                            ->label('Catatan Revisi dari Kepsek')
                            ->content(function (?string $state): string {
                                return RichText::display($state);
                            })
                            ->hiddenOn('create'),
                        Placeholder::make('file_link')
                            ->label('File Saat Ini')
                            ->content(function (Get $get): HtmlString|string {
                                $path = $get('file_path');
                                if (is_array($path)) {
                                    $path = $path[0] ?? null;
                                }
                                if (! is_string($path) || blank($path)) {
                                    return '-';
                                }

                                $url = PublicStorageUrl::fromPublicDiskPath($path);
                                $name = basename($path);

                                return new HtmlString(
                                    '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer" class="text-primary-600 hover:underline">'.e($name).'</a>'
                                );
                            })
                            ->hiddenOn('create')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function isContentLocked(?LessonPlan $record): bool
    {
        if (! $record) {
            return false;
        }

        return $record->status === 'APPROVED';
    }
}
