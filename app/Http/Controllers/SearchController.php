<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $program = $request->input('program');
        $topic   = $request->input('topic');

        // Parse multi-sort: "views:desc,contacts:asc" → ordered list
        $validFields = ['views', 'contacts', 'active', 'alpha', 'relevance'];
        $sorts = collect(explode(',', $request->input('sorts', 'alpha:asc')))
            ->map(fn($s) => array_pad(explode(':', trim($s)), 2, 'desc'))
            ->filter(fn($s) => in_array($s[0], $validFields))
            ->map(fn($s) => ['field' => $s[0], 'dir' => $s[1] === 'asc' ? 'asc' : 'desc'])
            ->values();

        if ($sorts->isEmpty()) {
            $sorts = collect([['field' => 'views', 'dir' => 'desc']]);
        }

        $query = Supervisor::with(['programs', 'topics'])
            ->withCount([
                'pageViews as views_30'   => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
                'contacts as contacts_30' => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
            ]);

        $usedFulltext = false;
        if ($q !== '') {
            if (mb_strlen($q) >= 3) {
                $words = preg_split('/\s+/', $q);
                $boolQ = implode('* ', array_map(fn($w) => "+{$w}", $words)) . '*';

                $query->selectRaw(
                    'supervisors.*,
                     MATCH(supervisors.name, supervisors.specific_topics) AGAINST(? IN BOOLEAN MODE) AS name_score,
                     (SELECT COALESCE(MAX(MATCH(t.title) AGAINST(? IN BOOLEAN MODE)),0)
                      FROM theses t WHERE t.supervisor_id = supervisors.id) AS title_score',
                    [$boolQ, $boolQ]
                )
                ->whereRaw(
                    '(MATCH(supervisors.name, supervisors.specific_topics) AGAINST(? IN BOOLEAN MODE) > 0
                     OR EXISTS (
                         SELECT 1 FROM theses t2
                         WHERE t2.supervisor_id = supervisors.id
                         AND MATCH(t2.title) AGAINST(? IN BOOLEAN MODE) > 0
                     ))',
                    [$boolQ, $boolQ]
                );
                $usedFulltext = true;
            } else {
                $like = "%{$q}%";
                $query->where(function ($q2) use ($like) {
                    $q2->where('supervisors.name', 'like', $like)
                        ->orWhereHas('theses', fn($t) => $t->where('title', 'like', $like));
                });
            }
        }

        if ($program === 'global-class') {
            $query->where('supervisors.is_global_class', true);
        } elseif ($program) {
            $query->whereHas('programs', fn($p) => $p->where('slug', $program));
        }

        if ($topic) {
            $query->whereHas('topics', fn($t) => $t->where('slug', $topic));
        }

        foreach ($sorts as $s) {
            match ($s['field']) {
                'relevance' => $usedFulltext ? $query->orderByRaw('(name_score + title_score) DESC') : null,
                'contacts'  => $query->orderBy('contacts_30', $s['dir']),
                'active'    => $query->orderBy('supervisors.active_titles', $s['dir']),
                'alpha'     => $query->orderBy('supervisors.name', $s['dir']),
                default     => $query->orderBy('views_30', $s['dir']),
            };
        }

        $supervisors = $query->paginate(12)->withQueryString();

        // Fuzzy fallback: if FULLTEXT returned nothing, retry with similarity matching
        if ($usedFulltext && $supervisors->total() === 0) {
            $fuzzyIds = $this->fuzzyIds($q);
            if (!empty($fuzzyIds)) {
                $supervisors = Supervisor::with(['programs', 'topics'])
                    ->withCount([
                        'pageViews as views_30'   => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
                        'contacts as contacts_30' => fn($q) => $q->where('created_at', '>=', now()->subDays(30)),
                    ])
                    ->whereIn('supervisors.id', $fuzzyIds)
                    ->when($program === 'global-class', fn($q) => $q->where('supervisors.is_global_class', true))
                    ->when($program && $program !== 'global-class', fn($q) => $q->whereHas('programs', fn($p) => $p->where('slug', $program)))
                    ->when($topic, fn($q) => $q->whereHas('topics', fn($t) => $t->where('slug', $topic)))
                    ->orderBy('supervisors.name')
                    ->paginate(12)
                    ->withQueryString();
            }
        }

        if ($request->header('HX-Request')) {
            return view('partials.supervisor-cards', compact('supervisors'));
        }

        $programs = \App\Models\Program::with('topics')->get();
        return view('home', compact('supervisors', 'programs'));
    }

    private function fuzzyIds(string $q): array
    {
        $queryWords = preg_split('/\s+/', mb_strtolower(trim($q)));

        $rows = Supervisor::with('theses:id,supervisor_id,title')
            ->select(['id', 'name', 'specific_topics'])
            ->get();

        $threshold = 65;
        $matched   = [];

        foreach ($rows as $supervisor) {
            $blob = mb_strtolower(implode(' ', array_filter([
                $supervisor->name,
                $supervisor->specific_topics,
                $supervisor->theses->pluck('title')->implode(' '),
            ])));

            $blobWords = preg_split('/[\s;,]+/', $blob);

            $score = 0;
            foreach ($queryWords as $qw) {
                $best = 0;
                foreach ($blobWords as $bw) {
                    similar_text($qw, $bw, $pct);
                    if ($pct > $best) $best = $pct;
                    // Also check if query word is a substring (handles merged words)
                    if (str_contains($bw, $qw) || str_contains($qw, $bw)) {
                        $best = max($best, 80.0);
                    }
                }
                $score += $best;
            }

            $avgScore = $score / count($queryWords);
            if ($avgScore >= $threshold) {
                $matched[$supervisor->id] = $avgScore;
            }
        }

        arsort($matched);
        return array_keys($matched);
    }
}
