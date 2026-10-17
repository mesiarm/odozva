<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('future');
        });

        $this->moveReasonsOutOfNotes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }

    /**
     * Earlier imports kept the "unsuitable" reason as a "Nevhodné: …" line in the note,
     * and a rejection reason as part of the activity note.
     */
    private function moveReasonsOutOfNotes(): void
    {
        $rows = DB::table('companies')
            ->whereIn('status', ['nevhodne', 'zamietnute'])
            ->whereNotNull('note')
            ->get(['id', 'note']);

        foreach ($rows as $row) {
            $reason = null;
            $lines = [];

            foreach (explode("\n", $row->note) as $line) {
                if (str_starts_with($line, 'Nevhodné: ')) {
                    $reason = trim(substr($line, strlen('Nevhodné: ')));

                    continue;
                }

                if ($reason === null && preg_match('/neprijali ma:\s*(.+)$/u', $line, $matches)) {
                    $reason = trim($matches[1]);
                }

                $lines[] = $line;
            }

            if ($reason !== null) {
                DB::table('companies')->where('id', $row->id)->update([
                    'reason' => $reason,
                    'note' => implode("\n", $lines) ?: null,
                ]);
            }
        }
    }
};
