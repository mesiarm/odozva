<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportCompaniesTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'companies');

        $rows = [
            ['#', 'Firma', 'Zameranie', 'Web', 'Poznámka', 'Email', 'Nevhodné (nerobí programovanie / iný odbor)', 'Čo robia / jazyk (z webu)'],
            [1, 'Nová firma', 'Krátke zameranie', 'https://www.nova.sk/kariera', 'Pekná firma', '', '', 'Vyvíjajú v PHP'],
            [2, 'CODIUM', '', 'https://codium.sk', '', 'Poslané – Profesia 2. 9. (.NET medior)', '', ''],
            [3, 'Hyperia', '', '', '', 'Poslané – zamietnuté (Profesia 3. 9.; mail 22. 9.)', '', ''],
            [4, 'Descartes', '', '', '', 'Poslané 27. 9., prebehla komunikácia', '', ''],
            [5, 'KROS', '', '', '', 'Poslané skôr – zamietnuté', '', ''],
            [6, 'eSYST', '', '', '', 'Poslané 14. 10. (e-mail)', '', ''],
            [7, 'Hardvér s.r.o.', '', '', '', '', 'HW + linux', ''],
            [10, 'Odmietla', '', '', '', 'Zamietnuté – neprijali ma: chcú aj frontendistu', '', ''],
            [9, 'Preverovaná', '', '', '', 'Poslané 27. 9., prebehla komunikácia; 9. 10. formálna prihláška – preverujú ju', '', ''],
            [8, '', '', '', '', '', '', ''],
        ];

        $handle = fopen($this->path, 'wb');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '\\');
        }

        fclose($handle);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_it_maps_the_spreadsheet_columns_to_companies(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026])->assertSuccessful();

        $this->assertDatabaseCount('companies', 9);

        $new = Company::firstWhere('name', 'Nová firma');
        $this->assertSame(ApplicationStatus::Unsent, $new->status);
        $this->assertSame('nova.sk', $new->web);
        $this->assertSame('Vyvíjajú v PHP', $new->focus);
        $this->assertSame('Pekná firma', $new->note);
    }

    public function test_it_reads_status_date_and_channel_from_the_activity_notes(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026]);

        $codium = Company::firstWhere('name', 'CODIUM');
        $this->assertSame(ApplicationStatus::Sent, $codium->status);
        $this->assertSame('2026-09-02', $codium->sent_at->toDateString());
        $this->assertSame('Profesia', $codium->channel);

        $this->assertSame(ApplicationStatus::Rejected, Company::firstWhere('name', 'Hyperia')->status);
        $this->assertSame('2026-09-03', Company::firstWhere('name', 'Hyperia')->sent_at->toDateString());
        $this->assertSame(ApplicationStatus::Reply, Company::firstWhere('name', 'Descartes')->status);
        $this->assertSame(ApplicationStatus::Waiting, Company::firstWhere('name', 'Preverovaná')->status);
        $this->assertSame('Email', Company::firstWhere('name', 'eSYST')->channel);

        $kros = Company::firstWhere('name', 'KROS');
        $this->assertSame(ApplicationStatus::Rejected, $kros->status);
        $this->assertNull($kros->sent_at);
    }

    public function test_a_rejection_reason_is_stored_separately_from_the_note(): void
    {
        $this->artisan('companies:import', ['path' => $this->path]);

        $rejected = Company::firstWhere('name', 'Odmietla');

        $this->assertSame(ApplicationStatus::Rejected, $rejected->status);
        $this->assertSame('chcú aj frontendistu', $rejected->reason);
        $this->assertNull(Company::firstWhere('name', 'KROS')->reason);
    }

    public function test_unsuitable_companies_keep_their_reason_and_are_not_counted_as_sent(): void
    {
        $this->artisan('companies:import', ['path' => $this->path]);

        $hardware = Company::firstWhere('name', 'Hardvér s.r.o.');

        $this->assertSame(ApplicationStatus::Unsuitable, $hardware->status);
        $this->assertNull($hardware->sent_at);
        $this->assertSame('HW + linux', $hardware->reason);
        $this->assertNull($hardware->note);
    }

    public function test_existing_companies_are_not_overwritten(): void
    {
        Company::factory()->create(['name' => 'CODIUM', 'note' => 'Moja poznámka']);

        $this->artisan('companies:import', ['path' => $this->path])
            ->expectsOutputToContain('bez zmeny 1')
            ->assertSuccessful();

        $this->assertSame('Moja poznámka', Company::firstWhere('name', 'CODIUM')->note);
        $this->assertSame(1, Company::where('name', 'CODIUM')->count());
    }

    public function test_a_dry_run_does_not_save_anything(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_a_missing_file_fails(): void
    {
        $this->artisan('companies:import', ['path' => $this->path.'.neexistuje'])->assertFailed();
    }

    public function test_it_gives_companies_of_the_main_sheet_a_location(): void
    {
        $this->artisan('companies:import', ['path' => $this->path]);
        $this->assertSame('Žilina', Company::firstWhere('name', 'CODIUM')->location);

        $this->artisan('companies:import', ['path' => $this->path, '--location' => 'Bratislava']);
        $this->assertSame('Žilina', Company::firstWhere('name', 'CODIUM')->location);
    }

    public function test_it_imports_the_sheet_with_companies_outside_zilina(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'applications');

        $rows = [
            ['#', 'Firma', 'Pozícia', 'Poloha (a odkiaľ to viem)', 'Kanál', 'Prihlásené', 'Stav', 'Posledná správa / poznámka'],
            [1, 'ESET', 'PHP Software Engineer', 'Bratislava (sídlo firmy; v e-maile neuvedené)', 'Workday (kariérny portál)', '30. 9.', 'Zamietnuté 5. 10.', 'Po zhodnotení profilu zamietnuté.'],
            [2, 'Henley', 'Web Developer', 'Bratislava (Pribinova 4)', 'Pozvánka od HR', '–', 'Pozvánka na 1. kolo 7. 10. o 16:00', 'Zoom, po anglicky.'],
            [3, 'Takoy', 'Pozícia z Profesie (názov v e-maile nie je)', 'Bardejov (kancelária)', 'Profesia', '–', 'Stiahol si sa 9. 10.', 'Miesto výkonu práce nevyhovuje.'],
            [4, 'AT&T', 'PHP Developer', 'neuvedená v e-maile', 'Workday', '30. 9.', 'Prijaté, čaká sa', 'Potvrdenie z 30. 9.'],
            [5, 'Servers.com', 'Backend Developer', 'neuvedená v e-maile', 'Profesia', '13. 10.', 'Bez odpovede', 'Len potvrdenie reakcie z Profesie.'],
            [6, 'OpsArmy', 'Pozícia v e-maile nie je', 'neuvedená v e-maile', 'Kariérny portál', '–', 'Prihláška asi nedokončená', '1. 10. pripomienka.'],
            ['', 'Poznámky', '', '', '', '', '', ''],
            ['', 'Zdroj: Gmail', '', '', '', '', '', ''],
        ];

        $handle = fopen($path, 'wb');

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '\\');
        }

        fclose($handle);

        $this->artisan('companies:import', ['path' => $path, '--year' => 2026])->assertSuccessful();

        @unlink($path);

        $this->assertDatabaseCount('companies', 6);
        $this->assertDatabaseMissing('companies', ['name' => 'Poznámky']);

        $eset = Company::firstWhere('name', 'ESET');
        $this->assertSame('Bratislava', $eset->location);
        $this->assertSame(ApplicationStatus::Rejected, $eset->status);
        $this->assertSame('2026-09-30', $eset->sent_at->toDateString());
        $this->assertSame('Workday (kariérny portál)', $eset->channel);
        $this->assertSame("Zamietnuté 5. 10.\nPo zhodnotení profilu zamietnuté.", $eset->note);

        $this->assertSame(ApplicationStatus::Interview, Company::firstWhere('name', 'Henley')->status);
        $this->assertNull(Company::firstWhere('name', 'Henley')->sent_at);

        $takoy = Company::firstWhere('name', 'Takoy');
        $this->assertSame(ApplicationStatus::Unsuitable, $takoy->status);
        $this->assertSame('Miesto výkonu práce nevyhovuje.', $takoy->reason);
        $this->assertNull($takoy->position);

        $this->assertNull(Company::firstWhere('name', 'AT&T')->location);
        $this->assertSame(ApplicationStatus::Waiting, Company::firstWhere('name', 'AT&T')->status);
        $this->assertSame(ApplicationStatus::Sent, Company::firstWhere('name', 'Servers.com')->status);
        $this->assertSame('Len potvrdenie reakcie z Profesie.', Company::firstWhere('name', 'Servers.com')->note);

        $opsArmy = Company::firstWhere('name', 'OpsArmy');
        $this->assertSame(ApplicationStatus::Unsent, $opsArmy->status);
        $this->assertNull($opsArmy->channel);
    }

    public function test_importing_the_same_file_again_changes_nothing(): void
    {
        $this->artisan('companies:import', ['path' => $this->path]);

        $this->artisan('companies:import', ['path' => $this->path])
            ->expectsOutputToContain('nových 0, aktualizovaných 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('companies', 9);
    }

    public function test_a_company_is_updated_when_its_row_in_the_sheet_changes(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026]);

        $changed = tempnam(sys_get_temp_dir(), 'changed');
        file_put_contents($changed, str_replace(
            'Poslané – Profesia 2. 9. (.NET medior)',
            'Zamietnuté – pozícia obsadená (e-mail 16. 10.)',
            file_get_contents($this->path),
        ));

        $this->artisan('companies:import', ['path' => $changed, '--year' => 2026])
            ->expectsOutputToContain('aktualizovaných 1')
            ->assertSuccessful();

        @unlink($changed);

        $codium = Company::firstWhere('name', 'CODIUM');

        $this->assertSame(ApplicationStatus::Rejected, $codium->status);
        $this->assertSame('Pozícia obsadená', $codium->reason);
        $this->assertSame('2026-09-02', $codium->sent_at->toDateString());
        $this->assertStringContainsString('Poslané – Profesia 2. 9. (.NET medior)', $codium->note);
        $this->assertStringContainsString('Zamietnuté – pozícia obsadená (e-mail 16. 10.)', $codium->note);
    }

    public function test_changes_made_in_the_app_survive_while_the_sheet_row_is_unchanged(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026]);

        Company::firstWhere('name', 'Hyperia')->update(['status' => ApplicationStatus::Interview, 'note' => 'Moja poznámka']);

        $changed = tempnam(sys_get_temp_dir(), 'changed');
        file_put_contents($changed, str_replace('Poslané 14. 10. (e-mail)', 'Poslané 14. 10. (e-mail), pripomenuté 20. 10.', file_get_contents($this->path)));

        $this->artisan('companies:import', ['path' => $changed, '--year' => 2026]);

        @unlink($changed);

        $hyperia = Company::firstWhere('name', 'Hyperia');

        $this->assertSame(ApplicationStatus::Interview, $hyperia->status);
        $this->assertSame('Moja poznámka', $hyperia->note);
        $this->assertStringContainsString('pripomenuté 20. 10.', Company::firstWhere('name', 'eSYST')->note);
    }

    public function test_empty_cells_in_a_changed_row_keep_the_current_values(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026]);

        Company::firstWhere('name', 'eSYST')->update(['location' => 'Bratislava', 'position' => 'PHP developer']);

        $changed = tempnam(sys_get_temp_dir(), 'changed');
        file_put_contents($changed, str_replace('Poslané 14. 10. (e-mail)', 'Poslané 14. 10. (e-mail), ďalšia správa', file_get_contents($this->path)));

        $this->artisan('companies:import', ['path' => $changed, '--year' => 2026, '--location' => 'Žilina']);

        @unlink($changed);

        $esyst = Company::firstWhere('name', 'eSYST');

        $this->assertSame('Žilina', $esyst->location);
        $this->assertSame('PHP developer', $esyst->position);
    }

    public function test_a_changed_row_without_mail_history_does_not_take_a_company_back_to_unsent(): void
    {
        $this->artisan('companies:import', ['path' => $this->path, '--year' => 2026]);

        Company::firstWhere('name', 'Nová firma')->update(['status' => ApplicationStatus::Sent]);

        $changed = tempnam(sys_get_temp_dir(), 'changed');
        file_put_contents($changed, str_replace('Pekná firma', 'Pekná firma, doplnená poznámka', file_get_contents($this->path)));

        $this->artisan('companies:import', ['path' => $changed, '--year' => 2026]);

        @unlink($changed);

        $company = Company::firstWhere('name', 'Nová firma');

        $this->assertSame(ApplicationStatus::Sent, $company->status);
        $this->assertStringContainsString('doplnená poznámka', $company->note);
    }
}
