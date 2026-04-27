<?php

namespace App\Filament\Clusters\Academic\Resources\LessonPlans;

use App\Filament\Clusters\Academic\AcademicCluster;
use App\Filament\Clusters\Academic\Resources\LessonPlans\Pages\CreateLessonPlan;
use App\Filament\Clusters\Academic\Resources\LessonPlans\Pages\EditLessonPlan;
use App\Filament\Clusters\Academic\Resources\LessonPlans\Pages\ListLessonPlans;
use App\Filament\Clusters\Academic\Resources\LessonPlans\Schemas\LessonPlanForm;
use App\Filament\Clusters\Academic\Resources\LessonPlans\Tables\LessonPlansTable;
use App\Models\LessonPlan;
use App\Models\User;
use App\Support\TemporaryAccessManager;
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

    protected static ?string $label = 'RPP';

    protected static ?string $pluralLabel = 'RPP';

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (in_array($user->role, ['super_admin', 'kepala_sekolah'], true)) {
            return true;
        }

        return app(TemporaryAccessManager::class)
            ->hasTemporaryPolicyGrant($user, 'viewAny', LessonPlan::class);
    }

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
        return parent::getEloquentQuery()
            ->with(['teacher.user', 'subjectForDisplay', 'schoolClass']);
    }
}
