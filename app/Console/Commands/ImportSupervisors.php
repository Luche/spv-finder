<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\Supervisor;
use App\Models\Thesis;
use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportSupervisors extends Command
{
    protected $signature   = 'import:supervisors {--path= : Path to xlsx (default: storage/app/supervisors.xlsx)}';
    protected $description = 'Import supervisors, topics and programs from the Excel workbook';

    // Topic code → name map for the CS sheet
    private const CS_CODES = [
        '00012' => 'Sisfo Geografi',
        '00016' => 'Intelegensia Semu',
        '00020' => 'Data Mining',
        '00030' => 'Jaringan',
        '00070' => 'E-Application',
        '00102' => 'Aplikasi Database',
        '00151' => 'Aplikasi Multimedia & Game',
        '00203' => 'Mobile Application',
        '00204' => 'Augmented Reality',
    ];

    public function handle(): int
    {
        $path = $this->option('path')
            ?? storage_path('app/supervisors.xlsx');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $this->info("Loading workbook...");
        $spreadsheet = IOFactory::load($path);

        // --- Admin sheet (must process first so UPSERTs below can use data) ---
        $adminData = [];
        if ($spreadsheet->sheetNameExists('Admin')) {
            $sheet = $spreadsheet->getSheetByName('Admin');
            $adminData = $this->readAdminSheet($sheet);
            $this->info('Admin sheet loaded: ' . count($adminData) . ' rows');
        } else {
            $this->warn('No "Admin" sheet found — skipping manual data (scholar URLs, active titles, theses).');
        }

        // --- Program sheets ---
        $programSheets = [
            'Computer Science Program',
            'Mobile Application and Tech',
            'Game Application and Tech',
            'Cyber Security',
            'Data Science',
            'Software Engineering (CSSE)',
        ];

        foreach ($programSheets as $sheetName) {
            if (! $spreadsheet->sheetNameExists($sheetName)) {
                $this->warn("Sheet not found: {$sheetName}");
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);

            if ($sheetName === 'Computer Science Program') {
                $this->importCsSheet($sheet, $adminData);
            } else {
                $this->importStandardSheet($sheet, $sheetName, $adminData);
            }

            $this->info("Imported: {$sheetName}");
        }

        // Apply admin data to any supervisors not yet touched (e.g. no program sheet match)
        foreach ($adminData as $kddsn => $data) {
            $sup = Supervisor::where('kddsn', $kddsn)->first();
            if ($sup) {
                $this->applyAdminData($sup, $data);
            }
        }

        $this->info('Done.');
        return 0;
    }

    // -----------------------------------------------------------------------
    // CS sheet: topic headers are codes in row 10, data from row 11
    // -----------------------------------------------------------------------
    private function importCsSheet($sheet, array $adminData): void
    {
        $program = Program::where('slug', Str::slug('Computer Science Program'))->firstOrFail();

        // Row 10 → topic codes per column (cols 5-13), col 14 = Global Class
        $topicCols = [];   // col => topic model
        for ($col = 5; $col <= 13; $col++) {
            $code = trim((string) ($sheet->getCell([$col, 10])->getValue() ?? ''));
            if ($code && isset(self::CS_CODES[$code])) {
                $name = self::CS_CODES[$code];
                $topicCols[$col] = Topic::firstOrCreate(
                    ['program_id' => $program->id, 'slug' => Str::slug($name)],
                    ['code' => $code, 'name' => $name]
                );
            }
        }
        $globalClassCol = 14;

        // Data rows start at 11
        for ($row = 11; $row <= $sheet->getHighestRow(); $row++) {
            $kddsn = trim((string) ($sheet->getCell([2, $row])->getValue() ?? ''));
            $name  = trim((string) ($sheet->getCell([3, $row])->getValue() ?? ''));
            $email = strtolower(trim((string) ($sheet->getCell([4, $row])->getValue() ?? '')));

            if (! $kddsn || ! $name) {
                continue;
            }

            $isGlobal = strtoupper(trim((string) ($sheet->getCell([$globalClassCol, $row])->getValue() ?? ''))) === 'Y';

            $supervisor = Supervisor::updateOrCreate(
                ['kddsn' => $kddsn],
                ['name' => $name, 'email' => $email, 'is_global_class' => $isGlobal]
            );

            $supervisor->programs()->syncWithoutDetaching([$program->id]);

            foreach ($topicCols as $col => $topic) {
                $val = strtoupper(trim((string) ($sheet->getCell([$col, $row])->getValue() ?? '')));
                if ($val === 'Y') {
                    $supervisor->topics()->syncWithoutDetaching([$topic->id]);
                }
            }

            if (isset($adminData[$kddsn])) {
                $this->applyAdminData($supervisor, $adminData[$kddsn]);
                unset($adminData[$kddsn]); // mark as applied
            }
        }
    }

    // -----------------------------------------------------------------------
    // Standard sheet: topic headers in row 5 (named), data from row 6
    // -----------------------------------------------------------------------
    private function importStandardSheet($sheet, string $sheetName, array &$adminData): void
    {
        $program = Program::where('slug', Str::slug($sheetName))->firstOrFail();

        $highestCol = $this->getHighestColumnIndex($sheet, 5);

        // Row 5: topic names starting at col 5
        $topicCols = [];
        for ($col = 5; $col <= $highestCol; $col++) {
            $topicName = trim((string) ($sheet->getCell([$col, 5])->getValue() ?? ''));
            if ($topicName) {
                $topicCols[$col] = Topic::firstOrCreate(
                    ['program_id' => $program->id, 'slug' => Str::slug($topicName)],
                    ['name' => $topicName]
                );
            }
        }

        // Data rows start at 6
        for ($row = 6; $row <= $sheet->getHighestRow(); $row++) {
            $kddsn = trim((string) ($sheet->getCell([2, $row])->getValue() ?? ''));
            $name  = trim((string) ($sheet->getCell([3, $row])->getValue() ?? ''));
            $email = strtolower(trim((string) ($sheet->getCell([4, $row])->getValue() ?? '')));

            if (! $kddsn || ! $name) {
                continue;
            }

            $supervisor = Supervisor::updateOrCreate(
                ['kddsn' => $kddsn],
                // Don't overwrite name/email if already set (CS sheet may have canonical version)
                array_filter(['name' => $name, 'email' => $email])
            );

            $supervisor->programs()->syncWithoutDetaching([$program->id]);

            foreach ($topicCols as $col => $topic) {
                $val = strtoupper(trim((string) ($sheet->getCell([$col, $row])->getValue() ?? '')));
                if ($val === 'Y') {
                    $supervisor->topics()->syncWithoutDetaching([$topic->id]);
                }
            }

            if (isset($adminData[$kddsn])) {
                $this->applyAdminData($supervisor, $adminData[$kddsn]);
                unset($adminData[$kddsn]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // Admin sheet: Kddsn | ActiveTitles | ScholarURL | SpecificTopics | Title1..5
    // -----------------------------------------------------------------------
    private function readAdminSheet($sheet): array
    {
        $data = [];
        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $kddsn = trim((string) ($sheet->getCell([1, $row])->getValue() ?? ''));
            if (! $kddsn) {
                continue;
            }
            $data[$kddsn] = [
                'active_titles'   => (int) ($sheet->getCell([2, $row])->getValue() ?? 0),
                'scholar_url'     => trim((string) ($sheet->getCell([3, $row])->getValue() ?? '')),
                'specific_topics' => trim((string) ($sheet->getCell([4, $row])->getValue() ?? '')),
                'titles'          => array_filter([
                    trim((string) ($sheet->getCell([5, $row])->getValue() ?? '')),
                    trim((string) ($sheet->getCell([6, $row])->getValue() ?? '')),
                    trim((string) ($sheet->getCell([7, $row])->getValue() ?? '')),
                    trim((string) ($sheet->getCell([8, $row])->getValue() ?? '')),
                    trim((string) ($sheet->getCell([9, $row])->getValue() ?? '')),
                ]),
            ];
        }
        return $data;
    }

    private function applyAdminData(Supervisor $supervisor, array $data): void
    {
        $supervisor->update([
            'active_titles'   => $data['active_titles'],
            'scholar_url'     => $data['scholar_url'] ?: null,
            'specific_topics' => $data['specific_topics'] ?: null,
        ]);

        // Replace theses for this supervisor
        $supervisor->theses()->delete();
        foreach (array_values($data['titles']) as $i => $title) {
            Thesis::create([
                'supervisor_id' => $supervisor->id,
                'title'         => $title,
                'position'      => $i + 1,
            ]);
        }
    }

    // PhpSpreadsheet's getHighestColumn returns a letter; convert
    private function getHighestColumnIndex($sheet, int $row): int
    {
        $highest = 'A';
        for ($col = 1; $col <= 20; $col++) {
            $val = $sheet->getCell([$col, $row])->getValue();
            if ($val !== null && $val !== '') {
                $highest = $col;
            }
        }
        return is_int($highest) ? $highest : 20;
    }
}
