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
            $table->text('canonical_href')->nullable()->after('html');
            $table->text('canonical_url')->nullable()->after('canonical_href');
            $table->integer('canonical_count')->default(0)->after('canonical_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['canonical_href', 'canonical_url', 'canonical_count']);
        });
    }
};
