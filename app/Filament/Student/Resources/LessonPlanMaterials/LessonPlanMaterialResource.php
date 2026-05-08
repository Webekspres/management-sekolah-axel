<?php

namespace App\Filament\Student\Resources\LessonPlanMaterials;

use App\Filament\Student\Resources\LessonPlanMaterials\Pages\ListLessonPlanMaterials;
use App\Filament\Student\Resources\LessonPlanMaterials\Tables\LessonPlanMaterialsTable;
use App\Models\LessonPlanMaterial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LessonPlanMaterialResource extends Resource
{
    protected static ?string $model = LessonPlanMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?string $label = 'Materi Pembelajaran';

    protected static ?string $pluralLabel = 'Materi Pembelajaran';

    public static function table(Table $table): Table
    {
        return LessonPlanMaterialsTable::configure($table);
    }

    /**
     * Hanya siswa yang memiliki profil student yang dapat mengakses halaman materi.
     * Ortu (siswa_ortu tanpa profil student) mendapat 403.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->student !== null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $student = auth()->user()?->student;

        if ($student === null) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->whereHas('lessonPlan', fn (Builder $q) => $q
                ->where('status', 'APPROVED')
                ->where('class_id', $student->class_id)
            )
            ->with(['lessonPlan.subjectForDisplay', 'lessonPlan.schoolClass']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonPlanMaterials::route('/'),
        ];
    }
}
