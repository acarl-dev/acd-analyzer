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
            $table->string('code')->after('crawl_run_id')->index();
            $table->string('severity')->after('code')->default('medium');
            $table->string('source')->after('severity')->index();
            $table->json('context')->nullable()->after('message');
            $table->timestamp('occurred_at')->nullable()->after('context');
            
            // Make depth nullable
            $table->unsignedInteger('depth')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crawl_errors', function (Blueprint $table) {
            $table->dropColumn(['code', 'severity', 'source', 'context', 'occurred_at']);
        });
    }
};
