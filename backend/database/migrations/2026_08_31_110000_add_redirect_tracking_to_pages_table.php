<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->text('requested_url')->nullable()->after('url');
            $table->text('final_url')->nullable()->after('requested_url');
            $table->unsignedTinyInteger('redirect_count')->default(0)->after('status_code');
            $table->json('redirect_chain')->nullable()->after('redirect_count');
        });

        // Populate requested_url and final_url for existing records
        DB::statement('UPDATE pages SET requested_url = url, final_url = url WHERE requested_url IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['requested_url', 'final_url', 'redirect_count', 'redirect_chain']);
        });
    }
};
