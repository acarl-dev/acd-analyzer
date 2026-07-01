<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crawl_runs', function (Blueprint $table) {
            $table->unsignedInteger('max_pages')->default(10)->after('pages_crawled');
            $table->unsignedInteger('max_depth')->default(1)->after('max_pages');
        });
    }

    public function down(): void
    {
        Schema::table('crawl_runs', function (Blueprint $table) {
            $table->dropColumn(['max_pages', 'max_depth']);
        });
    }
};