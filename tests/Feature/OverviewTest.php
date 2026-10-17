<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_lists_sent_companies_and_the_interview_headline(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Interview)->create(['name' => 'TurTel']);
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);
        Company::factory()->create(['name' => 'Koncept s.r.o.']);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSee('V TurTel ťa čaká', false)
            ->assertSee('CODIUM')
            ->assertSee('Koncept s.r.o.');
    }

    public function test_the_full_list_includes_unsent_and_unsuitable_companies(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);
        Company::factory()->create(['name' => 'Koncept s.r.o.']);
        Company::factory()->withStatus(ApplicationStatus::Unsuitable)->create(['name' => 'Hardvér s.r.o.']);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSeeInOrder(['CODIUM', 'Koncept s.r.o.', 'Hardvér s.r.o.', 'Pripravené na odoslanie'])
            ->assertDontSee('Zatiaľ tu nie je žiadna firma.');
    }

    public function test_unsent_and_unsuitable_companies_have_their_own_filters(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);
        Company::factory()->create(['name' => 'Koncept s.r.o.']);
        Company::factory()->withStatus(ApplicationStatus::Unsuitable)->create(['name' => 'Hardvér s.r.o.']);

        $this->get(route('overview', ['stav' => 'nevhodne']))
            ->assertOk()
            ->assertSee('Hardvér s.r.o.')
            ->assertDontSee('CODIUM');

        $this->get(route('overview', ['stav' => 'neposlane']))
            ->assertOk()
            ->assertSee('Koncept s.r.o.')
            ->assertDontSee('Hardvér s.r.o.');
    }

    public function test_filters_show_how_many_companies_have_each_status(): void
    {
        Company::factory()->count(2)->withStatus(ApplicationStatus::Sent)->create();
        Company::factory()->withStatus(ApplicationStatus::Rejected)->create();
        Company::factory()->count(3)->create();

        $html = $this->get(route('overview'))->assertOk()->getContent();

        foreach (['Všetky' => 6, 'Poslané' => 2, 'Zamietnuté' => 1, 'Neposlané' => 3, 'Nevhodné' => 0] as $label => $count) {
            $this->assertMatchesRegularExpression('/'.$label.'\s*<span[^>]*>'.$count.'<\/span>/u', $html, $label);
        }
    }

    public function test_rejections_count_as_replies_in_the_summary(): void
    {
        Company::factory()->count(2)->withStatus(ApplicationStatus::Sent)->create();
        Company::factory()->count(3)->withStatus(ApplicationStatus::Rejected)->create();
        Company::factory()->withStatus(ApplicationStatus::Reply)->create();

        $html = $this->get(route('overview'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/>4<\/dd>\s*<dt[^>]*>odpovedí<\/dt>/u', $html);
        $this->assertMatchesRegularExpression('/>6<\/dd>\s*<dt[^>]*>poslaných<\/dt>/u', $html);
    }

    public function test_a_saved_change_is_confirmed_and_the_row_can_be_found_again(): void
    {
        $company = Company::factory()->withStatus(ApplicationStatus::Sent)->create(['web' => 'codium.sk']);

        $this->withSession(['status' => 'Uložené.'])
            ->get(route('overview'))
            ->assertOk()
            ->assertSee('Uložené.')
            ->assertSee('id="company-'.$company->id.'"', false)
            ->assertSee('codium.sk');
    }

    public function test_the_waiting_filter_only_appears_once_a_company_is_waiting(): void
    {
        $this->get(route('overview'))->assertOk()->assertDontSee('Čaká sa');

        Company::factory()->withStatus(ApplicationStatus::Waiting)->create();

        $this->get(route('overview'))->assertOk()->assertSee('Čaká sa');
    }

    public function test_overview_invites_you_to_add_a_company_when_there_are_none(): void
    {
        $this->get(route('overview'))
            ->assertOk()
            ->assertSee('Zatiaľ tu nie je žiadna firma.');
    }

    public function test_overview_can_be_filtered_by_status(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Rejected)->create(['name' => 'Hyperia']);
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);

        $this->get(route('overview', ['stav' => 'zamietnute']))
            ->assertOk()
            ->assertSee('Hyperia')
            ->assertDontSee('CODIUM');
    }

    public function test_overview_can_be_searched_by_company_name(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Rejected)->create(['name' => 'Hyperia']);
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);

        $this->get(route('overview', ['q' => 'codi']))
            ->assertOk()
            ->assertSee('CODIUM')
            ->assertDontSee('Hyperia');
    }

    public function test_overview_ignores_an_unknown_status(): void
    {
        Company::factory()->withStatus(ApplicationStatus::Sent)->create(['name' => 'CODIUM']);

        $this->get(route('overview', ['stav' => 'neexistuje']))
            ->assertOk()
            ->assertSee('CODIUM');
    }

    public function test_overview_explains_when_nothing_matches(): void
    {
        $this->get(route('overview', ['q' => 'zzz']))
            ->assertOk()
            ->assertSee('Žiadna firma nezodpovedá filtru.');
    }

    public function test_overview_summarises_the_counts_from_the_database(): void
    {
        Company::factory()->count(3)->withStatus(ApplicationStatus::Sent)->create();
        Company::factory()->withStatus(ApplicationStatus::Reply)->create();
        Company::factory()->withStatus(ApplicationStatus::Rejected)->create();

        $this->get(route('overview'))
            ->assertOk()
            ->assertSee('Odpoveď prišla od 40 % oslovených firiem.', false);
    }

    public function test_later_topics_are_grouped_with_a_company_count(): void
    {
        Company::factory()->count(2)->create(['future' => 'Node.js']);
        Company::factory()->create(['future' => 'Java']);

        $this->get(route('overview'))
            ->assertOk()
            ->assertSee('Node.js · 2 firmy')
            ->assertSee('Java · 1 firma');
    }
}
