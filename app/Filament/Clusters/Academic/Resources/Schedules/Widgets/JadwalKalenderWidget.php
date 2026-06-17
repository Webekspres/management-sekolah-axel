<?php

namespace App\Filament\Clusters\Academic\Resources\Schedules\Widgets;

use App\Models\Schedule;
use App\Models\User;
use App\Support\TemporaryAccessManager;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class JadwalKalenderWidget extends CalendarWidget
{
    protected string $view = 'filament.widgets.jadwal-kalender-widget';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $dateClickEnabled = true;

    protected string|HtmlString|null|bool $heading = 'Kalender Jadwal Pelajaran';

    public bool $isInDayView = false;

    /**
     * @return array<Action>
     */
    public function getHeaderActions(): array
    {
        return [
            Action::make('backToMonth')
                ->label('Lihat Semua Tanggal')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->size('sm')
                ->visible($this->isInDayView)
                ->action(function (): void {
                    $this->isInDayView = false;
                    $this->setOption('view', 'dayGridMonth');
                }),
        ];
    }

    /**
     * Returns calendar events for the given date range.
     *
     * @return Collection|array<mixed>
     */
    protected function getEvents(FetchInfo $info): Collection|array
    {
        $schedules = $this->buildScopedQuery()->get();
        $period = CarbonPeriod::create($info->start, $info->end);
        $events = collect();

        foreach ($period as $date) {
            $matchingSchedules = $schedules->filter(
                fn (Schedule $schedule) => $schedule->day_of_week === $date->dayOfWeek
            );

            $groups = [];

            foreach ($matchingSchedules as $schedule) {
                if ($schedule->subject === null || $schedule->schoolClass === null) {
                    continue;
                }

                $key = "{$schedule->subject_id}_{$schedule->day_of_week}_{$schedule->start_time}_{$schedule->end_time}";

                if (! isset($groups[$key])) {
                    $groups[$key] = [
                        'schedule' => $schedule,
                        'classNames' => [],
                    ];
                }

                $groups[$key]['classNames'][] = $schedule->schoolClass->name;
            }

            foreach ($groups as $group) {
                /** @var Schedule $representative */
                $representative = $group['schedule'];

                $start = $date->copy()->setTimeFromTimeString($representative->start_time);
                $end = $date->copy()->setTimeFromTimeString($representative->end_time);

                $title = $this->buildEventTitle(
                    $representative->start_time,
                    $representative->subject->name,
                    $group['classNames']
                );

                $events->push(
                    CalendarEvent::make()
                        ->title($title)
                        ->start($start)
                        ->end($end)
                );
            }
        }

        return $events;
    }

    /**
     * Handles a date cell click — switches the calendar to the day view.
     */
    protected function onDateClick(DateClickInfo $info): void
    {
        $this->isInDayView = true;
        $this->setOption('view', 'timeGridDay');
        $this->setOption('date', $info->date->toIso8601String());
    }

    /**
     * Builds the Eloquent query for schedules with eager loading and role-based scoping.
     */
    private function buildScopedQuery(): Builder
    {
        $query = Schedule::query()->with(['schoolClass', 'subject', 'teacher.user']);

        /** @var User|null $user */
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if ($user->role === 'guru') {
            if (! $user->teacher) {
                return $query->whereRaw('1=0');
            }
            $query->where('teacher_id', $user->teacher->id);
        }

        $allowedLevelIds = app(TemporaryAccessManager::class)
            ->getAllowedLevelIds($user, Schedule::class);

        if ($allowedLevelIds !== null && $allowedLevelIds->isNotEmpty()) {
            $query->whereHas('schoolClass', fn (Builder $q) => $q->whereIn('level_id', $allowedLevelIds));
        }

        return $query;
    }

    /**
     * @param  array<string>  $classNames
     */
    private function buildEventTitle(string $startTime, string $subjectName, array $classNames): string
    {
        $time = Carbon::createFromTimeString($startTime)->format('H:i');
        sort($classNames);

        return $time.': '.$subjectName.' - '.implode(', ', $classNames);
    }
}
