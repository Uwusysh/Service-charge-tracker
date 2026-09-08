<?php

namespace Database\Seeders;

use App\Models\CostHeading;
use Illuminate\Database\Seeder;

class CostHeadingSeeder extends Seeder
{
    public function run(): void
    {
        $headings = [
            ['code' => 'INS', 'name' => 'Insurance', 'sort_order' => 10],
            ['code' => 'CLN', 'name' => 'Cleaning', 'sort_order' => 20],
            ['code' => 'UTL', 'name' => 'Utilities', 'sort_order' => 30],
            ['code' => 'GRD', 'name' => 'Grounds maintenance', 'sort_order' => 40],
            ['code' => 'REP', 'name' => 'Repairs & maintenance', 'sort_order' => 50],
            ['code' => 'LFT', 'name' => 'Lift', 'sort_order' => 60],
            ['code' => 'FIR', 'name' => 'Fire safety', 'sort_order' => 70],
            ['code' => 'STA', 'name' => 'Staff', 'sort_order' => 80],
            ['code' => 'MGT', 'name' => 'Management fees', 'sort_order' => 90],
            ['code' => 'ACC', 'name' => 'Accountancy', 'sort_order' => 100],
            ['code' => 'LEG', 'name' => 'Legal', 'sort_order' => 110],
            ['code' => 'CON', 'name' => 'Contingency', 'sort_order' => 120],
            ['code' => 'RSV', 'name' => 'Reserve fund', 'sort_order' => 130],
            ['code' => 'OTH', 'name' => 'Other', 'sort_order' => 140],
        ];

        foreach ($headings as $heading) {
            CostHeading::query()->updateOrCreate(
                ['code' => $heading['code']],
                $heading
            );
        }
    }
}
