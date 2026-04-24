<?php

namespace App\Filament\Kepsek\Resources\LessonPlans;

use App\Filament\Kepsek\Clusters\AcademicCluster;
use App\Filament\Kepsek\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Filament\Kepsek\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Filament\Kepsek\Resources\LessonPlans\Schemas\LessonPlanForm;
use App\Filament\Kepsek\Resources\LessonPlans\Tables\LessonPlansTable;
use App\Models\LessonPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonPlanResource extends Resource
{
    protected static ?string $model = LessonPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = AcademicCluster::class;

    protected static ?string $label = 'Approval RPP';

    protected static ?string $pluralLabel = 'Approval RPP';

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
            'edit' => EditLessonPlan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['teacher.user', 'subjectForDisplay', 'schoolClass']);
    }
}
