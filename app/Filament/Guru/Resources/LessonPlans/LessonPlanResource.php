<?php

namespace App\Filament\Guru\Resources\LessonPlans;

use App\Filament\Guru\Clusters\AcademicCluster;
use App\Filament\Guru\Resources\LessonPlans\Pages\CreateLessonPlan;
use App\Filament\Guru\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Filament\Guru\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Filament\Guru\Resources\LessonPlans\Schemas\LessonPlanForm;
use App\Filament\Guru\Resources\LessonPlans\Tables\LessonPlansTable;
use App\Models\LessonPlan;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonPlanResource extends Resource
{
    protected static ?string $model = LessonPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = AcademicCluster::class;

    protected static ?string $label = 'Pengajuan RPP';

    protected static ?string $pluralLabel = 'Pengajuan RPP';

    public static function form(Schema $schema): Schema
    {
        return LessonPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLessonPlans::route('/'),
            'create' => CreateLessonPlan::route('/create'),
            'edit' => EditLessonPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User $user */
        $user = auth()->user();

        if (! $user->teacher) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('teacher_id', $user->teacher->id);
    }
}
