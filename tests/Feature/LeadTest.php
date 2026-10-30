<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lead_can_be_created_from_a_directory_entry(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'FIRST CAR s.r.o.',
            'source' => 'finstat',
            'ico' => '12345678',
            'revenue' => 1500000,
            'founded_on' => '2008-03-15',
        ]);

        $lead->refresh();

        $this->assertSame('finstat', $lead->source);
        $this->assertSame(1500000, $lead->revenue);
        $this->assertSame('2008-03-15', $lead->founded_on->toDateString());
    }

    public function test_leads_do_not_show_up_in_the_overview_or_the_counts(): void
    {
        Company::factory()->create(['name' => 'Moja firma']);
        Lead::factory()->count(3)->create(['name' => 'Cudzia firma']);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSee('Moja firma')
            ->assertDontSee('Cudzia firma');

        $this->get(route('statistics'))->assertOk()->assertSee('1 firma v zozname');
    }

    public function test_company_details_taken_from_the_spreadsheet_are_stored(): void
    {
        $company = Company::factory()->create([
            'career_url' => 'https://firma.sk/kariera',
            'address' => 'Priemyselná ulica, Žilina',
            'location_confidence' => 'Sídlo v Žiline',
            'ico' => '31405851',
            'employees' => '500-999',
            'revenue' => 43345123,
            'founded_on' => '1995-11-13',
        ])->refresh();

        $this->assertSame('https://firma.sk/kariera', $company->career_url);
        $this->assertSame(43345123, $company->revenue);
        $this->assertSame('1995-11-13', $company->founded_on->toDateString());
    }
}
