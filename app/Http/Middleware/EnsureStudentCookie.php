<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->cookie('student_uuid');

        if (!$uuid) {
            $uuid = (string) Str::uuid();
        }

        // Ensure row exists
        Student::firstOrCreate(['uuid' => $uuid]);

        $request->attributes->set('student_uuid', $uuid);

        $response = $next($request);

        // Set/refresh cookie for 1 year
        $response->cookie('student_uuid', $uuid, 60 * 24 * 365, '/', null, false, true);

        return $response;
    }
}
