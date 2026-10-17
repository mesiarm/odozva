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

        $this->assertDatabaseCount('companies', 8);

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

    public function test_unsuitable_companies_keep_their_reason_and_are_not_counted_as_sent(): void
    {
        $this->artisan('companies:import', ['path' => $this->path]);

        $hardware = Company::firstWhere('name', 'Hardvér s.r.o.');

        $this->assertSame(ApplicationStatus::Unsuitable, $hardware->status);
        $this->assertNull($hardware->sent_at);
        $this->assertSame('Nevhodné: HW + linux', $hardware->note);
    }

    public function test_existing_companies_are_not_overwritten(): void
    {
        Company::factory()->create(['name' => 'CODIUM', 'note' => 'Moja poznámka']);

        $this->artisan('companies:import', ['path' => $this->path])
            ->expectsOutputToContain('preskočených 1')
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
}
