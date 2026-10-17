<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Desktop\Facades\Shell;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_form_is_shown(): void
    {
        $this->get(route('companies.create'))
            ->assertOk()
            ->assertSee('Nová firma');
    }

    public function test_a_company_can_be_created_with_only_a_name(): void
    {
        $this->post(route('companies.store'), ['name' => 'TurTel', 'status' => 'neposlane'])
            ->assertRedirectContains(route('overview'))
            ->assertSessionHas('status', 'Firma bola pridaná.');

        $company = Company::firstWhere('name', 'TurTel');

        $this->assertSame(ApplicationStatus::Unsent, $company->status);
        $this->assertNull($company->sent_at);
    }

    public function test_creating_a_sent_company_records_the_date(): void
    {
        $this->travelTo('2026-10-17');

        $this->post(route('companies.store'), [
            'name' => 'CODIUM',
            'status' => 'poslane',
            'channel' => 'Email',
            'future' => 'Node.js',
        ]);

        $company = Company::firstWhere('name', 'CODIUM');

        $this->assertSame('2026-10-17', $company->sent_at->toDateString());
        $this->assertSame('Node.js', $company->future);
    }

    public function test_a_company_needs_a_name_and_a_valid_status(): void
    {
        $this->post(route('companies.store'), ['name' => '', 'status' => 'nezmysel'])
            ->assertSessionHasErrors(['name', 'status']);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_the_reason_can_be_saved_and_is_shown_in_the_list(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'Hyperia', 'channel' => 'Profesia']);

        $this->patch(route('companies.update', $company), [
            'status' => 'zamietnute',
            'reason' => 'Hľadajú seniora',
            'note' => 'Odpísali e-mailom.',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Hľadajú seniora', $company->fresh()->reason);
        $this->assertSame('Odpísali e-mailom.', $company->fresh()->note);

        $this->get(route('overview'))->assertOk()->assertSee('Hľadajú seniora');
    }

    public function test_an_unsuitable_company_can_be_created_with_a_reason(): void
    {
        $this->post(route('companies.store'), ['name' => 'Hardvér', 'status' => 'nevhodne', 'reason' => 'HW + linux']);

        $company = Company::firstWhere('name', 'Hardvér');

        $this->assertSame(ApplicationStatus::Unsuitable, $company->status);
        $this->assertSame('HW + linux', $company->reason);
        $this->assertSame('Nevhodné: HW + linux', $company->summary_line);
    }

    public function test_a_rejection_can_be_recorded_with_the_date_the_application_was_sent(): void
    {
        $company = Company::factory()->create(['name' => 'Hyperia']);

        $this->patch(route('companies.update', $company), [
            'status' => 'zamietnute',
            'sent_at' => '2026-09-03',
            'note' => 'Zamietnuté e-mailom.',
        ])->assertSessionDoesntHaveErrors();

        $company->refresh();

        $this->assertSame(ApplicationStatus::Rejected, $company->status);
        $this->assertSame('2026-09-03', $company->sent_at->toDateString());
    }

    public function test_a_company_can_be_created_straight_as_rejected_with_a_sent_date(): void
    {
        $this->post(route('companies.store'), [
            'name' => 'Hyperia',
            'status' => 'zamietnute',
            'sent_at' => '2026-09-03',
        ])->assertRedirectContains(route('overview'));

        $company = Company::firstWhere('name', 'Hyperia');

        $this->assertSame(ApplicationStatus::Rejected, $company->status);
        $this->assertSame('2026-09-03', $company->sent_at->toDateString());
    }

    public function test_the_sent_date_cannot_be_in_the_future(): void
    {
        $this->travelTo('2026-10-17');
        $company = Company::factory()->create();

        $this->patch(route('companies.update', $company), ['status' => 'poslane', 'sent_at' => '2026-12-01'])
            ->assertSessionHasErrors('sent_at');
    }

    public function test_clearing_the_sent_date_removes_it(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Sent)->create();

        $this->patch(route('companies.update', $company), ['status' => 'poslane', 'sent_at' => '']);

        $this->assertNull($company->fresh()->sent_at);
    }

    public function test_a_company_can_be_updated(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Sent)->create();

        $this->patch(route('companies.update', $company), [
            'status' => 'pohovor',
            'position' => 'PHP developer',
            'note' => 'Volali v utorok.',
        ])->assertRedirect(route('overview').'#company-'.$company->id)
            ->assertSessionHas('opened', $company->id)
            ->assertSessionHas('status', 'Uložené.');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'pohovor',
            'position' => 'PHP developer',
            'note' => 'Volali v utorok.',
        ]);
    }

    public function test_moving_an_unsent_company_to_a_sent_status_records_the_date(): void
    {
        $this->travelTo('2026-10-17');
        $company = Company::factory()->create();

        $this->patch(route('companies.update', $company), ['status' => 'poslane']);

        $this->assertSame('2026-10-17', $company->fresh()->sent_at->toDateString());
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Sent)->create();

        $this->patch(route('companies.update', $company), ['status' => 'nezmysel'])
            ->assertSessionHasErrors('status');

        $this->assertSame(ApplicationStatus::Sent, $company->fresh()->status);
    }

    public function test_a_draft_can_be_marked_as_sent(): void
    {
        $this->travelTo('2026-10-17');
        $company = Company::factory()->create();

        $this->post(route('companies.sent', $company))
            ->assertRedirect(route('overview'))
            ->assertSessionHas('status', 'Označené ako poslané.');

        $company->refresh();

        $this->assertSame(ApplicationStatus::Sent, $company->status);
        $this->assertSame('2026-10-17', $company->sent_at->toDateString());
    }

    public function test_marking_as_sent_does_not_change_a_company_that_already_replied(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Reply)->create();

        $this->post(route('companies.sent', $company));

        $this->assertSame(ApplicationStatus::Reply, $company->fresh()->status);
    }

    public function test_the_company_web_opens_in_the_browser_outside_the_native_app(): void
    {
        $company = Company::factory()->create(['web' => 'codium.sk']);

        $this->post(route('companies.web', $company))->assertRedirect('https://codium.sk');
    }

    public function test_the_company_web_opens_through_the_native_shell_inside_the_app(): void
    {
        config(['nativephp-internal.running' => true]);
        Shell::shouldReceive('openExternal')->once()->with('https://codium.sk');

        $company = Company::factory()->create(['web' => 'codium.sk']);

        $this->post(route('companies.web', $company))->assertNoContent();
    }

    public function test_a_company_without_a_web_cannot_be_opened(): void
    {
        $company = Company::factory()->create(['web' => null]);

        $this->post(route('companies.web', $company))->assertNotFound();
    }
}
