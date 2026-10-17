<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertRedirect(route('overview'));

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
        ])->assertRedirect(route('overview'));

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
        ])->assertRedirect(route('overview'))
            ->assertSessionHas('opened', $company->id);

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

        $this->post(route('companies.sent', $company))->assertRedirect(route('overview'));

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
}
