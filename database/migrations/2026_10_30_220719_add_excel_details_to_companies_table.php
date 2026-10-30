<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Details that used to live only in the spreadsheet. They are kept for reference and not shown in the app.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('web_url', 500)->nullable()->after('web');
            $table->string('career_url', 500)->nullable()->after('web_url');
            $table->string('address')->nullable()->after('location');
            $table->string('location_confidence')->nullable()->after('address');
            $table->text('location_note')->nullable()->after('location_confidence');
            $table->string('web_check')->nullable()->after('career_url');
            $table->string('source')->nullable()->after('web_check');
            $table->text('summary')->nullable()->after('focus');
            $table->string('ico', 20)->nullable()->index()->after('summary');
            $table->string('employees', 50)->nullable()->after('ico');
            $table->unsignedBigInteger('revenue')->nullable()->after('employees');
            $table->date('founded_on')->nullable()->after('revenue');
            $table->string('registry_status', 50)->nullable()->after('founded_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'web_url', 'career_url', 'address', 'location_confidence', 'location_note', 'web_check',
                'source', 'summary', 'ico', 'employees', 'revenue', 'founded_on', 'registry_status',
            ]);
        });
    }
};
