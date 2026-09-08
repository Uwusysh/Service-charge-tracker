<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Building;
use App\Models\Client;
use App\Models\CostHeading;
use App\Models\Invoice;
use App\Models\Schedule;
use App\Models\ScheduleUnit;
use App\Models\ServiceChargeYear;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ApportionmentService;
use App\Services\BudgetCalculationService;
use Illuminate\Database\Seeder;

class SamplePortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $headings = CostHeading::query()->get()->keyBy('code');
        $apportionment = app(ApportionmentService::class);
        $calc = app(BudgetCalculationService::class);

        $admin = User::query()->where('email', 'admin@setk.test')->first();
        $accountant = User::query()->where('email', 'accountant@setk.test')->first();

        $mapleClient = Client::query()->create([
            'name' => 'Maple Court RMC Ltd',
            'type' => 'rmc',
            'company_details' => 'Company number 12345678',
            'accountant_details' => 'Year-end 31 December',
            'is_active' => true,
        ]);

        $riversideClient = Client::query()->create([
            'name' => 'Riverside Freehold Ltd',
            'type' => 'freeholder',
            'company_details' => 'Company number 23456789',
            'is_active' => true,
        ]);

        $oakClient = Client::query()->create([
            'name' => 'Oak View RTM Company Ltd',
            'type' => 'rtm',
            'company_details' => 'Company number 34567890',
            'is_active' => true,
        ]);

        $maple = $this->createBuildingWithUnits($mapleClient, 'Maple Court', 'MC-001', '12 Maple Avenue', 'London', 'SW1A 1AA', 10);
        $riverside = $this->createBuildingWithUnits($riversideClient, 'Riverside House', 'RH-001', '5 River Road', 'Manchester', 'M1 1AE', 6);
        $oak = $this->createBuildingWithUnits($oakClient, 'Oak View', 'OV-001', '8 Oak Lane', 'Bristol', 'BS1 4DJ', 4);

        $mapleYear = ServiceChargeYear::query()->create([
            'building_id' => $maple->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
            'label' => 'SCY 2027',
        ]);

        ServiceChargeYear::query()->create([
            'building_id' => $riverside->id,
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
            'status' => 'active',
            'label' => 'SCY 2027/28',
        ]);

        ServiceChargeYear::query()->create([
            'building_id' => $oak->id,
            'start_date' => '2027-03-25',
            'end_date' => '2028-03-24',
            'status' => 'active',
            'label' => 'SCY 2027/28',
        ]);

        $mapleEstate = $this->createEqualSchedule($maple, 'Estate', $apportionment);
        $mapleBlock = $this->createEqualSchedule($maple, 'Block', $apportionment);
        $mapleLift = $this->createEqualSchedule($maple, 'Lift', $apportionment, $maple->units->take(6));

        $this->createEqualSchedule($riverside, 'Block', $apportionment);
        $this->createEqualSchedule($oak, 'Estate', $apportionment);
        $this->createEqualSchedule($oak, 'Block', $apportionment);

        $budget = Budget::query()->create([
            'building_id' => $maple->id,
            'service_charge_year_id' => $mapleYear->id,
            'title' => 'Maple Court Service Charge Budget 2027',
            'status' => Budget::STATUS_READY,
            'prepared_by' => $admin?->id,
            'prepared_at' => now()->subDays(5),
            'authorised_approver' => 'Jane Director',
            'authorised_at' => now()->subDays(2),
            'authorised_capacity' => 'RMC Director',
            'overall_notes' => 'Sample Final budget for POC demonstration.',
            'version' => 1,
        ]);

        $lineDefs = [
            [$mapleEstate->id, 'GRD', '8000.00', false],
            [$mapleEstate->id, 'CON', '2000.00', false],
            [$mapleBlock->id, 'INS', '20000.00', false],
            [$mapleBlock->id, 'CLN', '12000.00', false],
            [$mapleBlock->id, 'UTL', '15000.00', false],
            [$mapleBlock->id, 'REP', '10000.00', false],
            [$mapleBlock->id, 'FIR', '5000.00', false],
            [$mapleBlock->id, 'STA', '8000.00', false],
            [$mapleBlock->id, 'MGT', '10000.00', false],
            [$mapleBlock->id, 'ACC', '3000.00', false],
            [$mapleBlock->id, 'LEG', '2000.00', false],
            [$mapleBlock->id, 'RSV', '2000.00', true],
            [$mapleLift->id, 'LFT', '3000.00', false],
        ];

        $linesByCode = [];
        foreach ($lineDefs as [$scheduleId, $code, $amount, $isReserve]) {
            $line = BudgetLine::query()->create([
                'budget_id' => $budget->id,
                'schedule_id' => $scheduleId,
                'cost_heading_id' => $headings[$code]->id,
                'description' => $headings[$code]->name,
                'previous_budget' => null,
                'previous_actual' => null,
                'current_estimate' => $amount,
                'is_reserve' => $isReserve,
                'basis_explanation' => null,
            ]);
            $linesByCode[$code] = $line;
        }

        $calc->freezeUnitTotals($budget->fresh('lines'));
        $budget->update([
            'status' => Budget::STATUS_FINAL,
            'finalised_by' => $accountant?->id ?? $admin?->id,
            'finalised_at' => now()->subDay(),
        ]);

        $insurer = Supplier::query()->create(['name' => 'Britannia Buildings Insurance', 'is_active' => true]);
        $cleaner = Supplier::query()->create(['name' => 'Sparkle Communal Cleaning', 'is_active' => true]);
        $repairs = Supplier::query()->create(['name' => 'Metro Maintenance Ltd', 'is_active' => true]);

        Invoice::query()->create([
            'building_id' => $maple->id,
            'service_charge_year_id' => $mapleYear->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $linesByCode['INS']->id,
            'supplier_id' => $insurer->id,
            'invoice_reference' => 'INS-2027-001',
            'normalized_reference' => 'ins-2027-001',
            'invoice_date' => '2027-02-01',
            'net_amount' => '15000.00',
            'vat_amount' => '3000.00',
            'gross_amount' => '18000.00',
            'status' => Invoice::STATUS_APPROVED,
            'description' => 'Buildings insurance premium',
            'submitted_by' => $admin?->id,
            'submitted_at' => now()->subDays(10),
            'reviewed_by' => $accountant?->id,
            'reviewed_at' => now()->subDays(9),
        ]);

        Invoice::query()->create([
            'building_id' => $maple->id,
            'service_charge_year_id' => $mapleYear->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $linesByCode['CLN']->id,
            'supplier_id' => $cleaner->id,
            'invoice_reference' => 'CLN-2027-Q1',
            'normalized_reference' => 'cln-2027-q1',
            'invoice_date' => '2027-03-15',
            'net_amount' => '5000.00',
            'vat_amount' => '1000.00',
            'gross_amount' => '6000.00',
            'status' => Invoice::STATUS_APPROVED,
            'description' => 'Q1 cleaning',
            'submitted_by' => $admin?->id,
            'submitted_at' => now()->subDays(8),
            'reviewed_by' => $accountant?->id,
            'reviewed_at' => now()->subDays(7),
        ]);

        Invoice::query()->create([
            'building_id' => $maple->id,
            'service_charge_year_id' => $mapleYear->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $linesByCode['REP']->id,
            'supplier_id' => $repairs->id,
            'invoice_reference' => 'REP-2027-014',
            'normalized_reference' => 'rep-2027-014',
            'invoice_date' => '2027-04-10',
            'net_amount' => '4166.67',
            'vat_amount' => '833.33',
            'gross_amount' => '5000.00',
            'status' => Invoice::STATUS_SUBMITTED,
            'description' => 'Communal repairs',
            'submitted_by' => $admin?->id,
            'submitted_at' => now()->subDay(),
        ]);
    }

    private function createBuildingWithUnits(
        Client $client,
        string $name,
        string $reference,
        string $address,
        string $city,
        string $postcode,
        int $unitCount
    ): Building {
        $building = Building::query()->create([
            'client_id' => $client->id,
            'name' => $name,
            'address_line1' => $address,
            'city' => $city,
            'postcode' => $postcode,
            'reference' => $reference,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $unitCount; $i++) {
            Unit::query()->create([
                'building_id' => $building->id,
                'unit_reference' => 'Flat '.$i,
                'description' => 'Flat '.$i,
                'is_active' => true,
                'active_from' => '2027-01-01',
            ]);
        }

        return $building->fresh('units');
    }

    private function createEqualSchedule(
        Building $building,
        string $name,
        ApportionmentService $apportionment,
        $units = null
    ): Schedule {
        $units = $units ?? $building->units;
        $balancing = $units->last();

        $schedule = Schedule::query()->create([
            'building_id' => $building->id,
            'name' => $name,
            'allocation_method' => 'equal',
            'balancing_unit_id' => $balancing->id,
        ]);

        foreach ($units as $unit) {
            ScheduleUnit::query()->create([
                'schedule_id' => $schedule->id,
                'unit_id' => $unit->id,
                'percentage' => '0.000000',
            ]);
        }

        $apportionment->applyEqualShares($schedule->fresh('scheduleUnits'));

        return $schedule->fresh('scheduleUnits');
    }
}
