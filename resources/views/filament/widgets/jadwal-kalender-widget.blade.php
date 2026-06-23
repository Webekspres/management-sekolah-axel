@php
    use Filament\Support\Facades\FilamentAsset;
    use Guava\Calendar\Enums\Context;
    use Filament\Support\Facades\FilamentColor;
    use Filament\Support\View\Components\ButtonComponent;
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :after-header="$this->getCachedHeaderActionsComponent()"
        :footer="$this->getCachedFooterActionsComponent()"
    >

        {{-- Dark mode overrides — injected AFTER CDN CSS to guarantee priority --}}
        <style>
            html.dark .ec {
                --ec-bg-color: #18181b !important;
                --ec-color-400: #52525b;
                --ec-color-300: #3f3f46;
                --ec-color-200: #27272a;
                --ec-color-100: #18181b;
                --ec-color-50: #111113;
                --ec-text-color: #d4d4d8;
                --ec-border-color: #27272a;
                --ec-button-bg-color: #27272a;
                --ec-button-border-color: #3f3f46;
                --ec-button-text-color: #d4d4d8;
                --ec-button-active-bg-color: #3f3f46;
                --ec-button-active-border-color: #52525b;
                --ec-button-active-text-color: #fafafa;
                --ec-event-bg-color: #2563eb;
                --ec-event-text-color: #fff;
                --ec-bg-event-color: #3f3f46;
                --ec-bg-event-opacity: 0.4;
                --ec-now-indicator-color: #f59e0b;
                --ec-popup-bg-color: #18181b;
                --ec-today-bg-color: rgba(245, 158, 11, 0.12);
                --ec-highlight-color: rgba(37, 99, 235, 0.12);
                --ec-list-day-bg-color: #18181b;
                background-color: transparent !important;
                color: #d4d4d8;
                color-scheme: dark;
            }

            html.dark .ec-day { --ec-day-bg-color: #18181b !important; }
            html.dark .ec-day.ec-today { --ec-day-bg-color: rgba(245, 158, 11, 0.12) !important; }
            html.dark .ec-day.ec-other-month { --ec-day-bg-color: #111113 !important; }

            html.dark .ec-body,
            html.dark .ec-header,
            html.dark .ec-sidebar,
            html.dark .ec-col-head,
            html.dark .ec-col-group,
            html.dark .ec-slots,
            html.dark .ec-time-grid .ec-body,
            html.dark .ec-time-grid .ec-days,
            html.dark .ec-time-grid .ec-all-day,
            html.dark .ec-no-events { background-color: #18181b !important; }

            html.dark .ec-header .ec-day,
            html.dark .ec-col-head,
            html.dark .ec-col-group { color: #a1a1aa !important; }

            html.dark .ec-day a,
            html.dark .ec-day .ec-day-head a { color: #a1a1aa !important; }

            html.dark .ec-day.ec-today a,
            html.dark .ec-day.ec-today .ec-day-head a { color: #fbbf24 !important; font-weight: 700; }

            html.dark .ec-day.ec-other-month a { color: #52525b !important; }

            html.dark .ec-title { color: #fafafa !important; }
            html.dark .ec-time { color: #71717a !important; }
            html.dark .ec-line { border-color: #27272a !important; }

            html.dark .ec-button {
                background-color: #27272a !important;
                color: #d4d4d8 !important;
                border-color: #3f3f46 !important;
            }
            html.dark .ec-button:not(:disabled):hover,
            html.dark .ec-button.ec-active {
                background-color: #3f3f46 !important;
                color: #fafafa !important;
            }

            .ec-event {
                border-radius: 4px;
                font-size: .75rem;
                line-height: 1.2;
                padding: 2px 5px;
            }

            .ec-event.ec-preview,
            .ec-now-indicator {
                z-index: 30;
            }
        </style>

        @if($heading = $this->getHeading())
            <x-slot name="heading">
                {{ $this->getHeading() }}
            </x-slot>
        @endif

        @if ($this->isInDayView)
            <div class="mb-3 flex justify-end">
                <x-filament::button
                    wire:click="backToMonthView"
                    color="gray"
                    size="sm"
                    icon="heroicon-o-calendar-days"
                >
                    Lihat Semua Tanggal
                </x-filament::button>
            </div>
        @endif

        <div
            wire:ignore
            x-load
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('calendar', 'guava/calendar') }}"
            x-data="calendar({
                view: @js($this->getCalendarView()),
                locale: @js($this->getLocale()),
                firstDay: @js($this->getFirstDay()),
                dayMaxEvents: @js($this->getDayMaxEvents()),
                eventContent: @js($this->getEventContentJs()),
                eventClickEnabled: @js($this->isEventClickEnabled()),
                eventDragEnabled: @js($this->isEventDragEnabled()),
                eventResizeEnabled: @js($this->isEventResizeEnabled()),
                noEventsClickEnabled: @js($this->isNoEventsClickEnabled()),
                dateClickEnabled: @js($this->isDateClickEnabled()),
                dateSelectEnabled: @js($this->isDateSelectEnabled()),
                datesSetEnabled: @js($this->isDatesSetEnabled()),
                viewDidMountEnabled: @js($this->isViewDidMountEnabled()),
                eventAllUpdatedEnabled: @js($this->isEventAllUpdatedEnabled()),
                hasDateClickContextMenu: @js($this->hasContextMenu(Context::DateClick)),
                hasDateSelectContextMenu: @js($this->hasContextMenu(Context::DateSelect)),
                hasEventClickContextMenu: @js($this->hasContextMenu(Context::EventClick)),
                hasNoEventsClickContextMenu: @js($this->hasContextMenu(Context::NoEventsClick)),
                resources: @js($this->getResourcesJs()),
                resourceLabelContent: @js($this->getResourceLabelContentJs()),
                theme: @js($this->getTheme()),
                options: @js($this->getOptions()),
                eventAssetUrl: @js(FilamentAsset::getAlpineComponentSrc('calendar-event', 'guava/calendar')),
            })"
            @class(FilamentColor::getComponentClasses(ButtonComponent::class, 'primary'))
        >
            <div data-calendar></div>
            @if($this->hasContextMenu())
                <x-guava-calendar::context-menu/>
            @endif
        </div>
    </x-filament::section>
        <x-filament-actions::modals/>
</x-filament-widgets::widget>
