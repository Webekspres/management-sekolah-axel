<?php

use App\Filament\Student\Widgets\SiswaOrtuOverviewStats;
use App\Filament\Student\Widgets\StudentAnnouncementListWidget;
use App\Filament\Student\Widgets\StudentAttendanceTrendChart;
use App\Models\Invoice;
use App\Models\Student;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('widget siswa menampilkan ringkasan tagihan, rapor, dan nilai', function () {
    $student = Student::factory()->create();

    Invoice::factory()->create([
        'student_id' => $student->id,
        'status' => 'UNPAID',
    ]);

    $this->actingAs($student->user);
    Filament::setCurrentPanel(Filament::getPanel('student'));

    Livewire::test(SiswaOrtuOverviewStats::class)
        ->assertSee('Tagihan Aktif')
        ->assertSee('Rapor Siap Diunduh')
        ->assertSee('Rata-rata RAPOR')
        ->assertSee('Di Bawah KKM')
        ->assertSee('1');

    Livewire::test(StudentAttendanceTrendChart::class)->assertSuccessful();

    Livewire::test(StudentAnnouncementListWidget::class)->assertSuccessful();
});
