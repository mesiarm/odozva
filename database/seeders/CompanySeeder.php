<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Seed sample companies so the overview has something to show.
     */
    public function run(): void
    {
        $companies = [
            ['TurTel', ApplicationStatus::Interview, null, 'Telefón', null, 'turtel.sk', null, 'Čaká sa na pohovor.'],
            ['Descartes Systems', ApplicationStatus::Reply, '2026-09-27', 'Email', null, 'descartes.com', null, 'Komunikácia prebehla.'],
            ['CODIUM', ApplicationStatus::Sent, '2026-09-02', 'Profesia', '.NET developer medior', 'codium.sk', null, 'Firma už videla životopis. Prišla automatická odpoveď.'],
            ['24pay', ApplicationStatus::Sent, '2026-10-13', 'Email', 'Backend (PHP)', '24-pay.sk', null, null],
            ['Ringier Slovakia', ApplicationStatus::Sent, '2026-10-13', 'Email', null, 'ringier.sk', null, null],
            ['PROSOFT', ApplicationStatus::Sent, '2026-10-13', 'Email', null, 'prosoft.sk', null, null],
            ['EMEL', ApplicationStatus::Sent, '2026-10-07', 'Profesia', 'Programátor', null, null, null],
            ['Hyperia', ApplicationStatus::Rejected, '2026-09-03', 'Profesia', 'PHP Backend Developer', 'hyperia.sk', null, 'Zamietnuté v emaili z 22. 9.'],
            ['Prima banka', ApplicationStatus::Rejected, '2026-09-02', 'Profesia', 'Programátor (M/Ž)', 'primabanka.sk', null, 'Dev tím v Žiline.'],
            ['SOFTEC', ApplicationStatus::Rejected, '2026-09-02', 'Profesia', 'Fullstack Developer', 'softec.sk', null, 'Po rozhovore zamietnuté 9. 9.'],
            ['inoby', ApplicationStatus::Unsent, null, 'Email', null, 'inoby.sk', null, 'Koncept e-mailu pripravený.'],
            ['eSYST', ApplicationStatus::Unsent, null, 'Email', null, 'esyst.sk', null, 'Koncept e-mailu pripravený.'],
            ['MeDoIT', ApplicationStatus::Unsent, null, 'Email', null, 'medoit.sk', null, 'Koncept e-mailu pripravený.'],
            ['SAMTECH', ApplicationStatus::Unsent, null, 'Email', null, 'samtech.sk', 'Java', 'Java EE, React.'],
            ['Verteco digital services', ApplicationStatus::Unsent, null, 'Email', null, 'verteco.digital', 'Node.js', 'TypeScript, Node.js, Java.'],
            ['MARTES', ApplicationStatus::Unsent, null, null, null, 'martes.sk', null, 'Web spomína PHP.'],
        ];

        foreach ($companies as [$name, $status, $sentAt, $channel, $position, $web, $future, $note]) {
            Company::updateOrCreate(
                ['name' => $name],
                compact('status') + [
                    'sent_at' => $sentAt,
                    'channel' => $channel,
                    'position' => $position,
                    'web' => $web,
                    'future' => $future,
                    'note' => $note,
                ],
            );
        }
    }
}
