<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreensTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_show_rates_and_counts_per_status(): void
    {
        Company::factory()->count(2)->withStatus(ApplicationStatus::Sent)->create();
        Company::factory()->withStatus(ApplicationStatus::Interview)->create(['name' => 'TurTel']);
        Company::factory()->withStatus(ApplicationStatus::Rejected)->create();
        Company::factory()->create();

        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee('5 firiem v zozname')
            ->assertSee('50 %', false)
            ->assertSee('25 %', false)
            ->assertSee('TurTel');
    }

    public function test_statistics_work_without_any_companies(): void
    {
        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee('0 firiem v zozname')
            ->assertSee('Zatiaľ žiadna aktivita.');
    }

    public function test_later_groups_companies_by_topic(): void
    {
        Company::factory()->state(['status' => ApplicationStatus::Later, 'name' => 'SAMTECH', 'future' => 'Java'])->create();
        Company::factory()->state(['status' => ApplicationStatus::Later, 'name' => 'Verteco', 'future' => 'Node.js'])->create();
        Company::factory()->state(['status' => ApplicationStatus::Later, 'name' => 'Bez nápadu'])->create();
        Company::factory()->create(['name' => 'Len neposlaná', 'future' => 'PHP']);

        $this->get(route('later'))
            ->assertOk()
            ->assertSeeInOrder(['Bez témy', 'Bez nápadu', 'Java', 'SAMTECH', 'Node.js', 'Verteco'])
            ->assertDontSee('Len neposlaná');
    }

    public function test_later_explains_when_it_is_empty(): void
    {
        $this->get(route('later'))
            ->assertOk()
            ->assertSee('Zatiaľ tu nič nie je.')
            ->assertSee('Na neskôr');
    }

    public function test_navigation_marks_the_current_screen(): void
    {
        $this->get(route('statistics'))
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }
}
