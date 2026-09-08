<?php

namespace Tests\Feature;

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
use App\Services\BudgetMonitorService;
use App\Services\InvoiceService;
use App\Support\Money;
use Database\Seeders\CostHeadingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptanceTests extends TestCase
{
    use RefreshDatabase;

    private function seedHeadings(): void
    {
        $this->seed(CostHeadingSeeder::class);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    /**
     * @return array{building: Building, year: ServiceChargeYear, schedule: Schedule, units: \Illuminate\Support\Collection<int, Unit>}
     */
    private function makeBuildingPortfolio(int $unitCount = 3): array
    {
        $client = Client::query()->create([
            'name' => 'Test RMC',
            'type' => 'rmc',
            'is_active' => true,
        ]);

        $building = Building::query()->create([
            'client_id' => $client->id,
            'name' => 'Test Court',
            'address_line1' => '1 Test Street',
            'city' => 'London',
            'postcode' => 'E1 1AA',
            'reference' => 'TST-'.uniqid(),
            'is_active' => true,
        ]);

        $units = collect();
        for ($i = 1; $i <= $unitCount; $i++) {
            $units->push(Unit::query()->create([
                'building_id' => $building->id,
                'unit_reference' => 'Flat '.$i,
                'is_active' => true,
            ]));
        }

        $year = ServiceChargeYear::query()->create([
            'building_id' => $building->id,
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
            'status' => 'active',
            'label' => '2027',
        ]);

        $schedule = Schedule::query()->create([
            'building_id' => $building->id,
            'name' => 'Block',
            'allocation_method' => 'equal',
            'balancing_unit_id' => $units->last()->id,
        ]);

        foreach ($units as $unit) {
            ScheduleUnit::query()->create([
                'schedule_id' => $schedule->id,
                'unit_id' => $unit->id,
                'percentage' => '0.000000',
            ]);
        }

        app(ApportionmentService::class)->applyEqualShares($schedule->fresh('scheduleUnits'));

        return compact('building', 'year', 'schedule', 'units');
    }

    /** AT-01: Role helpers and middleware gatekeeping */
    public function test_at01_role_helpers_and_access_control(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMINISTRATOR);
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $accountant = $this->makeUser(User::ROLE_ACCOUNTANT);

        $this->assertTrue($admin->isAdministrator());
        $this->assertTrue($admin->canFinaliseBudget());
        $this->assertTrue($admin->canApproveInvoice());

        $this->assertTrue($manager->isBlockManager());
        $this->assertFalse($manager->canFinaliseBudget());
        $this->assertFalse($manager->canApproveInvoice());

        $this->assertTrue($accountant->isAccountant());
        $this->assertTrue($accountant->canFinaliseBudget());
        $this->assertTrue($accountant->canApproveInvoice());

        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($accountant)->post(route('budgets.finalise', 1))->assertNotFound();
    }

    /** AT-02: Equal shares total exactly 100% to 6dp; manual does not invent % */
    public function test_at02_apportionment_equal_and_manual_validation(): void
    {
        $portfolio = $this->makeBuildingPortfolio(3);
        $schedule = $portfolio['schedule']->fresh('scheduleUnits');
        $service = app(ApportionmentService::class);

        $check = $service->validateScheduleTotals($schedule);
        $this->assertTrue($check['valid']);
        $this->assertSame('100.000000', $check['total']);

        $service->syncManualPercentages($schedule, [
            ['unit_id' => $portfolio['units'][0]->id, 'percentage' => '40'],
            ['unit_id' => $portfolio['units'][1]->id, 'percentage' => '40'],
            ['unit_id' => $portfolio['units'][2]->id, 'percentage' => '10'],
        ]);

        $invalid = $service->validateScheduleTotals($schedule->fresh('scheduleUnits'));
        $this->assertFalse($invalid['valid']);
        $this->assertSame('90.000000', $invalid['total']);
    }

    /** AT-03: Draft budget create + line amounts stored as decimal strings */
    public function test_at03_draft_budget_creation_and_totals(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $heading = CostHeading::query()->where('code', 'INS')->firstOrFail();

        $this->actingAs($manager)->post(route('budgets.store'), [
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Draft 2027',
            'overall_notes' => 'Test',
        ])->assertRedirect();

        $budget = Budget::query()->firstOrFail();
        $this->assertSame(Budget::STATUS_DRAFT, $budget->status);

        $this->actingAs($manager)->post(route('budget-lines.store', $budget), [
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '1000.50',
            'is_reserve' => 0,
        ])->assertRedirect();

        $this->assertSame('1000.50', Money::normalize((string) $budget->fresh()->lines()->first()->current_estimate));
        $this->assertSame('1000.50', app(BudgetCalculationService::class)->buildingBudgetTotal($budget->fresh()));
    }

    /** AT-04: Mark ready for review */
    public function test_at04_mark_ready_for_review(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $heading = CostHeading::query()->firstOrFail();

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Ready test',
            'status' => Budget::STATUS_DRAFT,
            'prepared_by' => $manager->id,
            'prepared_at' => now(),
        ]);

        BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '500.00',
            'is_reserve' => false,
        ]);

        $this->actingAs($manager)->post(route('budgets.mark-ready', $budget))->assertRedirect();
        $this->assertSame(Budget::STATUS_READY, $budget->fresh()->status);
    }

    /** AT-05: Finalise freezes unit totals with balancing remainder */
    public function test_at05_finalise_freezes_contributions_with_balancing_unit(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio(3);
        $accountant = $this->makeUser(User::ROLE_ACCOUNTANT);
        $heading = CostHeading::query()->firstOrFail();

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Finalise test',
            'status' => Budget::STATUS_READY,
            'prepared_by' => $accountant->id,
            'prepared_at' => now(),
            'authorised_approver' => 'Director',
            'authorised_at' => now(),
            'authorised_capacity' => 'Director',
        ]);

        BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '100.00',
            'is_reserve' => false,
        ]);

        $this->actingAs($accountant)->post(route('budgets.finalise', $budget))->assertRedirect();

        $budget->refresh();
        $this->assertSame(Budget::STATUS_FINAL, $budget->status);
        $this->assertCount(3, $budget->unitTotals);
        $sum = Money::normalize((string) $budget->unitTotals()->sum('contribution'));
        $this->assertSame('100.00', $sum);
    }

    /** AT-06: Validate for final requires approval header and 100% schedules */
    public function test_at06_validate_for_final_errors(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $heading = CostHeading::query()->firstOrFail();

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Invalid final',
            'status' => Budget::STATUS_READY,
        ]);

        $result = app(BudgetCalculationService::class)->validateForFinal($budget);
        $this->assertNotEmpty($result['errors']);

        BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '10.00',
            'previous_budget' => '5.00',
            'is_reserve' => false,
            'basis_explanation' => null,
        ]);

        $budget->update([
            'authorised_approver' => 'A',
            'authorised_at' => now(),
            'authorised_capacity' => 'Director',
        ]);

        $withWarning = app(BudgetCalculationService::class)->validateForFinal($budget->fresh('lines'));
        $this->assertEmpty($withWarning['errors']);
        $this->assertNotEmpty($withWarning['warnings']);
    }

    /** AT-07: Only Final budgets receive invoices */
    public function test_at07_invoices_only_against_final_budgets(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $heading = CostHeading::query()->firstOrFail();
        $supplier = Supplier::query()->create(['name' => 'Supplier A', 'is_active' => true]);

        $draft = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Draft',
            'status' => Budget::STATUS_DRAFT,
        ]);

        $line = BudgetLine::query()->create([
            'budget_id' => $draft->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '100.00',
            'is_reserve' => false,
        ]);

        $this->actingAs($manager)->post(route('invoices.store'), [
            'budget_id' => $draft->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'INV-1',
            'invoice_date' => '2027-02-01',
            'net_amount' => '10.00',
            'vat_amount' => '2.00',
        ])->assertSessionHasErrors('budget_id');
    }

    /** AT-08: Duplicate invoice reference check + approve flow */
    public function test_at08_invoice_duplicate_and_approval(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $accountant = $this->makeUser(User::ROLE_ACCOUNTANT);
        $heading = CostHeading::query()->firstOrFail();
        $supplier = Supplier::query()->create(['name' => 'Supplier B', 'is_active' => true]);

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Final',
            'status' => Budget::STATUS_FINAL,
            'authorised_approver' => 'A',
            'authorised_at' => now(),
            'authorised_capacity' => 'Director',
            'finalised_at' => now(),
        ]);

        $line = BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '1000.00',
            'is_reserve' => false,
        ]);

        $invoice = Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'ABC-100',
            'normalized_reference' => 'abc-100',
            'invoice_date' => '2027-03-01',
            'net_amount' => '100.00',
            'vat_amount' => '20.00',
            'gross_amount' => '120.00',
            'status' => Invoice::STATUS_DRAFT,
            'submitted_by' => $manager->id,
        ]);

        $service = app(InvoiceService::class);
        $service->submit($invoice, $manager);
        $this->assertSame(Invoice::STATUS_SUBMITTED, $invoice->fresh()->status);

        $this->actingAs($accountant)->post(route('invoices.approve', $invoice))->assertRedirect();
        $this->assertSame(Invoice::STATUS_APPROVED, $invoice->fresh()->status);

        $dupe = Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => ' abc-100 ',
            'normalized_reference' => Invoice::normalizeReference(' abc-100 '),
            'invoice_date' => '2027-03-02',
            'net_amount' => '50.00',
            'vat_amount' => '10.00',
            'gross_amount' => '60.00',
            'status' => Invoice::STATUS_DRAFT,
            'submitted_by' => $manager->id,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->submit($dupe, $manager);
    }

    /** AT-09: Block manager cannot approve invoices or finalise */
    public function test_at09_block_manager_cannot_approve_or_finalise(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $manager = $this->makeUser(User::ROLE_BLOCK_MANAGER);
        $heading = CostHeading::query()->firstOrFail();

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Ready',
            'status' => Budget::STATUS_READY,
            'authorised_approver' => 'A',
            'authorised_at' => now(),
            'authorised_capacity' => 'Director',
        ]);

        BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '10.00',
            'is_reserve' => false,
        ]);

        $this->actingAs($manager)->post(route('budgets.finalise', $budget))->assertForbidden();

        $supplier = Supplier::query()->create(['name' => 'S', 'is_active' => true]);
        $final = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Final BM',
            'status' => Budget::STATUS_FINAL,
            'finalised_at' => now(),
        ]);
        $line = BudgetLine::query()->create([
            'budget_id' => $final->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '10.00',
            'is_reserve' => false,
        ]);
        $invoice = Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $final->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'X1',
            'normalized_reference' => 'x1',
            'invoice_date' => '2027-01-01',
            'net_amount' => '1.00',
            'vat_amount' => '0.00',
            'gross_amount' => '1.00',
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($manager)->post(route('invoices.approve', $invoice))->assertForbidden();
    }

    /** AT-10: Accountant can approve; Final budget line amounts cannot be changed */
    public function test_at10_accountant_approve_and_final_immutable_amounts(): void
    {
        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $accountant = $this->makeUser(User::ROLE_ACCOUNTANT);
        $heading = CostHeading::query()->firstOrFail();
        $supplier = Supplier::query()->create(['name' => 'S2', 'is_active' => true]);

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Final immutable',
            'status' => Budget::STATUS_FINAL,
            'finalised_at' => now(),
        ]);

        $line = BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '250.00',
            'is_reserve' => false,
        ]);

        $this->actingAs($accountant)->put(route('budget-lines.update', $line), [
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '999.00',
        ])->assertForbidden();

        $invoice = Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'APP-1',
            'normalized_reference' => 'app-1',
            'invoice_date' => '2027-01-01',
            'net_amount' => '10.00',
            'vat_amount' => '2.00',
            'gross_amount' => '12.00',
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $this->actingAs($accountant)->post(route('invoices.approve', $invoice))->assertRedirect();
        $this->assertSame(Invoice::STATUS_APPROVED, $invoice->fresh()->status);
    }

    /** AT-11: Monitor metrics and overspend clarity; GBP helper */
    public function test_at11_budget_monitor_and_money_helper(): void
    {
        $this->assertSame('£1,234.50', Money::formatGbp('1234.50'));

        $this->seedHeadings();
        $portfolio = $this->makeBuildingPortfolio();
        $heading = CostHeading::query()->where('code', 'INS')->firstOrFail();
        $supplier = Supplier::query()->create(['name' => 'S3', 'is_active' => true]);

        $budget = Budget::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'title' => 'Monitor',
            'status' => Budget::STATUS_FINAL,
            'finalised_at' => now(),
        ]);

        $line = BudgetLine::query()->create([
            'budget_id' => $budget->id,
            'schedule_id' => $portfolio['schedule']->id,
            'cost_heading_id' => $heading->id,
            'current_estimate' => '100.00',
            'is_reserve' => false,
        ]);

        Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'OV-1',
            'normalized_reference' => 'ov-1',
            'invoice_date' => '2027-01-01',
            'net_amount' => '150.00',
            'vat_amount' => '0.00',
            'gross_amount' => '150.00',
            'status' => Invoice::STATUS_APPROVED,
        ]);

        Invoice::query()->create([
            'building_id' => $portfolio['building']->id,
            'service_charge_year_id' => $portfolio['year']->id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $supplier->id,
            'invoice_reference' => 'OV-2',
            'normalized_reference' => 'ov-2',
            'invoice_date' => '2027-01-02',
            'net_amount' => '20.00',
            'vat_amount' => '0.00',
            'gross_amount' => '20.00',
            'status' => Invoice::STATUS_SUBMITTED,
        ]);

        $metrics = app(BudgetMonitorService::class)->forBuilding($budget);
        $this->assertSame('100.00', $metrics['budget']);
        $this->assertSame('150.00', $metrics['approved_spent']);
        $this->assertSame('20.00', $metrics['pending_amount']);
        $this->assertSame('-50.00', $metrics['remaining']);
        $this->assertTrue($metrics['overspend']);
    }
}
