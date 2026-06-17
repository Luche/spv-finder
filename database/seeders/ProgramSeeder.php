<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'Computer Science Program',
            'Mobile Application and Tech',
            'Game Application and Tech',
            'Cyber Security',
            'Data Science',
            'Software Engineering (CSSE)',
        ];

        foreach ($programs as $name) {
            Program::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
