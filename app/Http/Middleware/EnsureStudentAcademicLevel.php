<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\DashboardAcademicContext;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentAcademicLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if ($user?->effectiveRole() === 'siswa_ortu') {
            $studentLevelId = $user->resolveStudentAcademicLevelId();

            if ($studentLevelId) {
                session(['active_academic_level_id' => $studentLevelId]);
            } else {
                session()->forget('active_academic_level_id');
            }

            DashboardAcademicContext::resetCache();
        }

        return $next($request);
    }
}
