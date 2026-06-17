<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class IdentityController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['student_id' => 'nullable|string|max:20']);

        $uuid = $request->attributes->get('student_uuid');
        Student::where('uuid', $uuid)->update(['student_id' => $request->student_id ?: null]);

        return back()->with('status', 'ID tersimpan.');
    }
}
