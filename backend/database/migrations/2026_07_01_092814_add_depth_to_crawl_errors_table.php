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
        Schema::table('crawl_errors', function (Blueprint $table) {
            $table->unsignedInteger('depth')->default(0)->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('crawl_errors', function (Blueprint $table) {
            $table->dropColumn('depth');
        });
    }
};
