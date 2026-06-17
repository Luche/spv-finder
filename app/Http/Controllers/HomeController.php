<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $programs = Program::with('topics')->get();

        $supervisors = Supervisor::with(['programs', 'topics'])
            ->withCount([
                'pageViews as views_30'   => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
                'contacts as contacts_30' => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('home', compact('programs', 'supervisors'));
    }
}
