<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\PageView;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function show(Request $request, string $kddsn)
    {
        $supervisor = Supervisor::with(['programs', 'topics', 'theses'])
            ->where('kddsn', $kddsn)
            ->firstOrFail();

        $uuid = $request->attributes->get('student_uuid');

        // Record page view — once per student per supervisor per 30-day window
        $recentView = PageView::where('supervisor_id', $supervisor->id)
            ->where('student_uuid', $uuid)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if (!$recentView) {
            PageView::create([
                'supervisor_id' => $supervisor->id,
                'student_uuid'  => $uuid,
                'view_date'     => today(),
                'created_at'    => now(),
            ]);
            $supervisor->increment('views_total');
        }

        $views30    = $supervisor->pageViews()->where('created_at', '>=', now()->subDays(30))->count();
        $contacts30 = $supervisor->contacts()->where('created_at', '>=', now()->subDays(30))->count();

        return view('supervisor', compact('supervisor', 'views30', 'contacts30'));
    }

    public function contact(Request $request, string $kddsn)
    {
        $supervisor = Supervisor::where('kddsn', $kddsn)->firstOrFail();
        $uuid = $request->attributes->get('student_uuid');

        $recentContact = Contact::where('supervisor_id', $supervisor->id)
            ->where('student_uuid', $uuid)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        if (!$recentContact) {
            Contact::create([
                'supervisor_id' => $supervisor->id,
                'student_uuid'  => $uuid,
                'created_at'    => now(),
            ]);
            $supervisor->increment('contacts_total');
        }

        $subject = rawurlencode('Permohonan Bimbingan Skripsi');
        $body    = rawurlencode(
            "Yth. Bapak/Ibu {$supervisor->name},\n\n"
            . "Perkenalkan, saya:\n"
            . "Nama   : [Nama Lengkap]\n"
            . "NIM    : [NIM]\n"
            . "Program: [Nama Program]\n\n"
            . "Saya bermaksud mengajukan permohonan bimbingan skripsi dengan topik:\n"
            . "[Topik skripsi yang diminati]\n\n"
            . "Secara singkat, ide saya adalah:\n"
            . "[Ringkasan ide singkat — 2-3 kalimat]\n\n"
            . "Saya sangat berharap dapat bimbingan dari Bapak/Ibu. "
            . "Apakah Bapak/Ibu bersedia meluangkan waktu untuk berdiskusi?\n\n"
            . "Terima kasih banyak atas perhatian Bapak/Ibu.\n\n"
            . "Hormat saya,\n"
            . "[Nama Lengkap]"
        );

        $mailto = "mailto:{$supervisor->email}?subject={$subject}&body={$body}";

        return response()->json(['mailto' => $mailto]);
    }
}
